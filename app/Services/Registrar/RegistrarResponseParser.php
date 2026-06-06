<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;

/**
 * Normalises raw registrar responses into predictable shapes and surfaces
 * registrar error codes as RegistrarException. Keeping this logic isolated
 * makes the registrar classes thin and unit-testable with mocked payloads.
 */
class RegistrarResponseParser
{
    public const NAMESILO_SUCCESS = 300;

    /**
     * Return the NameSilo `reply` node, asserting the operation succeeded.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public function nameSiloReply(array $json, string $operation): array
    {
        $reply = $json['reply'] ?? null;

        if (! is_array($reply)) {
            throw new RegistrarException("NameSilo {$operation}: malformed response (no reply node).", registrar: 'namesilo', context: $json);
        }

        $code = (int) ($reply['code'] ?? 0);

        if ($code !== self::NAMESILO_SUCCESS) {
            $detail = $reply['detail'] ?? 'unknown error';
            throw new RegistrarException(
                "NameSilo {$operation} failed (code {$code}): {$detail}",
                registrar: 'namesilo',
                context: $reply,
            );
        }

        return $reply;
    }

    /**
     * Interpret a NameSilo availability reply for a specific domain.
     *
     * @param  array<string, mixed>  $reply
     * @return array{domain: string, available: bool, premium: bool, price: ?string, currency: string}
     */
    public function nameSiloAvailability(array $reply, string $domain): array
    {
        $domain = strtolower($domain);

        $available = $this->nameSiloDomainNodes($reply['available'] ?? null);
        $unavailable = $this->nameSiloDomainNodes($reply['unavailable'] ?? null);

        foreach ($available as $node) {
            if (strtolower((string) ($node['domain'] ?? '')) === $domain) {
                return [
                    'domain' => $domain,
                    'available' => true,
                    'premium' => (bool) ($node['premium'] ?? false),
                    'price' => isset($node['price']) ? (string) $node['price'] : null,
                    'currency' => 'USD',
                ];
            }
        }

        foreach ($unavailable as $node) {
            if (strtolower((string) ($node['domain'] ?? '')) === $domain) {
                return [
                    'domain' => $domain,
                    'available' => false,
                    'premium' => false,
                    'price' => null,
                    'currency' => 'USD',
                ];
            }
        }

        // Could not determine — never allow checkout for uncertain availability.
        throw new RegistrarException("NameSilo availability for {$domain} was indeterminate.", registrar: 'namesilo', context: $reply);
    }

    /**
     * NameSilo returns domain nodes as either a single object or a list.
     * Normalise both to a list of arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    private function nameSiloDomainNodes(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        // Shape: { "domain": [ {...}, {...} ] } or { "domain": {...} } or [ "example.com" ]
        if (isset($node['domain'])) {
            $domains = $node['domain'];
            if (is_string($domains)) {
                return [['domain' => $domains, 'price' => $node['price'] ?? null, 'premium' => $node['premium'] ?? false]];
            }
            if (is_array($domains) && array_is_list($domains)) {
                return array_map(fn ($d) => is_array($d) ? $d : ['domain' => $d], $domains);
            }
            if (is_array($domains)) {
                return [$domains];
            }
        }

        // Plain list of strings.
        if (array_is_list($node)) {
            return array_map(fn ($d) => is_array($d) ? $d : ['domain' => $d], $node);
        }

        return [];
    }
}
