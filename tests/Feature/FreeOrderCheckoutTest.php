<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Billing\StripeService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Stripe\SetupIntent;
use Tests\TestCase;

class FreeOrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
        // Provision inline without touching external APIs.
        config(['provisioning.sync' => true, 'provisioning.dry_run' => true]);
        Http::fake();
    }

    private function billingPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Customer',
            'phone' => '+447000000000',
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'terms' => '1',
        ], $overrides);
    }

    /** A £0 hosting plan that includes a free domain. */
    private function makeFreeHostingProduct(): Product
    {
        $product = Product::ofType(ProductType::Hosting)->with('hostingPackage')->firstOrFail();
        $product->prices()->where('billing_cycle', 'monthly')->update(['amount' => 0]);
        $product->hostingPackage->update(['includes_free_domain' => true]);

        return $product;
    }

    public function test_free_order_completes_without_stripe_and_starts_provisioning(): void
    {
        $product = $this->makeFreeHostingProduct();
        $user = $this->createUser();
        $this->actingAs($user);

        $this->postJson('/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'monthly'])->assertOk();
        $this->postJson('/checkout/domain', ['domain_source' => 'existing', 'domain_name' => 'myexisting.com'])->assertOk();

        $this->postJson('/checkout/complete-free', $this->billingPayload())
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('free', true);

        $order = Order::firstOrFail();
        $this->assertEquals(0.0, (float) $order->total);
        $this->assertTrue($order->isPaid());
        $this->assertSame('no_payment_required', $order->payment_status->value);
        $this->assertNotNull($order->paid_at);
        $this->assertNull($order->stripe_payment_intent_id);

        // Provisioning created the customer-visible service records.
        $this->assertDatabaseHas('hosting_accounts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('domains', ['user_id' => $user->id, 'domain_name' => 'myexisting.com']);

        // A free entry exists in the payment ledger (no real charge).
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'no_payment_required', 'amount' => '0.00']);
    }

    public function test_complete_free_refuses_a_payable_order(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // The £200 website package is not free.
        $this->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();
        $this->postJson('/checkout/domain', ['domain_source' => 'later'])->assertOk();

        $this->postJson('/checkout/complete-free', $this->billingPayload())
            ->assertStatus(422)
            ->assertJsonPath('error', 'This order requires payment.');
    }

    public function test_free_order_requires_a_setup_intent_when_card_is_required(): void
    {
        SiteSetting::set('checkout.require_card_for_free_orders', '1', 'checkout', 'boolean');

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('hasSavedCard')->andReturn(false);
            $mock->shouldReceive('createSetupIntent')->andReturn(SetupIntent::constructFrom([
                'id' => 'seti_test_1',
                'client_secret' => 'seti_test_1_secret',
                'status' => 'requires_payment_method',
            ]));
        });

        $product = $this->makeFreeHostingProduct();
        $user = $this->createUser();
        $this->actingAs($user);

        $this->postJson('/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'monthly'])->assertOk();
        $this->postJson('/checkout/domain', ['domain_source' => 'existing', 'domain_name' => 'mine.com'])->assertOk();

        $this->postJson('/checkout/complete-free', $this->billingPayload())
            ->assertOk()
            ->assertJsonPath('setup_required', true)
            ->assertJsonPath('client_secret', 'seti_test_1_secret');

        // The order must NOT be completed until the card is saved.
        $this->assertFalse(Order::firstOrFail()->isPaid());
    }
}
