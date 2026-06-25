<?php

namespace Tests\Feature;

use App\Enums\HostingStatus;
use App\Enums\RoleName;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingAutoRenewTest extends TestCase
{
    use RefreshDatabase;

    private function makeDomain($user, bool $autoRenew = true): Domain
    {
        return Domain::create([
            'user_id' => $user->id,
            'domain_name' => 'foo-'.$user->id.'.com',
            'sld' => 'foo-'.$user->id,
            'tld' => 'com',
            'registrar' => 'porkbun',
            'status' => 'active',
            'auto_renew' => $autoRenew,
        ]);
    }

    public function test_customer_can_toggle_domain_auto_renew(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $domain = $this->makeDomain($user);

        $this->actingAs($user)
            ->post(route('customer.billing.domains.auto-renew', $domain))
            ->assertRedirect();

        $this->assertFalse($domain->fresh()->auto_renew);

        $this->actingAs($user)->post(route('customer.billing.domains.auto-renew', $domain));
        $this->assertTrue($domain->fresh()->auto_renew);
    }

    public function test_customer_can_toggle_hosting_auto_renew(): void
    {
        $this->seed([ProductSeeder::class, HostingPackageSeeder::class]);
        $user = $this->createUser(RoleName::Customer);

        $account = HostingAccount::create([
            'user_id' => $user->id,
            'hosting_package_id' => HostingPackage::firstOrFail()->id,
            'domain_name' => 'host.com',
            'whm_username' => 'pwuser'.$user->id,
            'status' => HostingStatus::Active->value,
            'auto_renew' => true,
        ]);

        $this->actingAs($user)
            ->post(route('customer.billing.hosting.auto-renew', $account))
            ->assertRedirect();

        $this->assertFalse($account->fresh()->auto_renew);
    }

    public function test_customer_cannot_toggle_another_users_domain(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);
        $domain = $this->makeDomain($owner);

        $this->actingAs($intruder)
            ->post(route('customer.billing.domains.auto-renew', $domain))
            ->assertNotFound();

        $this->assertTrue($domain->fresh()->auto_renew);
    }

    public function test_billing_page_renders_auto_renew_controls(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $this->makeDomain($user);

        $this->actingAs($user)
            ->get(route('customer.billing.index'))
            ->assertOk()
            ->assertSee('Auto-renew')
            ->assertSee('Payment method');
    }
}
