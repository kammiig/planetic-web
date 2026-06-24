<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\TldPricing;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TldPricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TldPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, TldPricingSeeder::class]);

        config()->set('domain.default_registrar', 'porkbun');
        config()->set('domain.porkbun.api_key', 'pk_test');
        config()->set('domain.porkbun.secret_key', 'sk_test');
        config()->set('domain.porkbun.endpoint', 'https://api.porkbun.com/api/json/v3');
    }

    private function fakePorkbun(array $availableDomains): void
    {
        Http::fake(['api.porkbun.com/*' => function (Request $request) use ($availableDomains) {
            $domain = strtolower(basename((string) parse_url($request->url(), PHP_URL_PATH)));

            return Http::response([
                'status' => 'SUCCESS',
                'response' => in_array($domain, $availableDomains, true)
                    ? ['avail' => 'yes', 'price' => '9.68', 'premium' => 'no']
                    : ['avail' => 'no'],
            ]);
        }]);
    }

    public function test_resolver_matches_longest_tld_suffix(): void
    {
        $this->assertSame(8.99, TldPricing::priceForDomain('shop.example.co.uk'));
        $this->assertSame(12.99, TldPricing::priceForDomain('example.com'));
        $this->assertNull(TldPricing::priceForDomain('example.unknown-tld'));
    }

    public function test_domain_search_uses_admin_tld_price(): void
    {
        $this->fakePorkbun(['brandnew-name.com']);

        // Admin sets a new .com price (model save fires the cache-flush event).
        $com = TldPricing::where('tld', 'com')->firstOrFail();
        $com->register_price = 55.00;
        $com->save();

        $this->postJson('/domains/search', ['domain' => 'brandnew-name.com'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'available' => true,
                'currency' => 'GBP',
                'price' => '55.00',
            ]);
    }

    public function test_cart_charges_the_admin_tld_price_for_a_domain(): void
    {
        $com = TldPricing::where('tld', 'com')->firstOrFail();
        $com->register_price = 42.00;
        $com->save();

        $this->postJson('/cart/items', [
            'item_type' => 'domain_registration',
            'domain_name' => 'mybrand.com',
        ])->assertSuccessful();

        $item = CartItem::where('domain_name', 'mybrand.com')->firstOrFail();
        $this->assertSame('42.00', (string) $item->unit_price);
    }
}
