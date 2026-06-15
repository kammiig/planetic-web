<?php

namespace Tests\Feature;

use App\Enums\HostingStatus;
use App\Enums\RoleName;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Services\Hosting\WhmService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostingCpanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
        config()->set('whm.host', 'whm.test');
        config()->set('whm.username', 'root');
        config()->set('whm.token', 'whm-token');
    }

    private function activeAccount(\App\Models\User $user): HostingAccount
    {
        $package = HostingPackage::first();

        return HostingAccount::create([
            'user_id' => $user->id,
            'hosting_package_id' => $package->id,
            'domain_name' => 'sdafads-check3.com',
            'whm_username' => 'pwsdaf18',
            'server_ip' => '185.61.154.31',
            'status' => HostingStatus::Active->value,
            'created_on_whm_at' => now(),
        ]);
    }

    public function test_one_click_cpanel_redirects_to_the_whm_session_url(): void
    {
        Http::fake(function (Request $request) {
            return Http::response(['data' => ['url' => 'https://whm.test:2083/cpsess1234/login/?session=token']]);
        });

        $user = $this->createUser(RoleName::Customer);
        $account = $this->activeAccount($user);

        $this->actingAs($user)
            ->get(route('customer.hosting.cpanel', $account))
            ->assertRedirect('https://whm.test:2083/cpsess1234/login/?session=token');
    }

    public function test_cpanel_sso_is_owner_only(): void
    {
        Http::fake();
        $owner = $this->createUser(RoleName::Customer);
        $other = $this->createUser(RoleName::Customer);
        $account = $this->activeAccount($owner);

        $this->actingAs($other)
            ->get(route('customer.hosting.cpanel', $account))
            ->assertNotFound();
    }

    public function test_cpanel_sso_shows_a_safe_error_when_whm_fails(): void
    {
        Http::fake(fn () => Http::response('boom', 500));

        $user = $this->createUser(RoleName::Customer);
        $account = $this->activeAccount($user);

        $this->actingAs($user)
            ->from(route('customer.hosting.show', $account))
            ->get(route('customer.hosting.cpanel', $account))
            ->assertRedirect(route('customer.hosting.show', $account))
            ->assertSessionHas('error');
    }

    public function test_create_user_session_never_returns_secrets(): void
    {
        Http::fake(fn () => Http::response(['data' => ['url' => 'https://whm.test:2083/cpsess9/login/?session=abc']]));

        $url = app(WhmService::class)->createUserSession('pwsdaf18');

        $this->assertStringStartsWith('https://whm.test:2083/cpsess9/login/', $url);
        $this->assertStringNotContainsString('whm-token', $url);
    }
}
