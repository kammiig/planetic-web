<?php

namespace Tests\Feature;

use App\Actions\Checkout\CompletePaidOrder;
use App\Actions\Domains\CheckDomainAvailability;
use App\Actions\Orders\CreateOrderFromCart;
use App\Enums\HostingStatus;
use App\Enums\OrderStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Cart;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Business rule: anything that includes hosting must have a domain before
 * payment — registered new, included free, or owned by the customer — except
 * the website package, which may defer it ("decide later") and then visibly
 * waits in the dashboard instead of failing WHM provisioning.
 */
class DomainChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);

        config()->set('provisioning.sync', true);
        config()->set('provisioning.dry_run', true);
        config()->set('whm.server_hostname', 'srv1.planeticweb.com');
        config()->set('hosting.default_package', 'planetic_starter');
    }

    private function customer(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'phone' => '+447000000000',
        ]);
    }

    private function hostingProduct(): Product
    {
        return Product::query()->whereHas('hostingPackage')->firstOrFail();
    }

    private function fakeAvailability(bool $available = true): void
    {
        $this->mock(CheckDomainAvailability::class, function ($mock) use ($available) {
            $mock->shouldReceive('handle')->andReturn([
                'domain' => 'chosen.com',
                'available' => $available,
                'price' => '12.99',
            ]);
        });
    }

    public function test_hosting_checkout_requires_a_domain_before_payment(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
            'billing_cycle' => 'monthly',
        ]);

        // Payment is refused until a domain choice is made.
        $this->actingAs($user)->postJson(route('checkout.payment-intent'), [
            'name' => 'Test', 'phone' => '+447000000000',
            'billing_address_line_1' => '1 High Street', 'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA', 'billing_country' => 'GB', 'terms' => '1',
        ])->assertStatus(422)->assertJsonPath('error', fn ($e) => str_contains($e, 'domain'));
    }

    public function test_hosting_cannot_defer_the_domain(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);

        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'later',
        ])->assertStatus(422)->assertJsonValidationErrors('domain_source');
    }

    public function test_existing_domain_choice_attaches_to_hosting_items(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);

        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'existing',
            'domain_name' => 'Owned-Already.com',
        ])->assertOk();

        $cart = Cart::firstOrFail()->load('items');
        $item = $cart->items->firstWhere('item_type', \App\Enums\ItemType::Hosting);

        $this->assertSame('owned-already.com', $item->domain_name);
        $this->assertSame('existing', $item->metadata['domain_source']);
        // No registration line is ever added for a customer-owned domain.
        $this->assertFalse($cart->items->contains('item_type', \App\Enums\ItemType::DomainRegistration));
    }

    public function test_new_domain_is_free_with_the_website_package(): void
    {
        $this->fakeAvailability();
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);

        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new',
            'domain_name' => 'chosen.com',
        ])->assertOk();

        $cart = Cart::firstOrFail()->load('items');
        $domainLine = $cart->items->firstWhere('item_type', \App\Enums\ItemType::DomainRegistration);

        $this->assertNotNull($domainLine);
        $this->assertSame(0.0, (float) $domainLine->unit_price);
        $this->assertStringContainsString('free first year', $domainLine->name);
        // Total still just the £200 package.
        $this->assertSame(200.0, (float) $cart->fresh()->total);
    }

    public function test_new_domain_is_charged_for_plain_hosting(): void
    {
        $this->fakeAvailability();
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);

        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new',
            'domain_name' => 'chosen.com',
        ])->assertOk();

        $domainLine = Cart::firstOrFail()->load('items')
            ->items->firstWhere('item_type', \App\Enums\ItemType::DomainRegistration);

        $this->assertNotNull($domainLine);
        $this->assertGreaterThan(0, (float) $domainLine->unit_price);
    }

    public function test_free_domain_with_flagged_hosting_plan(): void
    {
        $this->fakeAvailability();
        HostingPackage::query()->update(['includes_free_domain' => true]);
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);

        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new',
            'domain_name' => 'chosen.com',
        ])->assertOk();

        $domainLine = Cart::firstOrFail()->load('items')
            ->items->firstWhere('item_type', \App\Enums\ItemType::DomainRegistration);

        $this->assertSame(0.0, (float) $domainLine->unit_price);
    }

    public function test_website_package_can_defer_domain_and_everything_stays_visible(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->postJson(route('checkout.domain'), ['domain_source' => 'later'])->assertOk();

        $order = app(CreateOrderFromCart::class)->handle(Cart::firstOrFail()->load('items'), $user);
        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_later_1']);

        $order->refresh();

        // Order finishes cleanly — no fake failures, no manual review.
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(0, $order->provisioningJobs()
            ->whereIn('status', [ProvisioningStatus::Failed->value, ProvisioningStatus::ManualReview->value])
            ->count());

        // Hosting + project are visible; hosting waits for the domain.
        $this->assertDatabaseHas('hosting_accounts', [
            'order_id' => $order->id,
            'status' => HostingStatus::AwaitingDomain->value,
            'domain_name' => null,
        ]);
        $this->assertDatabaseHas('website_projects', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('domains', ['order_id' => $order->id]);

        // The customer got "choose your domain", not "services ready".
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'type' => 'domain_needed']);
        $this->assertDatabaseMissing('notification_logs', ['user_id' => $user->id, 'type' => 'provisioning_completed']);
    }

    public function test_adding_the_domain_later_finishes_provisioning(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->postJson(route('checkout.domain'), ['domain_source' => 'later'])->assertOk();

        $order = app(CreateOrderFromCart::class)->handle(Cart::firstOrFail()->load('items'), $user);
        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_later_2']);

        $this->fakeAvailability();

        // The dashboard prompt posts here.
        $this->actingAs($user)
            ->post(route('customer.orders.domain', $order), [
                'domain_source' => 'new',
                'domain_name' => 'chosen.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $hosting = $order->hostingAccount()->first();

        // Dry-run provisioning ran synchronously: everything is live.
        $this->assertSame('chosen.com', $hosting->domain_name);
        $this->assertSame(HostingStatus::Active, $hosting->status);
        $this->assertNotNull($hosting->whm_username);
        $this->assertDatabaseHas('domains', ['order_id' => $order->id, 'domain_name' => 'chosen.com', 'status' => 'active']);
        $this->assertSame(OrderStatus::Completed, $order->status);

        // And the real "services ready" email followed.
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'type' => 'provisioning_completed']);
    }

    public function test_existing_domain_orders_skip_registration_but_provision_hosting(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);
        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'existing',
            'domain_name' => 'owned-already.com',
        ])->assertOk();

        $order = app(CreateOrderFromCart::class)->handle(Cart::firstOrFail()->load('items'), $user);
        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_existing_1']);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);

        // Never registered by us — recorded as external, no register step at all.
        $this->assertDatabaseHas('domains', [
            'order_id' => $order->id, 'domain_name' => 'owned-already.com', 'registrar' => 'external',
        ]);
        $this->assertDatabaseMissing('provisioning_jobs', ['order_id' => $order->id, 'job_type' => 'register_domain']);
        $this->assertDatabaseMissing('provisioning_jobs', ['order_id' => $order->id, 'job_type' => 'update_nameservers']);

        // Hosting + Cloudflare still provisioned for it.
        $this->assertDatabaseHas('hosting_accounts', [
            'order_id' => $order->id, 'domain_name' => 'owned-already.com', 'status' => HostingStatus::Active->value,
        ]);
        $this->assertDatabaseHas('provisioning_jobs', ['order_id' => $order->id, 'job_type' => 'create_cloudflare_zone']);
    }

    public function test_orders_provision_parks_legacy_domainless_hosting_orders(): void
    {
        $user = $this->customer();

        // A pre-fix order: paid hosting, no domain, failed WHM step, manual review.
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-19999',
            'status' => OrderStatus::ManualReview->value,
            'payment_status' => \App\Enums\PaymentStatus::Succeeded->value,
            'paid_at' => now(),
            'currency' => 'GBP',
            'subtotal' => 4.99, 'discount_total' => 0, 'tax_total' => 0, 'total' => 4.99,
        ]);
        $order->items()->create([
            'product_id' => $this->hostingProduct()->id,
            'item_type' => 'hosting',
            'name' => 'Starter (monthly)',
            'quantity' => 1, 'unit_price' => 4.99, 'total' => 4.99,
        ]);
        $order->provisioningJobs()->create([
            'user_id' => $user->id,
            'job_type' => 'create_hosting_account',
            'status' => ProvisioningStatus::Failed->value,
            'error_message' => 'Hosting requires a domain name.',
            'max_attempts' => 3,
        ]);

        $this->artisan('orders:provision', ['order' => 'ORD-19999'])->assertSuccessful();

        $order->refresh();

        // No blind retry: hosting visible and waiting, bogus step cleared,
        // order out of manual review and completed via the email step.
        $this->assertDatabaseHas('hosting_accounts', [
            'order_id' => $order->id, 'status' => HostingStatus::AwaitingDomain->value,
        ]);
        $this->assertDatabaseMissing('provisioning_jobs', [
            'order_id' => $order->id, 'job_type' => 'create_hosting_account',
        ]);
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'type' => 'domain_needed']);
    }

    public function test_checkout_page_shows_domain_step_for_hosting_cart(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $this->hostingProduct()->id,
        ]);

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee('Choose your domain')
            ->assertSee('Register a new domain')
            ->assertSee('Use a domain I already own')
            ->assertDontSee('I\'ll decide my domain later', false);
    }

    public function test_checkout_page_offers_defer_only_for_website_package(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee('Choose your domain')
            ->assertSee('decide my domain later');
    }
}
