<?php

namespace Tests\Unit;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\CloudflareRegistrar;
use App\Services\Registrar\RegistrarResponseParser;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareRegistrarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // checkAvailability caches for 5 minutes; a shared cache between tests
        // would make later expectations pass on a stale earlier result.
        Cache::flush();
    }

    private function registrar(): CloudflareRegistrar
    {
        config()->set('domain.cloudflare.api_token', 'cf_test_token');
        config()->set('domain.cloudflare.account_id', 'acct_123');
        config()->set('domain.cloudflare.endpoint', 'https://api.cloudflare.com/client/v4');
        config()->set('domain.cloudflare.registration_poll_attempts', 3);
        config()->set('domain.cloudflare.registration_poll_seconds', 0);

        return new CloudflareRegistrar(new RegistrarResponseParser);
    }

    /** @param array<string, mixed> $result */
    private function envelope(array $result): array
    {
        return ['success' => true, 'errors' => [], 'messages' => [], 'result' => $result];
    }

    public function test_available_domain_is_parsed_with_registry_price(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response($this->envelope([
            'domains' => [[
                'name' => 'acmecorp.dev',
                'registrable' => true,
                'tier' => 'standard',
                'pricing' => ['currency' => 'USD', 'registration_cost' => '10.11', 'renewal_cost' => '10.11'],
            ]],
        ]))]);

        $result = $this->registrar()->checkAvailability('acmecorp.dev');

        $this->assertTrue($result['available']);
        $this->assertFalse($result['premium']);
        $this->assertSame('10.11', $result['price']);
        $this->assertSame('USD', $result['currency']);
    }

    public function test_taken_domain_is_reported_unavailable(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response($this->envelope([
            'domains' => [['name' => 'taken.com', 'registrable' => false, 'reason' => 'unavailable']],
        ]))]);

        $this->assertFalse($this->registrar()->checkAvailability('taken.com')['available']);
    }

    public function test_premium_tier_is_flagged(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response($this->envelope([
            'domains' => [[
                'name' => 'gold.com',
                'registrable' => true,
                'tier' => 'premium',
                'pricing' => ['currency' => 'USD', 'registration_cost' => '4200.00'],
            ]],
        ]))]);

        $this->assertTrue($this->registrar()->checkAvailability('gold.com')['premium']);
    }

    /**
     * The distinction that matters most: a TLD outside the API beta is NOT an
     * unavailable domain. Reporting it as taken would refuse a perfectly
     * registrable .co.uk at checkout, so it must surface as fallback-eligible.
     */
    public function test_tld_outside_the_api_beta_is_fallback_eligible_not_unavailable(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response($this->envelope([
            'domains' => [[
                'name' => 'mybrand.co.uk',
                'registrable' => false,
                'reason' => 'extension_not_supported_via_api',
            ]],
        ]))]);

        try {
            $this->registrar()->checkAvailability('mybrand.co.uk');
            $this->fail('Expected a RegistrarException for an unsupported extension.');
        } catch (RegistrarException $e) {
            $this->assertTrue($e->fallbackEligible, 'Unsupported extensions must route to the fallback registrar.');
            $this->assertStringContainsString('extension_not_supported_via_api', $e->getMessage());
        }
    }

    public function test_registration_maps_the_registrant_contact(): void
    {
        Http::fake([
            'api.cloudflare.com/*/registrar/registrations/*' => Http::response($this->envelope([]), 404),
            'api.cloudflare.com/*/registrar/domain-check' => Http::response($this->envelope([
                'domains' => [[
                    'name' => 'acmecorp.dev', 'registrable' => true, 'tier' => 'standard',
                    'pricing' => ['currency' => 'USD', 'registration_cost' => '10.11'],
                ]],
            ])),
            'api.cloudflare.com/*/registrar/registrations' => Http::response($this->envelope([
                'domain_name' => 'acmecorp.dev',
                'state' => 'succeeded',
                'completed' => true,
                'context' => ['registration' => [
                    'domain_name' => 'acmecorp.dev',
                    'status' => 'active',
                    'expires_at' => '2027-10-27T10:00:00Z',
                ]],
            ]), 201),
        ]);

        $result = $this->registrar()->registerDomain([
            'domain' => 'acmecorp.dev',
            'contact' => [
                'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com',
                'phone' => '+44.2079460000', 'address_line_1' => '123 Main St', 'city' => 'London',
                'postcode' => 'EC1A 1BB', 'country' => 'gb',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('cloudflare', $result['registrar']);
        $this->assertSame('2027-10-27T10:00:00Z', $result['expiry_date']);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/registrar/registrations')) {
                return false;
            }

            $registrant = $request->data()['contacts']['registrant'] ?? [];

            return ($registrant['postal_info']['name'] ?? null) === 'Ada Lovelace'
                && ($registrant['postal_info']['address']['country_code'] ?? null) === 'GB'
                && ($registrant['phone'] ?? null) === '+44.2079460000';
        });
    }

    /**
     * Registrations are non-refundable and the step is retryable, so a retry
     * must adopt the existing registration rather than buy the domain twice.
     */
    public function test_registration_is_idempotent_when_cloudflare_already_holds_the_domain(): void
    {
        Http::fake([
            'api.cloudflare.com/*/registrar/registrations/acmecorp.dev' => Http::response($this->envelope([
                'domain_name' => 'acmecorp.dev',
                'status' => 'active',
                'expires_at' => '2027-10-27T10:00:00Z',
            ])),
        ]);

        $result = $this->registrar()->registerDomain(['domain' => 'acmecorp.dev']);

        $this->assertTrue($result['success']);
        $this->assertSame('2027-10-27T10:00:00Z', $result['expiry_date']);

        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/registrar/registrations') && $r->method() === 'POST');
    }

    public function test_asynchronous_registration_is_polled_to_completion(): void
    {
        $pending = $this->envelope([
            'domain_name' => 'slow.dev', 'state' => 'in_progress', 'completed' => false,
        ]);

        Http::fake([
            'api.cloudflare.com/*/registrar/registrations/slow.dev/registration-status' => Http::sequence()
                ->push($pending, 200)
                ->push($this->envelope([
                    'domain_name' => 'slow.dev', 'state' => 'succeeded', 'completed' => true,
                    'context' => ['registration' => ['domain_name' => 'slow.dev', 'status' => 'active', 'expires_at' => '2027-01-01T00:00:00Z']],
                ]), 200),
            'api.cloudflare.com/*/registrar/registrations/slow.dev' => Http::response($this->envelope([]), 404),
            'api.cloudflare.com/*/registrar/domain-check' => Http::response($this->envelope([
                'domains' => [['name' => 'slow.dev', 'registrable' => true, 'tier' => 'standard', 'pricing' => ['currency' => 'USD', 'registration_cost' => '10.11']]],
            ])),
            'api.cloudflare.com/*/registrar/registrations' => Http::response($pending, 202),
        ]);

        $result = $this->registrar()->registerDomain(['domain' => 'slow.dev']);

        $this->assertTrue($result['success']);
        $this->assertSame('2027-01-01T00:00:00Z', $result['expiry_date']);
    }

    public function test_failed_workflow_state_raises_a_registrar_exception(): void
    {
        Http::fake([
            'api.cloudflare.com/*/registrar/registrations/bad.dev' => Http::response($this->envelope([]), 404),
            'api.cloudflare.com/*/registrar/domain-check' => Http::response($this->envelope([
                'domains' => [['name' => 'bad.dev', 'registrable' => true, 'tier' => 'standard', 'pricing' => ['currency' => 'USD', 'registration_cost' => '10.11']]],
            ])),
            'api.cloudflare.com/*/registrar/registrations' => Http::response($this->envelope([
                'domain_name' => 'bad.dev', 'state' => 'failed', 'completed' => false,
                'context' => ['message' => 'payment method declined'],
            ]), 201),
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessageMatches("/state 'failed'/");

        $this->registrar()->registerDomain(['domain' => 'bad.dev']);
    }

    /**
     * Cloudflare Registrar domains are locked to Cloudflare nameservers. Asking
     * for those is a no-op; asking for anything else must fail loudly rather
     * than silently leave DNS pointing at the wrong host.
     */
    public function test_cloudflare_nameservers_are_a_verified_no_op(): void
    {
        $result = $this->registrar()->updateNameservers('example.com', ['kim.ns.cloudflare.com', 'walt.ns.cloudflare.com']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['no_op']);
        Http::assertNothingSent();
    }

    public function test_external_nameservers_are_rejected(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessageMatches('/must use Cloudflare nameservers/');

        $this->registrar()->updateNameservers('example.com', ['ns1.otherhost.com', 'ns2.otherhost.com']);
    }

    public function test_renewal_is_reported_as_unsupported_rather_than_silently_skipped(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessageMatches('/does not yet expose a renewal API/');

        $this->registrar()->renewDomain('example.com');
    }

    public function test_missing_credentials_produce_an_actionable_error(): void
    {
        config()->set('domain.cloudflare.api_token', null);
        config()->set('domain.cloudflare.account_id', 'acct_123');

        $registrar = new CloudflareRegistrar(new RegistrarResponseParser);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessageMatches('/CLOUDFLARE_REGISTRAR_API_TOKEN/');

        $registrar->checkAvailability('example.com');
    }

    public function test_pricing_is_read_from_a_probe_domain(): void
    {
        // The probe SLD is random, so the fake echoes back whichever name was
        // asked for — exactly as Cloudflare would.
        Http::fake(function (Request $request) {
            $probe = $request->data()['domains'][0] ?? '';

            return Http::response($this->envelope([
                'domains' => [[
                    'name' => $probe,
                    'registrable' => true,
                    'tier' => 'standard',
                    'pricing' => ['currency' => 'USD', 'registration_cost' => '10.11', 'renewal_cost' => '11.11'],
                ]],
            ]));
        });

        $pricing = $this->registrar()->getPricing('dev');

        $this->assertTrue($pricing['supported']);
        $this->assertSame('dev', $pricing['tld']);
        $this->assertSame('10.11', $pricing['registration']);
        $this->assertSame('11.11', $pricing['renewal']);
        $this->assertSame('USD', $pricing['currency']);

        // Probing must never register anything.
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/registrations'));
    }

    public function test_pricing_returns_unsupported_when_the_tld_is_outside_the_beta(): void
    {
        Http::fake(function (Request $request) {
            $probe = $request->data()['domains'][0] ?? '';

            return Http::response($this->envelope([
                'domains' => [['name' => $probe, 'registrable' => false, 'reason' => 'extension_not_supported_via_api']],
            ]));
        });

        // Price sync must degrade quietly: the TLD is recorded as skipped, and
        // the admin price book keeps whatever cost figures it already had.
        $pricing = $this->registrar()->getPricing('co.uk');

        $this->assertFalse($pricing['supported']);
        $this->assertNull($pricing['registration']);
    }
}
