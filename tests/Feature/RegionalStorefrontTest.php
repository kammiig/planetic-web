<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\TldPricing;
use App\Models\User;
use App\Services\Billing\StripeService;
use App\Support\Region;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TldPricingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The regional storefronts (config/regions.php).
 *
 * The governing rule under test throughout: a request's region comes from its
 * URL and nothing else. Geolocation may SUGGEST a storefront but never changes
 * what a URL returns — that is what keeps every page independently cacheable at
 * Cloudflare (which will not vary its cache on CF-IPCountry outside Enterprise)
 * and keeps both versions indexable by Googlebot, which crawls mainly from US
 * IP addresses.
 */
class RegionalStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class, TldPricingSeeder::class]);
    }

    protected function tearDown(): void
    {
        Region::flush();
        parent::tearDown();
    }

    /* ------------------------------------------------------------------ */
    /* Storefront rendering */
    /* ------------------------------------------------------------------ */

    public function test_uk_storefront_prices_in_pounds(): void
    {
        $response = $this->get('/hosting');

        $response->assertOk();
        $response->assertSee('£', false);
        $response->assertDontSee('$9', false);
    }

    public function test_international_storefront_prices_in_dollars(): void
    {
        $response = $this->get('/int/hosting');

        $response->assertOk();
        $response->assertSee('$', false);
    }

    public function test_the_same_url_returns_the_same_currency_regardless_of_visitor_country(): void
    {
        // The heart of the design: an American visitor on the UK tree still gets
        // the UK page. Anything else would poison the CDN cache and hide one
        // storefront from Google.
        $fromUs = $this->withHeaders(['CF-IPCountry' => 'US'])->get('/hosting');
        $fromUk = $this->withHeaders(['CF-IPCountry' => 'GB'])->get('/hosting');

        $fromUs->assertOk();
        $fromUk->assertOk();
        $this->assertSame($fromUs->getContent(), $fromUk->getContent());
    }

    public function test_geolocation_never_redirects(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'US'])->get('/hosting')->assertOk();
        $this->withHeaders(['CF-IPCountry' => 'GB'])->get('/int/hosting')->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* SEO */
    /* ------------------------------------------------------------------ */

    public function test_pages_declare_reciprocal_hreflang_alternates(): void
    {
        $uk = $this->get('/hosting');
        $uk->assertSee('hreflang="en-GB"', false);
        $uk->assertSee('hreflang="en"', false);
        $uk->assertSee('hreflang="x-default"', false);
        $uk->assertSee('href="'.url('/int/hosting').'"', false);

        // Reciprocity is what Google requires: the international page must point
        // back at the UK one, or the pair is ignored.
        $int = $this->get('/int/hosting');
        $int->assertSee('href="'.url('/hosting').'"', false);
        $int->assertSee('hreflang="x-default"', false);
    }

    public function test_each_storefront_canonicalises_to_itself(): void
    {
        // Neither tree is a duplicate of the other, so neither may canonicalise
        // away to the other — that would de-index one of them.
        $this->get('/hosting')->assertSee('<link rel="canonical" href="'.url('/hosting').'">', false);
        $this->get('/int/hosting')->assertSee('<link rel="canonical" href="'.url('/int/hosting').'">', false);
    }

    public function test_html_lang_matches_the_storefront(): void
    {
        $this->get('/hosting')->assertSee('<html lang="en-GB"', false);
        $this->get('/int/hosting')->assertSee('<html lang="en"', false);
    }

    public function test_sitemap_lists_both_trees_with_alternates(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(url('/hosting'), false);
        $response->assertSee(url('/int/hosting'), false);
        $response->assertSee('xhtml:link rel="alternate" hreflang="en-GB"', false);
        $response->assertSee('hreflang="x-default"', false);
    }

    /* ------------------------------------------------------------------ */
    /* Region hint (the only use of geolocation) */
    /* ------------------------------------------------------------------ */

    public function test_region_hint_reads_the_cloudflare_country_header(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'US'])
            ->getJson('/region-hint')
            ->assertOk()
            ->assertJson(['suggested' => 'int', 'currency' => 'USD', 'prompt' => true, 'country' => 'US']);

        $this->withHeaders(['CF-IPCountry' => 'GB'])
            ->getJson('/region-hint')
            ->assertOk()
            ->assertJson(['suggested' => 'uk', 'currency' => 'GBP']);
    }

    public function test_region_hint_is_never_cached(): void
    {
        // If this response were cacheable it would hand one visitor's country to
        // everyone behind the same CDN edge.
        $response = $this->withHeaders(['CF-IPCountry' => 'US'])->getJson('/region-hint');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_an_explicit_choice_beats_geolocation_and_stops_the_prompt(): void
    {
        // The cookie is written by client-side JS, so it arrives unencrypted —
        // it is exempted from Laravel's cookie encryption in bootstrap/app.php,
        // or every client-set value would fail to decrypt and be discarded.
        // withCredentials() is what makes the test client send cookies on an
        // XHR-style request, matching how the banner actually calls this.
        $response = $this->withHeaders(['CF-IPCountry' => 'US'])
            ->withCredentials()
            ->withUnencryptedCookie('pw_region', 'uk')
            ->getJson('/region-hint');

        $response->assertJson(['suggested' => 'uk', 'prompt' => false]);
    }

    public function test_an_unknown_region_cookie_is_ignored(): void
    {
        // The cookie is client-writable, so its value is never trusted.
        $this->withHeaders(['CF-IPCountry' => 'US'])
            ->withCredentials()
            ->withUnencryptedCookie('pw_region', '../../etc/passwd')
            ->getJson('/region-hint')
            ->assertOk()
            ->assertJson(['suggested' => 'int', 'prompt' => true]);
    }

    public function test_unknown_and_tor_country_codes_yield_no_suggestion(): void
    {
        foreach (['XX', 'T1', ''] as $code) {
            $this->withHeaders(['CF-IPCountry' => $code])
                ->getJson('/region-hint')
                ->assertOk()
                ->assertJson(['suggested' => null, 'prompt' => false]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Currency correctness through checkout */
    /* ------------------------------------------------------------------ */

    public function test_a_cart_started_in_the_international_storefront_is_priced_in_usd(): void
    {
        $user = User::factory()->create();
        $product = Product::where('type', 'hosting')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/int/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'monthly'])
            ->assertSuccessful();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('USD', $cart->currency);
        $this->assertEqualsWithDelta(
            (float) $product->priceFor('monthly', 'USD')->amount,
            (float) $cart->items->first()->unit_price,
            0.001,
        );
    }

    /**
     * The rule that protects the customer: the currency is locked when the cart
     * is created. Wandering into the other storefront mid-session must not
     * re-price what is already in the basket.
     */
    public function test_switching_storefront_does_not_reprice_an_existing_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::where('type', 'hosting')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'monthly'])
            ->assertSuccessful();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('GBP', $cart->currency);

        // Now add a second item from the international storefront.
        $this->actingAs($user)
            ->postJson('/int/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'yearly'])
            ->assertSuccessful();

        $cart->refresh()->load('items');

        $this->assertSame('GBP', $cart->currency, 'The cart currency must not change mid-session.');

        foreach ($cart->items as $item) {
            $gbp = (float) $product->priceFor($item->metadata['billing_cycle'], 'GBP')->amount;
            $this->assertEqualsWithDelta($gbp, (float) $item->unit_price, 0.001);
        }
    }

    public function test_stripe_charges_the_currency_stored_on_the_order(): void
    {
        $order = new Order(['currency' => 'USD']);

        // Not the global config default, and not the request's region.
        $this->assertSame('usd', app(StripeService::class)->currencyFor($order));
        $this->assertSame('gbp', app(StripeService::class)->currencyFor(new Order(['currency' => 'GBP'])));
    }

    public function test_a_legacy_order_without_a_currency_falls_back_to_config(): void
    {
        config()->set('stripe.currency', 'gbp');

        $this->assertSame('gbp', app(StripeService::class)->currencyFor(new Order([])));
    }

    /* ------------------------------------------------------------------ */
    /* Prices are never converted at runtime */
    /* ------------------------------------------------------------------ */

    public function test_a_tld_without_a_usd_price_is_not_sold_internationally(): void
    {
        // .co.uk is a UK-only name and carries no USD price. It must be withheld
        // from the international storefront, not quoted at an exchange rate.
        $uk = TldPricing::where('tld', 'co.uk')->firstOrFail();

        $this->assertNotNull($uk->registerPrice('GBP'));
        $this->assertNull($uk->registerPrice('USD'));
        $this->assertFalse($uk->availableIn('USD'));
    }

    public function test_a_product_priced_only_in_gbp_cannot_be_bought_in_usd(): void
    {
        $user = User::factory()->create();
        $product = Product::where('type', 'hosting')->firstOrFail();

        // Withdraw the USD price, as an admin would by clearing the field.
        $product->prices()->where('currency', 'USD')->delete();
        $product->refresh();

        $this->actingAs($user)
            ->postJson('/int/cart/items', ['item_type' => 'hosting', 'product_id' => $product->id, 'billing_cycle' => 'monthly'])
            ->assertStatus(422);
    }

    /* ------------------------------------------------------------------ */
    /* Tax presentation */
    /* ------------------------------------------------------------------ */

    public function test_no_tax_line_is_shown_until_the_business_is_vat_registered(): void
    {
        // A "VAT £0.00" row implies an HMRC registration that does not exist.
        config()->set('regions.regions.uk.tax.registered', false);
        Region::flush();

        $this->assertFalse(Region::make('uk')->chargesTax());
        $this->assertSame(0.0, Region::make('uk')->taxOn(240.00));
    }

    public function test_vat_is_extracted_from_an_inclusive_total_once_registered(): void
    {
        config()->set('regions.regions.uk.tax.registered', true);
        config()->set('regions.regions.uk.tax.rate', 0.20);
        Region::flush();

        $region = Region::make('uk');

        $this->assertTrue($region->chargesTax());
        // £240 inclusive of 20% VAT contains £40 of VAT, not £48.
        $this->assertEqualsWithDelta(40.00, $region->taxOn(240.00), 0.01);
    }

    /* ------------------------------------------------------------------ */
    /* Admin-editable copy */
    /* ------------------------------------------------------------------ */

    public function test_tokens_in_admin_copy_render_in_the_storefront_currency(): void
    {
        Faq::create([
            'page' => 'home',
            'question' => 'How much is it?',
            'answer' => 'The :price package includes a free domain. Prices in :currency.',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        Region::setCurrent(Region::make('uk'));
        $uk = Faq::where('question', 'How much is it?')->firstOrFail();
        $this->assertStringContainsString('£200', $uk->answer);
        $this->assertStringContainsString('GBP', $uk->answer);

        Region::setCurrent(Region::make('int'));
        $int = Faq::where('question', 'How much is it?')->firstOrFail();
        $this->assertStringContainsString('$249', $int->answer);
        $this->assertStringContainsString('USD', $int->answer);
    }

    public function test_untokenised_copy_is_left_exactly_as_written(): void
    {
        Faq::create([
            'page' => 'home', 'question' => 'Plain?', 'answer' => 'No tokens here at all.',
            'is_active' => true, 'sort_order' => 98,
        ]);

        $this->assertSame('No tokens here at all.', Faq::where('question', 'Plain?')->firstOrFail()->answer);
    }

    /**
     * A testimonial is a real customer's words. Substituting a different figure
     * into a quotation would attribute a price to someone who never said it, so
     * testimonials are deliberately excluded from token substitution.
     */
    public function test_testimonial_quotes_are_never_rewritten(): void
    {
        Testimonial::create([
            'author_name' => 'A Customer',
            'body' => 'The £200 package was incredible value.',
            'rating' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Region::setCurrent(Region::make('int'));

        $this->assertSame(
            'The £200 package was incredible value.',
            Testimonial::where('author_name', 'A Customer')->firstOrFail()->body,
        );
    }

    /* ------------------------------------------------------------------ */
    /* Path translation */
    /* ------------------------------------------------------------------ */

    public function test_paths_translate_between_storefronts(): void
    {
        $uk = Region::make('uk');
        $int = Region::make('int');

        $this->assertSame('/int/hosting', $int->translatePath('/hosting'));
        $this->assertSame('/hosting', $uk->translatePath('/int/hosting'));
        $this->assertSame('/int', $int->translatePath('/'));
        $this->assertSame('/', $uk->translatePath('/int'));
        // Already in the target tree — must be idempotent.
        $this->assertSame('/int/hosting', $int->translatePath('/int/hosting'));
    }
}
