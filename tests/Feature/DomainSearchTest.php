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
        config()->set('domain.namesilo.api_key', 'test-key');
    }

    /** Fakes NameSilo so a configurable set of domains report as available. */
    private function fakeNameSilo(array $availableDomains): void
    {
        Http::fake(['www.namesilo.com/*' => function (Request $request) use ($availableDomains) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
            $domain = strtolower($query['domains'] ?? '');
            $isAvailable = in_array($domain, $availableDomains, true);

            return Http::response([
                'reply' => array_merge(
                    ['code' => 300, 'detail' => 'success'],
                    $isAvailable
                        ? ['available' => ['domain' => [['domain' => $domain, 'price' => '11.99']]]]
                        : ['unavailable' => ['domain' => [['domain' => $domain]]]],
                ),
            ]);
        }]);
    }

    public function test_available_domain_returns_price_in_gbp(): void
    {
        $this->fakeNameSilo(['available-name.com']);

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
        $this->fakeNameSilo(['taken-name.co.uk', 'taken-name.net']);

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
        Http::fake(['www.namesilo.com/*' => Http::response([
            'reply' => ['code' => 110, 'detail' => 'invalid api key'],
        ])]);

        $response = $this->postJson('/domains/search', ['domain' => 'example.com'])
            ->assertStatus(502)
            ->assertJson(['success' => false]);

        // The customer must never see the raw registrar error detail.
        $this->assertStringNotContainsString('invalid api key', $response->getContent());
        $this->assertStringNotContainsString('code 110', $response->getContent());
    }
}
