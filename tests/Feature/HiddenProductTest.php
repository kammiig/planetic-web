<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unlisted plans: withheld from every public pricing table, still fully
 * buyable through the private add-to-cart link an admin sends out.
 */
class HiddenProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class, SiteSettingSeeder::class, FaqSeeder::class]);
    }

    private function hideStarterHosting(): Product
    {
        $starter = Product::where('slug', 'starter-hosting')->firstOrFail();
        $starter->update(['is_hidden' => true]);

        return $starter;
    }

    public function test_a_hidden_plan_disappears_from_the_public_pricing_tables(): void
    {
        $starter = $this->hideStarterHosting();

        $this->get('/hosting')->assertOk()->assertDontSee($starter->name);
        $this->get('/')->assertOk()->assertDontSee('Choose '.$starter->name);
    }

    public function test_a_listed_plan_still_appears(): void
    {
        $this->get('/hosting')->assertOk()->assertSee('Starter Hosting');
    }

    public function test_a_hidden_plan_is_still_buyable_through_its_direct_link(): void
    {
        $starter = $this->hideStarterHosting();

        $this->get('/cart/add/starter-hosting')
            ->assertRedirect('/cart')
            ->assertSessionHas('success');

        // Priced server-side from the catalogue, exactly as the button would be.
        $this->assertDatabaseHas('cart_items', [
            'item_type' => 'hosting',
            'product_id' => $starter->id,
            'total' => 4.99,
        ]);
    }

    public function test_the_direct_link_honours_the_requested_billing_cycle(): void
    {
        $starter = $this->hideStarterHosting();

        $this->get('/cart/add/starter-hosting?cycle=yearly')->assertRedirect('/cart');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $starter->id,
            'total' => 49.00,
        ]);
    }

    public function test_the_direct_link_adds_the_website_package(): void
    {
        $this->get('/cart/add/complete-bespoke-website')->assertRedirect('/cart');

        $this->assertDatabaseHas('cart_items', ['item_type' => 'website_package', 'total' => 200.00]);
    }

    public function test_the_direct_link_rejects_unknown_and_unbuyable_products(): void
    {
        $this->get('/cart/add/no-such-plan')->assertNotFound();

        // A domain needs a name chosen at search time, so it has no one-click link.
        $this->get('/cart/add/domain-registration')->assertNotFound();
    }

    public function test_a_deactivated_plan_is_not_reachable_by_link(): void
    {
        // is_hidden hides; is_active still withdraws the product entirely.
        Product::where('slug', 'starter-hosting')->update(['is_active' => false]);

        $this->get('/cart/add/starter-hosting')->assertNotFound();
    }
}
