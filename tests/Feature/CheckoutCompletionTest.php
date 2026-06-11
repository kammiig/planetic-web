<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\StripeService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Proves the webhook-independent completion path: the success page and the
 * scheduled sweep verify payment with Stripe server-side and finish the order,
 * so a missing/misconfigured webhook endpoint can never strand a paid order.
 * Also covers the inline checkout auth (no redirect, cart preserved) and that
 * email verification never blocks paying.
 */
class CheckoutCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);

        // Synchronous provisioning, dry-run: records are created and activated
        // locally without calling NameSilo / WHM / Cloudflare.
        config()->set('provisioning.sync', true);
        config()->set('provisioning.dry_run', true);
        config()->set('whm.server_hostname', 'srv1.planeticweb.com');
        config()->set('hosting.default_package', 'planetic_starter');
    }

    private function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'email_verified_at' => now(),
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'phone' => '+447000000000',
        ]);
    }

    private function pendingOrder(User $user, ?string $paymentIntentId = 'pi_test_123'): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.(10000 + random_int(100, 999)),
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Pending->value,
            'currency' => 'GBP',
            'subtotal' => 12.99,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 12.99,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        $order->items()->create([
            'item_type' => 'domain_registration',
            'name' => 'Domain registration: example.com',
            'domain_name' => 'example.com',
            'quantity' => 1,
            'unit_price' => 12.99,
            'total' => 12.99,
        ]);

        return $order->load('items');
    }

    public function test_success_page_completes_the_order_when_stripe_confirms_payment(): void
    {
        $user = $this->customer();
        $order = $this->pendingOrder($user);

        // Stripe (server-to-server) says the charge succeeded; no webhook needed.
        $this->partialMock(StripeService::class, function ($mock) {
            $mock->shouldReceive('findSucceededPayment')
                ->once()
                ->andReturn(['payment_intent' => 'pi_test_123']);
        });

        $this->actingAs($user)
            ->get(route('checkout.success', ['payment_intent' => 'pi_test_123', 'redirect_status' => 'succeeded']))
            ->assertOk()
            ->assertSee($order->order_number);

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(OrderStatus::Completed, $order->status);

        // The Domains tab has a visible record immediately.
        $this->assertDatabaseHas('domains', ['order_id' => $order->id, 'domain_name' => 'example.com']);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
    }

    public function test_success_page_never_trusts_the_browser_when_stripe_says_unpaid(): void
    {
        $user = $this->customer();
        $order = $this->pendingOrder($user);

        // The browser claims success but Stripe cannot confirm any charge.
        $this->partialMock(StripeService::class, function ($mock) {
            $mock->shouldReceive('findSucceededPayment')->once()->andReturnNull();
        });

        $this->actingAs($user)
            ->get(route('checkout.success', ['payment_intent' => 'pi_test_123', 'redirect_status' => 'succeeded']))
            ->assertOk();

        $order->refresh();
        $this->assertFalse($order->isPaid());
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertDatabaseMissing('domains', ['order_id' => $order->id]);
    }

    public function test_stuck_sweep_rescues_a_pending_order_whose_charge_succeeded(): void
    {
        $user = $this->customer();
        $order = $this->pendingOrder($user);

        $this->partialMock(StripeService::class, function ($mock) {
            $mock->shouldReceive('findSucceededPayment')
                ->once()
                ->andReturn(['payment_intent' => 'pi_test_123']);
        });

        // The scheduled every-10-minutes sweep — no webhook, no customer visit.
        $this->artisan('orders:provision', ['--stuck' => true])->assertSuccessful();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertDatabaseHas('domains', ['order_id' => $order->id]);
    }

    public function test_stuck_sweep_skips_abandoned_checkouts(): void
    {
        $user = $this->customer();
        $order = $this->pendingOrder($user);

        // Stripe has no successful charge (customer abandoned at the card step).
        $this->partialMock(StripeService::class, function ($mock) {
            $mock->shouldReceive('findSucceededPayment')->once()->andReturnNull();
        });

        $this->artisan('orders:provision', ['--stuck' => true])->assertSuccessful();

        $order->refresh();
        $this->assertFalse($order->isPaid());
        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_guest_checkout_shows_inline_account_forms_not_redirect_links(): void
    {
        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'example.com'])
            ->assertRedirect();

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Create account')
            ->assertSee('Sign in')
            // The inline auth component is mounted (no redirect to /register).
            ->assertSee('checkoutAuth', false)
            ->assertSee('Create account &amp; continue', false);
    }

    public function test_inline_registration_keeps_the_cart_and_stays_in_checkout(): void
    {
        Notification::fake();

        // Guest builds a cart…
        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'example.com']);
        $cart = Cart::firstOrFail();
        $this->assertNull($cart->user_id);

        // …then creates an account WITHOUT leaving checkout.
        $this->postJson(route('checkout.register'), [
            'name' => 'Jane Buyer',
            'email' => 'jane@example.com',
            'password' => 'Sup3r$ecret!!',
            'password_confirmation' => 'Sup3r$ecret!!',
            'terms' => '1',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertAuthenticated();
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));

        // The verification email went out in the background…
        Notification::assertSentTo($user, \App\Notifications\VerifyEmailNotification::class);

        // …and the cart survived authentication and now belongs to the user.
        $this->get('/checkout')->assertOk()->assertSee('example.com');
        $this->assertSame($user->id, $cart->fresh()->user_id);
        $this->assertSame(1, Cart::count());
    }

    public function test_inline_login_keeps_the_cart(): void
    {
        $user = $this->customer(['email' => 'returning@example.com']);

        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'example.com']);
        $cart = Cart::firstOrFail();

        $this->postJson(route('checkout.login'), [
            'email' => 'returning@example.com',
            'password' => 'password',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertAuthenticatedAs($user);

        // The page reload after inline login claims the guest cart for the user.
        $this->get('/checkout')->assertOk()->assertSee('example.com');
        $this->assertSame($user->id, $cart->fresh()->user_id);
    }

    public function test_inline_registration_validation_errors_return_json(): void
    {
        $this->postJson(route('checkout.register'), [
            'name' => 'No Terms',
            'email' => 'bad',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['email', 'password', 'terms']]);

        $this->assertGuest();
    }

    public function test_unverified_users_can_start_payment(): void
    {
        $user = $this->customer(['email_verified_at' => null]);

        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'example.com']);

        $intent = \Stripe\PaymentIntent::constructFrom([
            'id' => 'pi_unverified_1',
            'client_secret' => 'pi_unverified_1_secret_abc',
            'status' => 'requires_payment_method',
        ]);

        $this->partialMock(StripeService::class, function ($mock) use ($intent) {
            $mock->shouldReceive('createOrReusePaymentIntent')->once()->andReturn($intent);
        });

        // Email verification must never block a purchase.
        $this->actingAs($user)->postJson(route('checkout.payment-intent'), [
            'name' => 'Jane Buyer',
            'phone' => '+447000000000',
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'terms' => '1',
        ])->assertOk()->assertJsonStructure(['client_secret', 'order_number']);
    }
}
