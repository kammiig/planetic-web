<?php

namespace Tests\Feature;

use App\Jobs\Provisioning\CreateCloudflareDnsRecordsJob;
use App\Models\CloudflareZone;
use App\Models\DnsRecord;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\User;
use App\Services\Provisioning\ProvisioningLogger;
use App\Services\Provisioning\ProvisioningOrchestrator;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DNS A/SPF records must use the hosting account's WHM-assigned IP — never a
 * stale config fallback — and re-syncing must update wrong records in place.
 */
class DnsServerIpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
        config()->set('provisioning.dry_run', true);
        // A deliberately WRONG fallback — records must not use it.
        config()->set('whm.server_ip', '185.61.154.34');
    }

    private function orderWithHosting(string $assignedIp): Order
    {
        $user = User::factory()->create();
        $package = HostingPackage::first();

        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-50001',
            'status' => 'provisioning', 'payment_status' => 'succeeded', 'paid_at' => now(),
            'currency' => 'GBP', 'subtotal' => 20, 'discount_total' => 0, 'tax_total' => 0, 'total' => 20,
        ]);
        $order->items()->create([
            'item_type' => 'hosting', 'name' => 'Starter', 'domain_name' => 'sdafads-check3.com',
            'quantity' => 1, 'unit_price' => 20, 'total' => 20,
        ]);

        $domain = Domain::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'domain_name' => 'sdafads-check3.com',
            'sld' => 'sdafads-check3', 'tld' => 'com', 'registrar' => 'namesilo', 'status' => 'active',
        ]);
        $zone = CloudflareZone::create([
            'user_id' => $user->id, 'domain_id' => $domain->id, 'zone_id' => 'zone_x',
            'zone_name' => 'sdafads-check3.com', 'status' => 'active', 'name_servers' => ['a.ns', 'b.ns'],
        ]);
        $domain->update(['cloudflare_zone_id' => $zone->id]);

        // The hosting account carries the REAL assigned IP from WHM.
        HostingAccount::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'hosting_package_id' => $package->id,
            'domain_id' => $domain->id, 'domain_name' => 'sdafads-check3.com', 'whm_username' => 'pwsdaf18',
            'server_ip' => $assignedIp, 'status' => 'active', 'created_on_whm_at' => now(),
        ]);

        return $order->load('items');
    }

    public function test_dns_records_use_the_hosting_account_ip_not_the_config_fallback(): void
    {
        $order = $this->orderWithHosting('185.61.154.31');

        CreateCloudflareDnsRecordsJob::dispatchSync($order->id);

        // Root A record points at the assigned IP, not the wrong config IP.
        $this->assertDatabaseHas('dns_records', ['type' => 'A', 'name' => '@', 'content' => '185.61.154.31']);
        $this->assertDatabaseMissing('dns_records', ['content' => '185.61.154.34']);
        // SPF uses the same IP.
        $this->assertDatabaseHas('dns_records', ['type' => 'TXT', 'name' => '@', 'content' => 'v=spf1 +a +mx +ip4:185.61.154.31 ~all']);
    }

    public function test_resync_repairs_records_created_with_the_wrong_ip(): void
    {
        $order = $this->orderWithHosting('185.61.154.34'); // wrong on first run

        CreateCloudflareDnsRecordsJob::dispatchSync($order->id);
        $this->assertDatabaseHas('dns_records', ['type' => 'A', 'name' => '@', 'content' => '185.61.154.34']);

        // The account's real IP is corrected, then we re-sync.
        $order->hostingAccount()->update(['server_ip' => '185.61.154.31']);
        $this->artisan('dns:resync', ['order' => 'ORD-50001'])->assertSuccessful();

        // Records updated in place — no duplicate @ A record, now the right IP.
        $this->assertSame(1, DnsRecord::where('type', 'A')->where('name', '@')->count());
        $this->assertDatabaseHas('dns_records', ['type' => 'A', 'name' => '@', 'content' => '185.61.154.31']);
        $this->assertDatabaseMissing('dns_records', ['content' => '185.61.154.34']);
    }
}
