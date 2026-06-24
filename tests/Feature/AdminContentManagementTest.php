<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Filament\Resources\HostingPackages\Pages\EditHostingPackage;
use App\Filament\Resources\WebsitePackages\Pages\EditWebsitePackage;
use App\Filament\Pages\SiteContentSettings;
use App\Models\HostingPackage;
use App\Models\SeoMeta;
use App\Models\SiteSetting;
use App\Models\WebsitePackage;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SeoMetaSeeder;
use Database\Seeders\SiteSettingSeeder;
use Database\Seeders\TestimonialSeeder;
use Database\Seeders\TldPricingSeeder;
use Database\Seeders\WebsitePackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ProductSeeder::class,
            HostingPackageSeeder::class,
            WebsitePackageSeeder::class,
            TldPricingSeeder::class,
            SiteSettingSeeder::class,
            SeoMetaSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
        ]);
    }

    public function test_super_admin_can_render_all_new_admin_pages(): void
    {
        $admin = $this->createUser(RoleName::SuperAdmin);

        $pages = [
            '/admin/hosting-packages',
            '/admin/website-packages',
            '/admin/tld-pricings',
            '/admin/seo-metas',
            '/admin/faqs',
            '/admin/testimonials',
            '/admin/site-content-settings',
            '/admin/registrar-settings',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_editing_hosting_price_in_admin_updates_product_price_and_frontend(): void
    {
        $admin = $this->createUser(RoleName::SuperAdmin);
        $package = HostingPackage::whereHas('product', fn ($q) => $q->where('slug', 'starter-hosting'))->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditHostingPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm(['price_monthly' => 7.77, 'price_yearly' => 77.00])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('7.77', (string) $package->product->priceFor('monthly')->amount);

        $this->get('/hosting')->assertOk()->assertSee('7.77');
    }

    public function test_changing_whm_package_name_in_admin_persists_for_provisioning(): void
    {
        $admin = $this->createUser(RoleName::SuperAdmin);
        $package = HostingPackage::firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditHostingPackage::class, ['record' => $package->getRouteKey()])
            ->fillForm(['whm_package_name' => 'kwashqap_NewName2026'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('kwashqap_NewName2026', $package->fresh()->whm_package_name);
    }

    public function test_editing_website_package_price_updates_frontend(): void
    {
        $admin = $this->createUser(RoleName::SuperAdmin);
        $package = WebsitePackage::firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditWebsitePackage::class, ['record' => $package->getRouteKey()])
            ->fillForm(['price_one_time' => 249.00])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('249.00', (string) $package->product->priceFor('one_time')->amount);

        $this->get('/website-package')->assertOk()->assertSee('£249');
    }

    public function test_disabling_a_hosting_plan_hides_it_from_the_frontend(): void
    {
        $this->get('/hosting')->assertSee('Starter Hosting');

        HostingPackage::whereHas('product', fn ($q) => $q->where('slug', 'starter-hosting'))
            ->update(['is_active' => false]);

        $this->get('/hosting')->assertDontSee('Starter Hosting');
    }

    public function test_editing_homepage_hero_setting_updates_the_homepage(): void
    {
        SiteSetting::set('hero.title', 'A brand new headline for testing', 'hero');

        $this->get('/')->assertOk()->assertSee('A brand new headline for testing');
    }

    public function test_site_content_page_saves_and_reflects_on_frontend(): void
    {
        $admin = $this->createUser(RoleName::SuperAdmin);

        Livewire::actingAs($admin)
            ->test(SiteContentSettings::class)
            ->fillForm(['hero__title' => 'Saved via the admin CMS page'])
            ->call('save');

        $this->assertSame('Saved via the admin CMS page', SiteSetting::get('hero.title'));
        $this->get('/')->assertSee('Saved via the admin CMS page');
    }

    public function test_editing_seo_meta_updates_the_page_source(): void
    {
        SeoMeta::where('page_key', 'home')->update([
            'meta_title' => 'Custom SEO Title Set In Admin',
            'meta_description' => 'A custom meta description controlled from the admin.',
        ]);

        $response = $this->get('/');
        $response->assertSee('<title>Custom SEO Title Set In Admin · '.config('app.name').'</title>', false);
        $response->assertSee('A custom meta description controlled from the admin.', false);
    }
}
