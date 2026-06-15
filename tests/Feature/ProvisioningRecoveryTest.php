<?php

namespace Tests\Feature;

use App\Actions\Checkout\CompletePaidOrder;
use App\Enums\HostingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\CloudflareZone;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteProject;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Proves the post-payment provisioning fix: services become visible immediately
 * and synchronously (no queue worker required), failures stay visible, the
 * recovery command unsticks orders, and dry-run works without external calls.
 */
class ProvisioningRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);

        // Synchronous provisioning (the production default) + integration config.
        config()->set('provisioning.sync', true);
        config()->set('domain.default_registrar', 'namesilo');
        config()->set('domain.namesilo.api_key', 'test-key');
        config()->set('cloudflare.api_token', 'cf-token');
        config()->set('cloudflare.account_id', 'cf-account');
        config()->set('whm.host', 'whm.test');
        config()->set('whm.username', 'root');
        config()->set('whm.token', 'whm-token');
        config()->set('whm.server_ip', '203.0.113.10');
        config()->set('whm.server_hostname', 'srv1.planeticweb.com');
        config()->set('whm.default_package', 'kwashqap_starter');
        config()->set('hosting.default_package', 'kwashqap_starter');
    }

    private function fakeIntegrations(bool $whmFails = false): void
    {
        Http::fake(function (Request $request) use ($whmFails) {
            $url = $request->url();
            $path = (string) parse_url($url, PHP_URL_PATH);

            if (str_contains($url, 'namesilo.com')) {
                return Http::response(['reply' => [
                    'code' => 300,
                    'detail' => 'success',
                    'domain' => 'example.com',
                    'order_id' => '55555',
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
                if ($whmFails) {
                    return Http::response(['metadata' => ['result' => 0, 'reason' => 'Insufficient disk space on server', 'command' => 'createacct']]);
                }

                return Http::response([
                    'metadata' => ['result' => 1, 'reason' => 'Account Creation Ok', 'command' => 'createacct'],
                    'data' => ['ip' => '203.0.113.10', 'nameserver' => 'ns1.planeticweb.com', 'package' => 'kwashqap_starter'],
                ]);
            }

            return Http::response([], 200);
        });
    }

    private function customer(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'company_name' => 'Example Ltd',
            'billing_address_line_1' => '1 High Street',
            'billing_city' => 'London',
            'billing_postcode' => 'EC1A 1AA',
            'billing_country' => 'GB',
            'phone' => '+447000000000',
        ]);
    }

    private function pendingWebsiteOrder(?User $user = null): Order
    {
        $user ??= $this->customer();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-10007',
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Pending->value,
            'currency' => 'GBP',
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 200,
            'stripe_payment_intent_id' => null,
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

    public function test_completing_a_paid_order_provisions_synchronously_and_shows_services(): void
    {
        $this->fakeIntegrations();
        $order = $this->pendingWebsiteOrder();

        // No queue worker, no manual job dispatch — just confirming payment.
        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_sync_123']);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Succeeded, $order->payment_status);

        // Every tab now has a record.
        $this->assertDatabaseHas('domains', ['domain_name' => 'example.com', 'status' => 'active', 'order_id' => $order->id]);
        $this->assertDatabaseHas('hosting_accounts', ['order_id' => $order->id, 'status' => 'active', 'server_ip' => '203.0.113.10']);
        $this->assertDatabaseHas('website_projects', ['order_id' => $order->id]);
        $this->assertDatabaseHas('cloudflare_zones', ['zone_id' => 'zone_abc123']);
        $this->assertSame(9, \App\Models\DnsRecord::count()); // A @,www,mail,webmail + 3 MX + SPF + DMARC
    }

    public function test_hosting_failure_keeps_a_visible_failed_record(): void
    {
        $this->fakeIntegrations(whmFails: true);
        $order = $this->pendingWebsiteOrder();

        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_fail_123']);

        $order->refresh();
        // Domain still registered; hosting visible but marked failed; order flagged.
        $this->assertSame(OrderStatus::ManualReview, $order->status);
        $this->assertDatabaseHas('domains', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('hosting_accounts', ['order_id' => $order->id, 'status' => HostingStatus::Failed->value]);
        $this->assertSame(1, HostingAccount::where('order_id', $order->id)->count());
    }

    public function test_orders_provision_command_unsticks_an_unpaid_order(): void
    {
        $this->fakeIntegrations();
        $order = $this->pendingWebsiteOrder();

        // Simulates an admin confirming the charge in Stripe, then forcing provisioning.
        $this->artisan('orders:provision', ['order' => 'ORD-10007', '--mark-paid' => true])
            ->assertSuccessful();

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertDatabaseHas('domains', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('hosting_accounts', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('website_projects', ['order_id' => $order->id]);
    }

    public function test_running_provisioning_twice_does_not_duplicate_records(): void
    {
        $this->fakeIntegrations();
        $order = $this->pendingWebsiteOrder();

        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_dup_123']);
        // A duplicate webhook / refresh would call this again — must be a no-op.
        app(CompletePaidOrder::class)->handle($order->fresh('items'), ['payment_intent' => 'pi_dup_123']);
        // And an explicit re-provision must also not duplicate.
        $this->artisan('orders:provision', ['order' => 'ORD-10007'])->assertSuccessful();

        $this->assertSame(1, Domain::where('order_id', $order->id)->count());
        $this->assertSame(1, HostingAccount::where('order_id', $order->id)->count());
        $this->assertSame(1, WebsiteProject::where('order_id', $order->id)->count());
        $this->assertSame(9, \App\Models\DnsRecord::count()); // A @,www,mail,webmail + 3 MX + SPF + DMARC
    }

    public function test_domain_only_order_skips_hosting_and_cloudflare(): void
    {
        $this->fakeIntegrations();
        $user = $this->customer();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-10009',
            'status' => OrderStatus::Provisioning->value,
            'payment_status' => PaymentStatus::Succeeded->value,
            'currency' => 'GBP',
            'subtotal' => 12.99,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 12.99,
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'item_type' => 'domain_registration',
            'name' => 'example.com registration',
            'domain_name' => 'example.com',
            'quantity' => 1,
            'unit_price' => 12.99,
            'total' => 12.99,
        ]);

        ProvisionOrderJob::dispatchSync($order->id);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertDatabaseHas('domains', ['domain_name' => 'example.com', 'status' => 'active']);
        $this->assertSame(0, HostingAccount::count());
        $this->assertSame(0, CloudflareZone::count());
    }

    public function test_dry_run_activates_records_without_calling_external_apis(): void
    {
        config()->set('provisioning.dry_run', true);
        Http::fake(); // any outbound call would be recorded

        $order = $this->pendingWebsiteOrder();
        app(CompletePaidOrder::class)->handle($order, ['payment_intent' => 'pi_dry_123']);

        $order->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertDatabaseHas('domains', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('hosting_accounts', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('cloudflare_zones', ['zone_name' => 'example.com']);

        // The whole point of dry-run: no registrar / WHM / Cloudflare traffic.
        Http::assertNothingSent();
    }
}
