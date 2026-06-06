<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);

        config()->set('domain.default_registrar', 'namesilo');
        config()->set('domain.namesilo.api_key', 'test-key');
        config()->set('cloudflare.api_token', 'cf-token');
        config()->set('cloudflare.account_id', 'cf-account');
        config()->set('whm.host', 'whm.test');
        config()->set('whm.username', 'root');
        config()->set('whm.token', 'whm-token');
        config()->set('whm.server_ip', '203.0.113.10');
        config()->set('whm.default_package', 'planetic_starter');
        config()->set('hosting.default_package', 'planetic_starter');
    }

    private function fakeIntegrations(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();
            $path = (string) parse_url($url, PHP_URL_PATH);

            if (str_contains($url, 'namesilo.com')) {
                return Http::response(['reply' => [
                    'code' => 300,
                    'detail' => 'success',
                    'domain' => 'example.com',
                    'order_id' => '55555',
                    'order_amount' => '12.99',
                    'expires' => now()->addYear()->format('Y-m-d'),
                ]]);
            }

            if (str_contains($url, 'cloudflare.com')) {
                if (str_contains($path, '/dns_records')) {
                    return Http::response(['success' => true, 'result' => ['id' => 'rec_'.uniqid()]]);
                }
                if (str_contains($path, '/settings/')) {
                    return Http::response(['success' => true, 'result' => ['id' => 'setting']]);
                }

                return Http::response(['success' => true, 'result' => [
                    'id' => 'zone_abc123',
                    'name' => 'example.com',
                    'status' => 'pending',
                    'name_servers' => ['dana.ns.cloudflare.com', 'rob.ns.cloudflare.com'],
                ]]);
            }

            if (str_contains($url, 'whm.test')) {
                return Http::response([
                    'metadata' => ['result' => 1, 'reason' => 'Account Creation Ok', 'command' => 'createacct'],
                    'data' => ['ip' => '203.0.113.10', 'nameserver' => 'ns1.planeticweb.com', 'package' => 'planetic_starter'],
                ]);
            }

            return Http::response([], 200);
        });
    }

    private function paidWebsiteOrder(): Order
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'phone' => '+447000000000',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-10001',
            'status' => OrderStatus::Provisioning->value,
            'payment_status' => PaymentStatus::Succeeded->value,
            'currency' => 'GBP',
            'subtotal' => 200,
            'total' => 200,
            'paid_at' => now(),
        ]);

        $order->items()->create([
            'item_type' => 'website_package',
            'name' => 'Complete Bespoke Website',
            'domain_name' => 'example.com',
            'quantity' => 1,
            'unit_price' => 200,
            'total' => 200,
        ]);

        return $order->load('items');
    }

    public function test_full_provisioning_chain_completes_the_order(): void
    {
        $this->fakeIntegrations();
        $order = $this->paidWebsiteOrder();

        ProvisionOrderJob::dispatch($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);

        // Domain registered and pointed at Cloudflare.
        $this->assertDatabaseHas('domains', ['domain_name' => 'example.com', 'status' => 'active']);
        $domain = $order->domain;
        $this->assertNotNull($domain->cloudflare_zone_id);
        $this->assertSame('55555', $domain->registrar_order_id);

        // Cloudflare zone + DNS records created.
        $this->assertDatabaseHas('cloudflare_zones', ['zone_id' => 'zone_abc123', 'domain_id' => $domain->id]);
        $this->assertDatabaseHas('dns_records', ['domain_id' => $domain->id, 'type' => 'A', 'name' => '@', 'proxied' => true]);
        $this->assertDatabaseHas('dns_records', ['domain_id' => $domain->id, 'name' => 'mail', 'proxied' => false]);
        $this->assertDatabaseHas('dns_records', ['domain_id' => $domain->id, 'type' => 'MX', 'proxied' => false]);

        // Hosting account created.
        $this->assertDatabaseHas('hosting_accounts', [
            'order_id' => $order->id,
            'domain_name' => 'example.com',
            'server_ip' => '203.0.113.10',
            'status' => 'active',
        ]);

        // All ledger steps completed.
        $this->assertSame(0, $order->provisioningJobs()->where('status', '!=', 'completed')->count());

        // Customer notified.
        $this->assertDatabaseHas('notification_logs', ['type' => 'provisioning_completed', 'status' => 'sent']);
    }

    public function test_provisioning_is_idempotent_on_retry(): void
    {
        $this->fakeIntegrations();
        $order = $this->paidWebsiteOrder();

        ProvisionOrderJob::dispatch($order->id);
        // Re-run the whole pipeline — must not duplicate external resources.
        ProvisionOrderJob::dispatch($order->id);

        $this->assertSame(1, \App\Models\Domain::where('domain_name', 'example.com')->count());
        $this->assertSame(1, \App\Models\CloudflareZone::where('domain_id', $order->domain->id)->count());
        $this->assertSame(1, \App\Models\HostingAccount::where('order_id', $order->id)->count());
        // 8 default DNS records, created exactly once.
        $this->assertSame(8, \App\Models\DnsRecord::where('domain_id', $order->domain->id)->count());
    }

    public function test_domain_registration_failure_routes_to_manual_review(): void
    {
        // NameSilo returns an error code → registration fails.
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'namesilo.com')) {
                return Http::response(['reply' => ['code' => 261, 'detail' => 'domain unavailable']]);
            }

            return Http::response(['success' => true, 'result' => []]);
        });

        $order = $this->paidWebsiteOrder();
        ProvisionOrderJob::dispatch($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::ManualReview, $order->status);
        $this->assertDatabaseHas('domains', ['domain_name' => 'example.com', 'status' => 'failed']);
        $this->assertDatabaseHas('provisioning_jobs', ['order_id' => $order->id, 'job_type' => 'register_domain', 'status' => 'manual_review']);
        // The hosting step must NOT have run — the chain halted.
        $this->assertDatabaseMissing('hosting_accounts', ['order_id' => $order->id]);
    }
}
