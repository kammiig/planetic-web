<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Product;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
    }

    public function test_website_package_can_be_added_at_the_server_calculated_price(): void
    {
        $this->postJson('/cart/items', ['item_type' => 'website_package'])
            ->assertOk()
            ->assertJsonPath('cart.total', '200.00');

        $this->assertDatabaseHas('cart_items', [
            'item_type' => 'website_package',
            'total' => 200.00,
        ]);
    }

    public function test_hosting_can_be_added_with_a_billing_cycle(): void
    {
        $starter = Product::where('slug', 'starter-hosting')->firstOrFail();

        $this->postJson('/cart/items', [
            'item_type' => 'hosting',
            'product_id' => $starter->id,
            'billing_cycle' => 'yearly',
        ])->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'item_type' => 'hosting',
            'product_id' => $starter->id,
            'total' => 49.00,
        ]);
    }

    public function test_frontend_supplied_prices_are_never_trusted(): void
    {
        // Attempt to spoof a £1 website package.
        $this->postJson('/cart/items', [
            'item_type' => 'website_package',
            'unit_price' => 1,
            'total' => 1,
            'price' => 1,
            'amount' => 1,
        ])->assertOk();

        // Server recomputes from the catalogue, ignoring the spoofed price.
        $this->assertDatabaseHas('cart_items', ['item_type' => 'website_package', 'total' => 200.00]);
        $this->assertDatabaseMissing('cart_items', ['item_type' => 'website_package', 'total' => 1.00]);
    }

    public function test_domain_registration_requires_a_valid_domain(): void
    {
        $this->postJson('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'bad domain'])
            ->assertStatus(422);
    }

    public function test_duplicate_website_package_is_rejected(): void
    {
        $this->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();
        $this->postJson('/cart/items', ['item_type' => 'website_package'])->assertStatus(422);

        $this->assertSame(1, \App\Models\CartItem::where('item_type', 'website_package')->count());
    }

    public function test_items_can_be_removed(): void
    {
        $this->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();
        $item = \App\Models\CartItem::firstOrFail();

        $this->delete("/cart/items/{$item->id}")->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_guest_cart_is_claimed_after_login(): void
    {
        // Add as a guest.
        $this->postJson('/cart/items', ['item_type' => 'website_package'])->assertOk();
        $cartId = session('cart_id');
        $this->assertDatabaseHas('carts', ['id' => $cartId, 'user_id' => null]);

        // Log in and revisit the cart.
        $user = $this->createUser();
        $this->actingAs($user)->get('/cart')->assertOk();

        $this->assertDatabaseHas('carts', ['id' => $cartId, 'user_id' => $user->id]);
    }
}
