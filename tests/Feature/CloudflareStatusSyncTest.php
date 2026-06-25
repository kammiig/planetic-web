<?php

namespace Tests\Feature;

use App\Models\CloudflareZone;
use App\Models\Domain;
use App\Services\DNS\CloudflareStatusSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cloudflare.api_token', 'cf_test');
        config()->set('cloudflare.api_base', 'https://api.cloudflare.com/client/v4');
    }

    private function makeZone(string $zoneId, string $name): CloudflareZone
    {
        $user = $this->createUser();
        $domain = Domain::create([
            'user_id' => $user->id,
            'domain_name' => $name,
            'sld' => explode('.', $name)[0],
            'tld' => substr($name, strpos($name, '.') + 1),
            'registrar' => 'porkbun',
            'status' => 'active',
            'nameservers' => ['ns1.porkbun.com'],
        ]);

        return CloudflareZone::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'zone_id' => $zoneId,
            'zone_name' => $name,
            'status' => 'pending',
        ]);
    }

    public function test_pending_zone_becomes_active_with_ssl_after_cloudflare_verifies(): void
    {
        $zone = $this->makeZone('zone123', 'planeticsolution.xyz');

        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/zone123/ssl/verification*' => Http::response([
                'success' => true,
                'result' => [['certificate_status' => 'active']],
            ]),
            'https://api.cloudflare.com/client/v4/zones/zone123*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'zone123',
                    'name' => 'planeticsolution.xyz',
                    'status' => 'active',
                    'name_servers' => ['chad.ns.cloudflare.com', 'virginia.ns.cloudflare.com'],
                ],
            ]),
        ]);

        $becameActive = app(CloudflareStatusSync::class)->sync($zone);

        $this->assertTrue($becameActive);

        $zone->refresh();
        $this->assertSame('active', $zone->status);
        $this->assertSame('active', $zone->ssl_status);
        $this->assertTrue($zone->sslIsActive());
        $this->assertSame('Active', $zone->dnsStatusLabel());
        $this->assertStringContainsString('Active', $zone->sslStatusLabel());

        // The registrar nameservers we display are updated to Cloudflare's.
        $this->assertSame(
            ['chad.ns.cloudflare.com', 'virginia.ns.cloudflare.com'],
            $zone->domain->fresh()->nameservers,
        );
    }

    public function test_zone_still_pending_keeps_waiting_status(): void
    {
        $zone = $this->makeZone('zone456', 'not-yet.com');

        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/zone456*' => Http::response([
                'success' => true,
                'result' => ['id' => 'zone456', 'name' => 'not-yet.com', 'status' => 'pending', 'name_servers' => []],
            ]),
        ]);

        $this->assertFalse(app(CloudflareStatusSync::class)->sync($zone));

        $zone->refresh();
        $this->assertSame('pending', $zone->status);
        $this->assertFalse($zone->sslIsActive());
        $this->assertSame('Waiting for nameserver verification', $zone->dnsStatusLabel());
        $this->assertSame('Waiting for nameserver verification', $zone->sslStatusLabel());
    }
}
