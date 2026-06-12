<?php

namespace Tests\Feature;

use App\Actions\Domains\CheckDomainAvailability;
use App\Enums\ItemType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
    }

    private function hostingProduct(): Product
    {
        return Product::query()->whereHas('hostingPackage')->firstOrFail();
    }

    private function fakeAvailability(): void
    {
        $this->mock(CheckDomainAvailability::class, function ($mock) {
            $mock->shouldReceive('handle')->andReturn([
                'domain' => 'chosen.com', 'available' => true, 'price' => '12.99',
            ]);
        });
    }

    public function test_removing_an_item_actually_removes_it_and_updates_the_total(): void
    {
        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'example.com']);
        $item = CartItem::firstOrFail();

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        $this->assertSame(0.0, (float) Cart::firstOrFail()->total);
    }

    public function test_no_false_success_when_the_item_belongs_to_someone_else(): void
    {
        // Another visitor's cart with an item in it.
        $other = User::factory()->create();
        $otherCart = Cart::create(['user_id' => $other->id, 'session_id' => 'other', 'currency' => 'GBP']);
        $foreign = $otherCart->items()->create([
            'item_type' => ItemType::DomainRegistration->value,
            'name' => 'Domain registration: theirs.com',
            'domain_name' => 'theirs.com',
            'quantity' => 1, 'unit_price' => 12.99, 'total' => 12.99,
        ]);

        // Current visitor (separate cart) tries to remove it.
        $this->post('/cart/items', ['item_type' => 'domain_registration', 'domain_name' => 'mine.com']);

        $this->delete(route('cart.items.destroy', $foreign))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('cart_items', ['id' => $foreign->id]);
    }

    public function test_removing_the_website_package_also_drops_its_free_domain_line(): void
    {
        $this->fakeAvailability();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new', 'domain_name' => 'chosen.com',
        ])->assertOk();

        $cart = Cart::firstOrFail()->load('items');
        $this->assertSame(2, $cart->items->count()); // package + free domain line

        $package = $cart->items->firstWhere('item_type', ItemType::WebsitePackage);
        $this->actingAs($user)->delete(route('cart.items.destroy', $package))->assertSessionHas('success');

        // The £0 registration line cannot survive on its own.
        $cart = $cart->fresh('items');
        $this->assertSame(0, $cart->items->count());
        $this->assertSame(0.0, (float) $cart->total);
    }

    public function test_removing_the_domain_line_resets_the_choice_so_checkout_asks_again(): void
    {
        $this->fakeAvailability();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting', 'product_id' => $this->hostingProduct()->id,
        ]);
        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new', 'domain_name' => 'chosen.com',
        ])->assertOk();

        $cart = Cart::firstOrFail()->load('items');
        $domainLine = $cart->items->firstWhere('item_type', ItemType::DomainRegistration);

        $this->actingAs($user)->delete(route('cart.items.destroy', $domainLine))->assertSessionHas('success');

        // Hosting stays, but its domain choice is gone → payment is blocked again.
        $hosting = $cart->fresh('items')->items->firstWhere('item_type', ItemType::Hosting);
        $this->assertNull($hosting->domain_name);
        $this->assertArrayNotHasKey('domain_source', $hosting->metadata ?? []);

        $this->actingAs($user)->postJson(route('checkout.payment-intent'), [
            'name' => 'Test', 'phone' => '+447000000000',
            'billing_address_line_1' => '1 High Street', 'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA', 'billing_country' => 'GB', 'terms' => '1',
        ])->assertStatus(422);
    }

    public function test_removing_the_website_package_reprices_the_domain_for_remaining_hosting(): void
    {
        $this->fakeAvailability();
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Website package + hosting plan + a free new domain.
        $this->actingAs($user)->post('/cart/items', ['item_type' => 'website_package']);
        $this->actingAs($user)->post('/cart/items', [
            'item_type' => 'hosting', 'product_id' => $this->hostingProduct()->id,
        ]);
        $this->actingAs($user)->postJson(route('checkout.domain'), [
            'domain_source' => 'new', 'domain_name' => 'chosen.com',
        ])->assertOk();

        $cart = Cart::firstOrFail()->load('items');
        $this->assertSame(0.0, (float) $cart->items->firstWhere('item_type', ItemType::DomainRegistration)->unit_price);

        // Drop the package — the free-domain perk goes with it.
        $package = $cart->items->firstWhere('item_type', ItemType::WebsitePackage);
        $this->actingAs($user)->delete(route('cart.items.destroy', $package));

        $line = $cart->fresh('items')->items->firstWhere('item_type', ItemType::DomainRegistration);
        $this->assertNotNull($line);
        $this->assertGreaterThan(0, (float) $line->unit_price);
        $this->assertStringNotContainsString('free first year', $line->name);
    }
}
