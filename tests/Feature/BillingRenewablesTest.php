<?php

namespace Tests\Feature;

use App\Actions\Provisioning\ActivateOrderSubscriptions;
use App\Enums\RoleName;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingRenewablesTest extends TestCase
{
    use RefreshDatabase;

    private function makeDomain($user, string $name, string $status): Domain
    {
        return Domain::create([
            'user_id' => $user->id,
            'domain_name' => $name,
            'sld' => explode('.', $name)[0],
            'tld' => substr($name, strpos($name, '.') + 1),
            'registrar' => 'porkbun',
            'status' => $status,
            'auto_renew' => true,
        ]);
    }

    public function test_failed_domain_is_not_shown_in_billing_but_active_one_is(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $this->makeDomain($user, 'good-domain.com', 'active');
        $this->makeDomain($user, 'failed-domain.co.uk', 'failed');

        $this->actingAs($user)
            ->get(route('customer.billing.index'))
            ->assertOk()
            ->assertSee('good-domain.com')
            ->assertDontSee('failed-domain.co.uk');
    }

    public function test_subscriptions_are_created_only_when_hosting_is_active(): void
    {
        $this->seed([ProductSeeder::class, HostingPackageSeeder::class]);
        $package = HostingPackage::with('product')->firstOrFail();
        $user = $this->createUser();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-T1',
            'status' => 'provisioning',
            'payment_status' => 'succeeded',
            'currency' => 'GBP',
            'subtotal' => 9.99,
            'total' => 9.99,
        ]);
        $order->items()->create([
            'item_type' => 'hosting',
            'product_id' => $package->product_id,
            'name' => $package->name,
            'quantity' => 1,
            'unit_price' => 9.99,
            'total' => 9.99,
            'metadata' => ['billing_cycle' => 'monthly'],
        ]);

        // Hosting not active yet → no renewal subscription is created.
        app(ActivateOrderSubscriptions::class)->handle($order->fresh('items'));
        $this->assertSame(0, $user->subscriptions()->count());

        // Once the hosting account is active, the subscription appears.
        HostingAccount::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'hosting_package_id' => $package->id,
            'domain_name' => 'live.com',
            'whm_username' => 'pwlive',
            'status' => 'active',
        ]);

        app(ActivateOrderSubscriptions::class)->handle($order->fresh('items'));
        $this->assertSame(1, $user->subscriptions()->count());
        $this->assertNotNull($user->subscriptions()->first()->hosting_account_id);

        // Idempotent — running again does not duplicate.
        app(ActivateOrderSubscriptions::class)->handle($order->fresh('items'));
        $this->assertSame(1, $user->subscriptions()->count());
    }

    public function test_reconcile_cancels_orphaned_subscriptions(): void
    {
        $this->seed([ProductSeeder::class, HostingPackageSeeder::class]);
        $package = HostingPackage::firstOrFail();
        $user = $this->createUser();

        // A phantom subscription with no active hosting (e.g. a failed order).
        $sub = $user->subscriptions()->create([
            'product_id' => $package->product_id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'currency' => 'GBP',
            'amount' => 0,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
            'next_renewal_date' => now()->addMonth()->toDateString(),
        ]);

        $this->artisan('subscriptions:reconcile')->assertSuccessful();

        $this->assertSame('cancelled', $sub->fresh()->status->value);
    }
}
