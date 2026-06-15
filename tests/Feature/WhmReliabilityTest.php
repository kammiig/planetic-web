<?php

namespace Tests\Feature;

use App\Enums\HostingStatus;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\User;
use App\Services\Hosting\WhmService;
use App\Support\Secrets;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WHM provisioning reliability: a transport timeout is reconciled against the
 * live account list (no false failure, no duplicate), and no cPanel password
 * ever reaches an error message, log or email.
 */
class WhmReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);

        config()->set('whm.host', 'whm.test');
        config()->set('whm.port', 2087);
        config()->set('whm.username', 'root');
        config()->set('whm.token', 'whm-token');
        config()->set('whm.server_ip', '185.61.154.32');
    }

    public function test_timeout_then_account_exists_is_adopted_not_failed(): void
    {
        // createacct times out, but listaccts shows the account WAS created.
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'createacct')) {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'cURL error 28: Operation timed out after 30002 milliseconds'
                );
            }

            // listaccts reconciliation.
            return Http::response(['data' => ['acct' => [[
                'user' => 'pwexample9', 'domain' => 'example.com', 'ip' => '185.61.154.32', 'plan' => 'kwashqap_Pro',
            ]]]]);
        });

        $result = app(WhmService::class)->createAccount([
            'username' => 'pwexample9',
            'domain' => 'example.com',
            'contactemail' => 'a@b.com',
            'plan' => 'kwashqap_Pro',
            'password' => 'S3cretCpanelPass!',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('185.61.154.32', $result['ip']);
    }

    public function test_timeout_with_no_existing_account_still_fails_without_leaking_the_password(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'createacct')) {
                // The realistic Guzzle message includes the request URL.
                throw new \Illuminate\Http\Client\ConnectionException(
                    'cURL error 28: Operation timed out for https://whm.test:2087/json-api/createacct?password=S3cretCpanelPass%21&plan=kwashqap_Pro'
                );
            }

            return Http::response(['data' => ['acct' => []]]); // no account found
        });

        try {
            app(WhmService::class)->createAccount([
                'username' => 'pwexample9', 'domain' => 'example.com',
                'contactemail' => 'a@b.com', 'plan' => 'kwashqap_Pro', 'password' => 'S3cretCpanelPass!',
            ]);
            $this->fail('Expected a WhmException.');
        } catch (\App\Exceptions\WhmException $e) {
            $this->assertStringNotContainsString('S3cretCpanelPass', $e->getMessage());
            $this->assertStringContainsString('[redacted]', $e->getMessage());
        }
    }

    public function test_secrets_helper_masks_passwords_and_tokens_in_urls(): void
    {
        $url = 'https://whm.test:2087/json-api/createacct?username=pw&domain=x.com&password=Sup3r%21&api.token=abc123&plan=kwashqap_Pro';
        $clean = Secrets::redact($url);

        $this->assertStringNotContainsString('Sup3r', $clean);
        $this->assertStringNotContainsString('abc123', $clean);
        $this->assertStringContainsString('plan=kwashqap_Pro', $clean); // non-secret kept
        $this->assertStringContainsString('password=[redacted]', $clean);
    }

    public function test_create_whm_job_adopts_an_existing_account_on_retry(): void
    {
        // listaccts already shows the account → the job adopts it, no createacct.
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'createacct')) {
                $this->fail('createacct must not be called when the account already exists.');
            }

            return Http::response(['data' => ['acct' => [[
                'user' => 'pwexisting', 'domain' => 'taken.com', 'ip' => '185.61.154.32', 'plan' => 'kwashqap_Pro',
            ]]]]);
        });

        $user = User::factory()->create(['email_verified_at' => now()]);
        $package = HostingPackage::where('whm_package_name', 'kwashqap_Pro')->firstOrFail();

        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-30001',
            'status' => 'provisioning', 'payment_status' => 'succeeded', 'paid_at' => now(),
            'currency' => 'GBP', 'subtotal' => 20, 'discount_total' => 0, 'tax_total' => 0, 'total' => 20,
        ]);
        $order->items()->create([
            'product_id' => $package->product_id, 'item_type' => 'hosting',
            'name' => 'Pro', 'domain_name' => 'taken.com', 'quantity' => 1, 'unit_price' => 20, 'total' => 20,
            'metadata' => ['domain_source' => 'existing'],
        ]);
        $account = HostingAccount::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'hosting_package_id' => $package->id,
            'domain_name' => 'taken.com', 'whm_username' => 'pwexisting', 'status' => HostingStatus::Pending->value,
        ]);

        app(\App\Jobs\Provisioning\CreateWhmHostingAccountJob::class, ['orderId' => $order->id])
            ->handle(app(\App\Services\Provisioning\ProvisioningOrchestrator::class), app(\App\Services\Provisioning\ProvisioningLogger::class));

        $this->assertSame(HostingStatus::Active, $account->fresh()->status);
    }
}
