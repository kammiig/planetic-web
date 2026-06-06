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
use Stripe\Checkout\Session;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
    }

    private function fakeStripeSession(): void
    {
        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')->andReturn(
                Session::constructFrom(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.test/pay'])
            );
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

    public function test_checkout_creates_order_before_redirecting_to_stripe(): void
    {
        Queue::fake();
        $this->fakeStripeSession();

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();

        $response = $this->actingAs($user)->post('/checkout/start', $this->billingPayload());

        $response->assertRedirect('https://checkout.stripe.test/pay');

        $order = Order::firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertEquals(200.00, (float) $order->total);
        $this->assertSame('pending', $order->status->value);
        $this->assertSame('cs_test_123', $order->stripe_checkout_session_id);

        // No provisioning happens at checkout — only after a verified webhook.
        Queue::assertNotPushed(ProvisionOrderJob::class);
        $this->assertNull($order->paid_at);
    }

    public function test_checkout_requires_accepted_terms(): void
    {
        $this->fakeStripeSession();
        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package']);

        $this->actingAs($user)
            ->from('/checkout')
            ->post('/checkout/start', $this->billingPayload(['terms' => '']))
            ->assertSessionHasErrors('terms');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_start_requires_authentication(): void
    {
        $this->post('/checkout/start', $this->billingPayload())->assertRedirect(route('login'));
    }

    public function test_billing_details_are_saved_to_the_profile(): void
    {
        Queue::fake();
        $this->fakeStripeSession();

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->post('/checkout/start', $this->billingPayload(['billing_city' => 'Manchester']));

        $this->assertSame('Manchester', $user->fresh()->billing_city);
    }
}
