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
