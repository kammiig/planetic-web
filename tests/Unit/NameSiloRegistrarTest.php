<?php

namespace Tests\Unit;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\NameSiloRegistrar;
use App\Services\Registrar\RegistrarResponseParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NameSiloRegistrarTest extends TestCase
{
    private function registrar(): NameSiloRegistrar
    {
        config()->set('domain.namesilo.api_key', 'test-key');
        config()->set('domain.namesilo.endpoint', 'https://www.namesilo.com/api');

        return new NameSiloRegistrar(new RegistrarResponseParser());
    }

    public function test_parses_an_available_domain(): void
    {
        Http::fake(['www.namesilo.com/*' => Http::response([
            'reply' => [
                'code' => 300,
                'detail' => 'success',
                'available' => ['domain' => [['domain' => 'example.com', 'price' => '11.99']]],
            ],
        ])]);

        $result = $this->registrar()->checkAvailability('example.com');

        $this->assertTrue($result['available']);
        $this->assertSame('example.com', $result['domain']);
        $this->assertFalse($result['premium']);
    }

    public function test_parses_an_unavailable_domain(): void
    {
        Http::fake(['www.namesilo.com/*' => Http::response([
            'reply' => [
                'code' => 300,
                'detail' => 'success',
                'unavailable' => ['domain' => [['domain' => 'taken.com']]],
            ],
        ])]);

        $result = $this->registrar()->checkAvailability('taken.com');

        $this->assertFalse($result['available']);
    }

    public function test_non_success_code_throws_registrar_exception(): void
    {
        Http::fake(['www.namesilo.com/*' => Http::response([
            'reply' => ['code' => 110, 'detail' => 'invalid api key'],
        ])]);

        $this->expectException(RegistrarException::class);
        $this->registrar()->checkAvailability('example.com');
    }

    public function test_registration_returns_normalised_payload(): void
    {
        Http::fake(['www.namesilo.com/*' => Http::response([
            'reply' => [
                'code' => 300,
                'detail' => 'success',
                'domain' => 'example.com',
                'order_id' => '99887',
                'order_amount' => '11.99',
            ],
        ])]);

        $result = $this->registrar()->registerDomain([
            'domain' => 'example.com',
            'years' => 1,
            'whois_privacy' => true,
            'auto_renew' => true,
            'contact' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('99887', $result['registrar_order_id']);
        $this->assertSame('11.99', $result['order_amount']);
    }

    public function test_missing_api_key_throws(): void
    {
        config()->set('domain.namesilo.api_key', null);
        $registrar = new NameSiloRegistrar(new RegistrarResponseParser());

        $this->expectException(RegistrarException::class);
        $registrar->checkAvailability('example.com');
    }
}
