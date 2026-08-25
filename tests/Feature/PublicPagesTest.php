<?php

namespace Tests\Feature;

use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
    }

    public function test_home_page_renders_with_first_year_notice(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Planetic')
            ->assertSee('Renewal applies after the first year');
    }

    public function test_website_package_page_states_first_year_only(): void
    {
        $response = $this->get('/website-package');
        $response->assertOk()
            ->assertSee('£200')
            ->assertSee('Free domain and hosting for the first year. Renewal applies after the first year.');

        // Must never market lifetime free service.
        $response->assertDontSee('free forever');
        $response->assertDontSee('free domain and hosting forever');
    }

    public function test_hosting_page_lists_seeded_plans(): void
    {
        $this->get('/hosting')
            ->assertOk()
            ->assertSee('Starter Hosting')
            ->assertSee('Business Hosting')
            ->assertSee('Pro Hosting');
    }

    public function test_home_page_shows_the_three_main_offer_cards(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Register a Domain')
            ->assertSee('Search Domain')
            ->assertSee('Choose Business Hosting')
            ->assertSee('Complete Bespoke Website')
            ->assertSee('Get Complete Website');
    }

    public function test_trustpilot_review_shows_branding_but_manual_does_not(): void
    {
        \App\Models\Testimonial::create([
            'author_name' => 'Verified Tess', 'body' => 'Brilliant Trustpilot-sourced review.',
            'rating' => 5, 'source' => 'trustpilot', 'is_verified' => true, 'is_active' => true, 'sort_order' => 1,
        ]);
        \App\Models\Testimonial::create([
            'author_name' => 'Manual Mary', 'body' => 'A genuine website review with no third-party source.',
            'rating' => 5, 'source' => 'manual', 'is_verified' => false, 'is_active' => true, 'sort_order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Verified Trustpilot review')   // branded source labelled
            ->assertSee('Verified customer');           // manual stays neutral, never faked
    }

    public function test_manual_review_never_borrows_trustpilot_or_google_branding(): void
    {
        \App\Models\Testimonial::query()->delete();
        \App\Models\Testimonial::create([
            'author_name' => 'Manual Only', 'body' => 'Only a manual review exists on the page.',
            'rating' => 5, 'source' => 'manual', 'is_verified' => false, 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Trustpilot')
            ->assertDontSee('Google review');
    }

    public function test_domain_page_renders_the_same_testimonials_as_the_home_page(): void
    {
        \App\Models\Testimonial::query()->delete();
        \App\Models\Testimonial::create([
            'author_name' => 'Shared Sam', 'body' => 'One review, edited once, shown on both pages.',
            'rating' => 5, 'source' => 'google', 'is_verified' => true, 'is_active' => true, 'sort_order' => 1,
        ]);

        // Same admin-managed rows, same component — editing a testimonial must
        // never leave the two pages disagreeing.
        foreach (['/', '/domains'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('One review, edited once, shown on both pages.')
                ->assertSee('Verified Google review')
                ->assertSee('5.0 out of 5');
        }
    }

    public function test_domain_page_drops_a_testimonial_deactivated_in_the_admin(): void
    {
        \App\Models\Testimonial::query()->delete();
        \App\Models\Testimonial::create([
            'author_name' => 'Retired Rita', 'body' => 'A review that has since been switched off.',
            'rating' => 5, 'source' => 'manual', 'is_active' => false, 'sort_order' => 1,
        ]);

        $this->get('/domains')->assertOk()->assertDontSee('A review that has since been switched off.');
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'domains' => ['/domains'],
            'hosting' => ['/hosting'],
            'website package' => ['/website-package'],
            'contact' => ['/contact'],
            'privacy' => ['/privacy-policy'],
            'terms' => ['/terms'],
            'renewal' => ['/renewal-policy'],
            'refund' => ['/refund-policy'],
        ];
    }
}
