<?php

namespace Tests\Feature;

use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class]);

        // Porkbun is the default registrar — searches resolve through it.
        config()->set('domain.default_registrar', 'porkbun');
        config()->set('domain.porkbun.api_key', 'pk_test');
        config()->set('domain.porkbun.secret_key', 'sk_test');
        config()->set('domain.porkbun.endpoint', 'https://api.porkbun.com/api/json/v3');
    }

    /** Fakes Porkbun so a configurable set of domains report as available. */
    private function fakePorkbun(array $availableDomains): void
    {
        Http::fake(['api.porkbun.com/*' => function (Request $request) use ($availableDomains) {
            // /api/json/v3/domain/checkDomain/{domain}
            $domain = strtolower(basename((string) parse_url($request->url(), PHP_URL_PATH)));
            $isAvailable = in_array($domain, $availableDomains, true);

            return Http::response([
                'status' => 'SUCCESS',
                'response' => $isAvailable
                    ? ['avail' => 'yes', 'price' => '9.68', 'premium' => 'no']
                    : ['avail' => 'no'],
            ]);
        }]);
    }

    public function test_available_domain_returns_price_in_gbp(): void
    {
        $this->fakePorkbun(['available-name.com']);

        // The customer price stays the GBP catalogue price (£12.99), not the
        // registrar's USD wholesale figure — pricing is set server-side.
        $this->postJson('/domains/search', ['domain' => 'available-name.com'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'domain' => 'available-name.com',
                'available' => true,
                'currency' => 'GBP',
                'price' => '12.99',
            ]);
    }

    public function test_unavailable_domain_returns_suggestions(): void
    {
        // Main .com taken; the .co.uk and .net alternatives are available.
        $this->fakePorkbun(['taken-name.co.uk', 'taken-name.net']);

        $response = $this->postJson('/domains/search', ['domain' => 'taken-name.com'])
            ->assertOk()
            ->assertJson(['success' => true, 'available' => false]);

        $suggestions = collect($response->json('suggestions'))->pluck('domain');
        $this->assertTrue($suggestions->contains('taken-name.co.uk'));
    }

    public function test_invalid_domain_is_rejected_with_validation_error(): void
    {
        // No Http::fake registered — if the controller tried to reach the
        // registrar the test would error, proving validation short-circuits.
        $response = $this->postJson('/domains/search', ['domain' => 'not a domain']);

        $response->assertStatus(422);
        $this->assertArrayHasKey('domain', $response->json('errors'));
    }

    public function test_registrar_failure_returns_safe_message_not_raw_error(): void
    {
        Http::fake(['api.porkbun.com/*' => Http::response(['status' => 'ERROR', 'message' => 'Invalid API key.'])]);

        $response = $this->postJson('/domains/search', ['domain' => 'example.com'])
            ->assertStatus(502)
            ->assertJson(['success' => false]);

        // The customer must never see the raw registrar error detail.
        $this->assertStringNotContainsString('Invalid API key', $response->getContent());
        $this->assertStringNotContainsString('PORKBUN', $response->getContent());
    }
}
