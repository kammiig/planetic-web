<?php

namespace Tests\Unit;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\FallbackRegistrar;
use App\Services\Registrar\RegistrarInterface;
use Tests\TestCase;

class FallbackRegistrarTest extends TestCase
{
    /**
     * A stub registrar that either answers or throws a prepared exception,
     * recording whether it was called.
     */
    private function stub(string $name, ?RegistrarException $throws = null): RegistrarInterface
    {
        return new class($name, $throws) implements RegistrarInterface
        {
            public bool $called = false;

            public function __construct(
                private readonly string $name,
                private readonly ?RegistrarException $throws,
            ) {}

            private function answer(array $payload): array
            {
                $this->called = true;

                if ($this->throws) {
                    throw $this->throws;
                }

                return $payload + ['registrar' => $this->name];
            }

            public function checkAvailability(string $domain): array
            {
                return $this->answer(['domain' => $domain, 'available' => true, 'premium' => false, 'price' => '9.99', 'currency' => 'USD']);
            }

            public function getPricing(string $tld): array
            {
                return $this->answer(['tld' => $tld, 'registration' => '9.99', 'renewal' => null, 'transfer' => null, 'currency' => 'USD', 'supported' => true]);
            }

            public function registerDomain(array $data): array
            {
                return $this->answer(['domain' => $data['domain'], 'success' => true, 'registrar_domain_id' => $data['domain'], 'registrar_order_id' => null, 'order_amount' => null, 'expiry_date' => null]);
            }

            public function renewDomain(string $domain, int $years = 1): array
            {
                return $this->answer(['domain' => $domain, 'success' => true, 'order_amount' => null, 'expiry_date' => null]);
            }

            public function getDomainInfo(string $domain): array
            {
                return $this->answer(['domain' => $domain, 'status' => 'active', 'expiry_date' => null, 'nameservers' => []]);
            }

            public function updateNameservers(string $domain, array $nameservers): array
            {
                return $this->answer(['domain' => $domain, 'success' => true]);
            }

            public function name(): string
            {
                return $this->name;
            }
        };
    }

    private function unsupportedTld(): RegistrarException
    {
        return new RegistrarException(
            'Cloudflare cannot register this TLD via its API.',
            registrar: 'cloudflare',
            fallbackEligible: true,
        );
    }

    public function test_the_primary_answers_when_it_can(): void
    {
        $primary = $this->stub('cloudflare');
        $fallback = $this->stub('porkbun');

        $result = (new FallbackRegistrar($primary, $fallback))->registerDomain(['domain' => 'example.com']);

        $this->assertSame('cloudflare', $result['registrar']);
        $this->assertFalse($fallback->called, 'The fallback must not be touched when the primary succeeds.');
    }

    /**
     * The reason this class exists: a .co.uk that Cloudflare's API beta cannot
     * take must still register — for a UK business it is the primary TLD, and a
     * paid order cannot be allowed to fail over a provider gap.
     */
    public function test_an_unsupported_tld_routes_to_the_fallback(): void
    {
        $primary = $this->stub('cloudflare', $this->unsupportedTld());
        $fallback = $this->stub('porkbun');

        $result = (new FallbackRegistrar($primary, $fallback))->registerDomain(['domain' => 'example.co.uk']);

        $this->assertTrue($fallback->called);
        $this->assertSame('porkbun', $result['registrar'], 'The domain record must name the registrar that actually holds it.');
    }

    /**
     * The fallback is never a way to paper over a real failure, and must never
     * let a second registrar be charged for a domain the first may already have
     * bought.
     */
    public function test_a_genuine_failure_is_not_retried_on_the_fallback(): void
    {
        $primary = $this->stub('cloudflare', new RegistrarException(
            'That domain is taken.',
            registrar: 'cloudflare',
        ));
        $fallback = $this->stub('porkbun');

        try {
            (new FallbackRegistrar($primary, $fallback))->registerDomain(['domain' => 'taken.com']);
            $this->fail('Expected the primary failure to propagate.');
        } catch (RegistrarException $e) {
            $this->assertSame('That domain is taken.', $e->getMessage());
        }

        $this->assertFalse($fallback->called, 'A real error must fail fast, never fall back.');
    }

    public function test_without_a_fallback_the_primary_error_propagates(): void
    {
        $primary = $this->stub('cloudflare', $this->unsupportedTld());

        $this->expectException(RegistrarException::class);

        (new FallbackRegistrar($primary, null))->registerDomain(['domain' => 'example.co.uk']);
    }

    public function test_availability_checks_also_fall_back(): void
    {
        $primary = $this->stub('cloudflare', $this->unsupportedTld());
        $fallback = $this->stub('porkbun');

        // Availability must fall back too, or a .co.uk would look unbuyable in
        // search long before anyone reached checkout.
        $result = (new FallbackRegistrar($primary, $fallback))->checkAvailability('example.co.uk');

        $this->assertTrue($result['available']);
        $this->assertTrue($fallback->called);
    }

    public function test_name_reports_the_configured_primary(): void
    {
        $registrar = new FallbackRegistrar($this->stub('cloudflare'), $this->stub('porkbun'));

        $this->assertSame('cloudflare', $registrar->name());
    }
}
