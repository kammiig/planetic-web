<?php

namespace App\Services\DNS;

use App\Exceptions\CloudflareException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare DNS API wrapper (Tickets 28, 32). Uses a scoped API token stored
 * only in config. Website records (@/www) are proxied; mail and control-panel
 * records must remain DNS-only.
 */
class CloudflareService
{
    private const ZONE_ALREADY_EXISTS = 1061;

    /**
     * Create a full DNS zone for a domain.
     *
     * @return array{id: string, name: string, status: string, name_servers: array<int, string>}
     */
    public function createZone(string $domain): array
    {
        $payload = [
            'name' => strtolower($domain),
            'type' => 'full',
            'jump_start' => false,
        ];

        if ($accountId = config('cloudflare.account_id')) {
            $payload['account'] = ['id' => $accountId];
        }

        $response = $this->request('post', '/zones', $payload, 'create zone', allowFailure: true);

        if (! ($response['success'] ?? false)) {
            $codes = array_column($response['errors'] ?? [], 'code');
            if (in_array(self::ZONE_ALREADY_EXISTS, $codes, true)) {
                throw new CloudflareException("Cloudflare zone for {$domain} already exists.", zoneExists: true, context: $response['errors']);
            }
            throw new CloudflareException('Cloudflare create zone failed: '.$this->firstError($response), context: $response['errors'] ?? null);
        }

        return $this->normaliseZone($response['result']);
    }

    /**
     * Find an existing zone by name (used to reuse a zone safely).
     *
     * @return array{id: string, name: string, status: string, name_servers: array<int, string>}|null
     */
    public function findZoneByName(string $domain): ?array
    {
        $response = $this->request('get', '/zones', ['name' => strtolower($domain)], 'list zones');
        $zone = $response['result'][0] ?? null;

        return $zone ? $this->normaliseZone($zone) : null;
    }

    /**
     * Create a DNS record in a zone.
     *
     * @param  array<string, mixed>  $record  {type, name, content, ttl, proxied, priority?}
     * @return array{id: string}
     */
    public function createDnsRecord(string $zoneId, array $record): array
    {
        $response = $this->request('post', "/zones/{$zoneId}/dns_records", $record, 'create DNS record');

        if (! ($response['success'] ?? false)) {
            throw new CloudflareException('Cloudflare create DNS record failed: '.$this->firstError($response), context: $response['errors'] ?? null);
        }

        return ['id' => $response['result']['id']];
    }

    public function updateDnsRecord(string $zoneId, string $recordId, array $record): array
    {
        $response = $this->request('patch', "/zones/{$zoneId}/dns_records/{$recordId}", $record, 'update DNS record');

        if (! ($response['success'] ?? false)) {
            throw new CloudflareException('Cloudflare update DNS record failed: '.$this->firstError($response));
        }

        return ['id' => $response['result']['id']];
    }

    public function deleteDnsRecord(string $zoneId, string $recordId): void
    {
        $this->request('delete', "/zones/{$zoneId}/dns_records/{$recordId}", [], 'delete DNS record');
    }

    public function setSslMode(string $zoneId, string $mode): void
    {
        $this->request('patch', "/zones/{$zoneId}/settings/ssl", ['value' => $mode], 'set SSL mode');
    }

    public function enableAlwaysUseHttps(string $zoneId): void
    {
        $this->request('patch', "/zones/{$zoneId}/settings/always_use_https", ['value' => 'on'], 'enable Always Use HTTPS');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload, string $label, bool $allowFailure = false): array
    {
        if (blank(config('cloudflare.api_token'))) {
            throw new CloudflareException('Cloudflare API token is not configured.');
        }

        $url = rtrim((string) config('cloudflare.api_base'), '/').$path;

        try {
            $client = $this->client();
            $response = match ($method) {
                'get' => $client->get($url, $payload),
                'delete' => $client->delete($url),
                'patch' => $client->patch($url, $payload),
                default => $client->post($url, $payload),
            };
        } catch (Throwable $e) {
            throw new CloudflareException("Cloudflare {$label} request error: {$e->getMessage()}", previous: $e);
        }

        $json = $response->json() ?? [];

        if ($response->failed() && ! $allowFailure) {
            throw new CloudflareException("Cloudflare {$label} HTTP {$response->status()}: ".$this->firstError($json), context: $json['errors'] ?? null);
        }

        return is_array($json) ? $json : [];
    }

    private function client(): PendingRequest
    {
        return Http::withToken(config('cloudflare.api_token'))
            ->acceptJson()
            ->timeout((int) config('cloudflare.request_timeout', 30));
    }

    private function normaliseZone(array $zone): array
    {
        return [
            'id' => $zone['id'],
            'name' => $zone['name'],
            'status' => $zone['status'] ?? 'pending',
            'name_servers' => $zone['name_servers'] ?? [],
        ];
    }

    private function firstError(array $response): string
    {
        return $response['errors'][0]['message'] ?? 'unknown error';
    }
}
