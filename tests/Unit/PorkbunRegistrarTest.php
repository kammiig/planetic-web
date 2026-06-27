<?php

namespace Tests\Unit;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarResponseParser;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PorkbunRegistrarTest extends TestCase
{
    private function registrar(): PorkbunRegistrar
    {
        config()->set('domain.porkbun.api_key', 'pk_test');
        config()->set('domain.porkbun.secret_key', 'sk_test');
        config()->set('domain.porkbun.endpoint', 'https://api.porkbun.com/api/json/v3');

        return new PorkbunRegistrar(new RegistrarResponseParser());
    }

    public function test_ping_confirms_the_connection(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'SUCCESS', 'yourIp' => '203.0.113.7'])]);

        $this->assertSame('203.0.113.7', $this->registrar()->ping()['yourIp']);
    }

    public function test_available_domain_is_parsed_with_price(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response([
            'status' => 'SUCCESS',
            'response' => ['avail' => 'yes', 'price' => '9.68', 'premium' => 'no'],
        ])]);

        $result = $this->registrar()->checkAvailability('example.com');

        $this->assertTrue($result['available']);
        $this->assertSame('9.68', $result['price']);
        $this->assertFalse($result['premium']);
    }

    public function test_unavailable_domain_is_parsed(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'SUCCESS', 'response' => ['avail' => 'no']])]);

        $this->assertFalse($this->registrar()->checkAvailability('taken.com')['available']);
    }

    public function test_pricing_is_returned_for_a_tld(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response([
            'status' => 'SUCCESS',
            'pricing' => ['com' => ['registration' => '9.68', 'renewal' => '11.06', 'transfer' => '9.68']],
        ])]);

        // Accepts a full domain and reduces it to the registrable TLD.
        $pricing = $this->registrar()->getPricing('example.com');

        $this->assertTrue($pricing['supported']);
        $this->assertSame('com', $pricing['tld']);
        $this->assertSame('9.68', $pricing['registration']);
        $this->assertSame('11.06', $pricing['renewal']);
    }

    public function test_registration_returns_normalised_payload(): void
    {
        $this->fakeRegistrationFlow();

        $result = $this->registrar()->registerDomain(['domain' => 'example.com', 'whois_privacy' => true]);

        $this->assertTrue($result['success']);
        $this->assertSame('778899', $result['registrar_order_id']);
        $this->assertSame('example.com', $result['registrar_domain_id']);
        $this->assertSame('2027-06-23 00:00:00', $result['expiry_date']);
    }

    public function test_registration_confirms_cost_in_pennies_and_agreement(): void
    {
        $this->fakeRegistrationFlow();

        $this->registrar()->registerDomain(['domain' => 'example.com', 'whois_privacy' => true]);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/domain/create/')) {
                return false;
            }

            // £/$9.68 → 968 pennies, plus the required agreement + privacy flags.
            return $request['cost'] === 968
                && $request['agreeToTerms'] === 'yes'
                && $request['whoisPrivacy'] === 'yes';
        });
    }

    public function test_dry_run_registration_does_not_create_the_domain(): void
    {
        Http::fake(['api.porkbun.com/*' => function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_contains($path, '/domain/checkDomain/')) {
                return Http::response(['status' => 'SUCCESS', 'response' => ['avail' => 'yes', 'price' => '9.68']]);
            }

            return Http::response(['status' => 'SUCCESS', 'dryRun' => true, 'wouldSucceed' => true, 'cost' => 968]);
        }]);

        $result = $this->registrar()->registerDomain(['domain' => 'example.com', 'dry_run' => true]);

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/domain/create/') && $r['dryRun'] === true);
    }

    public function test_registration_of_unavailable_domain_throws(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'SUCCESS', 'response' => ['avail' => 'no']])]);

        $this->expectException(RegistrarException::class);
        $this->registrar()->registerDomain(['domain' => 'taken.com']);
    }

    public function test_registration_http_400_surfaces_the_real_porkbun_reason_and_hint(): void
    {
        Http::fake([
            'api.porkbun.com/api/json/v3/domain/checkDomain/*' => Http::response([
                'status' => 'SUCCESS',
                'response' => ['avail' => 'yes', 'price' => '8.99', 'premium' => 'no'],
            ]),
            'api.porkbun.com/api/json/v3/domain/create/*' => Http::response([
                'status' => 'ERROR',
                'message' => 'Registrant contact phone number is invalid',
            ], 400),
        ]);

        try {
            $this->registrar()->registerDomain(['domain' => 'goods-group.co.uk', 'whois_privacy' => true]);
            $this->fail('Expected a RegistrarException for the HTTP 400.');
        } catch (RegistrarException $e) {
            // The exact Porkbun reason is surfaced (not just "HTTP 400")...
            $this->assertStringContainsString('Registrant contact phone number is invalid', $e->getMessage());
            // ...along with an admin hint about the registrant/contact data.
            $this->assertStringContainsString('registrant/contact', $e->getMessage());
        }
    }

    public function test_registration_400_with_empty_body_is_never_a_bare_http_400(): void
    {
        Http::fake([
            'api.porkbun.com/api/json/v3/domain/checkDomain/*' => Http::response([
                'status' => 'SUCCESS',
                'response' => ['avail' => 'yes', 'price' => '8.99', 'premium' => 'no'],
            ]),
            'api.porkbun.com/api/json/v3/domain/create/*' => Http::response('', 400),
        ]);

        try {
            $this->registrar()->registerDomain(['domain' => 'goods-group.co.uk', 'whois_privacy' => true]);
            $this->fail('Expected a RegistrarException.');
        } catch (RegistrarException $e) {
            $this->assertStringContainsString('HTTP 400', $e->getMessage());
            $this->assertStringContainsString('no response body', $e->getMessage());
            // The exception carries structured context for the provisioning monitor.
            $this->assertIsArray($e->context);
        }
    }

    public function test_debug_register_captures_the_real_reason_without_charging(): void
    {
        Http::fake([
            'api.porkbun.com/api/json/v3/domain/checkDomain/*' => Http::response([
                'status' => 'SUCCESS',
                'response' => ['avail' => 'yes', 'price' => '8.99', 'premium' => 'no'],
            ]),
            'api.porkbun.com/api/json/v3/domain/create/*' => Http::response([
                'status' => 'SUCCESS',
                'wouldSucceed' => 'no',
                'message' => 'Registrant address is required for this TLD',
            ]),
            'api.porkbun.com/api/json/v3/domain/getRegistrationRequirements/*' => Http::response([
                'status' => 'SUCCESS',
                'requirements' => ['registrantName', 'registrantAddress'],
            ]),
        ]);

        $report = $this->registrar()->debugRegister('goods-group.co.uk');

        $this->assertSame('co.uk', $report['tld']);
        $this->assertSame(899, $report['cost_pennies_sent']);
        $this->assertStringContainsString('Registrant address is required', $report['reason']);
        $this->assertArrayHasKey('registration_requirements', $report);
    }

    public function test_registration_waits_out_a_rate_limit_then_uses_the_exact_quote(): void
    {
        // Disable the real sleep so the test runs instantly.
        config()->set('domain.rate_limit_retry_seconds', 0);

        Http::fake([
            // First checkDomain is rate-limited; the retry succeeds with the quote.
            'api.porkbun.com/api/json/v3/domain/checkDomain/*' => Http::sequence()
                ->push(['status' => 'ERROR', 'code' => 'RATE_LIMIT_EXCEEDED', 'message' => '1 out of 1 checks within 10 seconds used.', 'ttlRemaining' => 1], 400)
                ->push(['status' => 'SUCCESS', 'response' => ['avail' => 'yes', 'price' => '5.66', 'premium' => 'no']], 200),
            'api.porkbun.com/api/json/v3/domain/listAll*' => Http::response(['status' => 'SUCCESS', 'domains' => []]),
            'api.porkbun.com/api/json/v3/domain/create/*' => Http::response(['status' => 'SUCCESS', 'orderId' => '999']),
        ]);

        $result = $this->registrar()->registerDomain(['domain' => 'goods-group.co.uk', 'whois_privacy' => true]);

        $this->assertTrue($result['success']);

        // The create call used the EXACT checkDomain quote (566 US cents), not a
        // different price source — so Porkbun never rejects on a cost mismatch.
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/domain/create/goods-group.co.uk')
            && (int) ($r['cost'] ?? -1) === 566);
    }

    public function test_update_nameservers_posts_the_ns_array(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'SUCCESS'])]);

        $result = $this->registrar()->updateNameservers('example.com', ['dana.ns.cloudflare.com', 'rob.ns.cloudflare.com']);

        $this->assertTrue($result['success']);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/domain/updateNs/example.com')
            && $r['ns'] === ['dana.ns.cloudflare.com', 'rob.ns.cloudflare.com']);
    }

    public function test_get_domain_info_reads_status_expiry_and_nameservers(): void
    {
        Http::fake(['api.porkbun.com/*' => function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_contains($path, '/domain/getNs/')) {
                return Http::response(['status' => 'SUCCESS', 'ns' => ['dana.ns.cloudflare.com']]);
            }

            return Http::response(['status' => 'SUCCESS', 'domains' => [
                ['domain' => 'example.com', 'status' => 'ACTIVE', 'expireDate' => '2027-06-23 00:00:00'],
            ]]);
        }]);

        $info = $this->registrar()->getDomainInfo('example.com');

        $this->assertSame('ACTIVE', $info['status']);
        $this->assertSame('2027-06-23 00:00:00', $info['expiry_date']);
        $this->assertContains('dana.ns.cloudflare.com', $info['nameservers']);
    }

    public function test_error_status_throws_with_hint_and_does_not_leak_the_secret(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'ERROR', 'message' => 'Invalid API key.'])]);

        try {
            $this->registrar()->checkAvailability('example.com');
            $this->fail('Expected a RegistrarException.');
        } catch (RegistrarException $e) {
            $this->assertStringContainsString('Invalid API key', $e->getMessage());
            // The admin hint names the env vars, but the secret VALUE must never appear.
            $this->assertStringNotContainsString('sk_test', $e->getMessage());
            $this->assertStringNotContainsString('pk_test', $e->getMessage());
        }
    }

    public function test_missing_credentials_throws_before_any_request(): void
    {
        config()->set('domain.porkbun.api_key', null);
        config()->set('domain.porkbun.secret_key', null);

        $this->expectException(RegistrarException::class);
        (new PorkbunRegistrar(new RegistrarResponseParser()))->checkAvailability('example.com');
    }

    /** Fakes the check → create → listAll sequence a real registration performs. */
    private function fakeRegistrationFlow(): void
    {
        Http::fake(['api.porkbun.com/*' => function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_contains($path, '/domain/checkDomain/')) {
                return Http::response(['status' => 'SUCCESS', 'response' => ['avail' => 'yes', 'price' => '9.68']]);
            }
            if (str_contains($path, '/domain/create/')) {
                return Http::response(['status' => 'SUCCESS', 'orderId' => 778899, 'cost' => 968]);
            }
            if (str_contains($path, '/domain/listAll')) {
                return Http::response(['status' => 'SUCCESS', 'domains' => [
                    ['domain' => 'example.com', 'status' => 'ACTIVE', 'expireDate' => '2027-06-23 00:00:00'],
                ]]);
            }

            return Http::response(['status' => 'SUCCESS']);
        }]);
    }
}
