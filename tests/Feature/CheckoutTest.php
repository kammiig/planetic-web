<?php

namespace Tests\Feature;

use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\Order;
use App\Services\Billing\StripeService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Stripe\PaymentIntent;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
    }

    /**
     * Mock the Stripe call so no network happens, mirroring the real method's
     * persistence of the intent id on the order.
     */
    private function fakePaymentIntent(): void
    {
        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createOrReusePaymentIntent')->andReturnUsing(function (Order $order) {
                $order->forceFill(['stripe_payment_intent_id' => 'pi_test_123'])->save();

                return PaymentIntent::constructFrom([
                    'id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret_abc',
                    'status' => 'requires_payment_method',
                    'amount' => 20000,
                    'currency' => 'gbp',
                ]);
            });
        });
    }

    private function billingPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Customer',
            'phone' => '+447000000000',
            'company_name' => 'Example Ltd',
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'terms' => '1',
        ], $overrides);
    }

    public function test_payment_intent_creates_a_pending_order_and_returns_client_secret(): void
    {
        Queue::fake();
        $this->fakePaymentIntent();

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();

        $response = $this->actingAs($user)->postJson('/checkout/payment-intent', $this->billingPayload());

        $response->assertOk()
            ->assertJsonPath('client_secret', 'pi_test_123_secret_abc')
            ->assertJsonPath('currency', 'GBP');

        $order = Order::firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertEquals(200.00, (float) $order->total);
        $this->assertSame('pending', $order->status->value);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);

        // No provisioning happens at checkout — only after a verified webhook.
        Queue::assertNotPushed(ProvisionOrderJob::class);
        $this->assertNull($order->paid_at);
    }

    public function test_checkout_requires_accepted_terms(): void
    {
        $this->fakePaymentIntent();

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package']);

        $this->actingAs($user)
            ->postJson('/checkout/payment-intent', $this->billingPayload(['terms' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('terms');

        $this->assertSame(0, Order::count());
    }

    public function test_payment_intent_requires_authentication(): void
    {
        $this->post('/checkout/payment-intent', $this->billingPayload())->assertRedirect(route('login'));
    }

    public function test_billing_details_are_saved_to_the_profile(): void
    {
        Queue::fake();
        $this->fakePaymentIntent();

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->postJson('/checkout/payment-intent', $this->billingPayload(['billing_city' => 'Manchester']));

        $this->assertSame('Manchester', $user->fresh()->billing_city);
    }
}
