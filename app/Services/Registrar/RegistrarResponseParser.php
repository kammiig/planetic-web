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
            $hint = $this->nameSiloCodeHint($code);

            throw new RegistrarException(
                "NameSilo {$operation} failed (code {$code}): {$detail}".($hint ? " — {$hint}" : ''),
                registrar: 'namesilo',
                context: $reply,
            );
        }

        return $reply;
    }

    /**
     * Plain-English admin guidance for the NameSilo failure codes we actually
     * see in practice. Shown in the provisioning monitor and admin alert
     * emails — never to customers.
     */
    private function nameSiloCodeHint(int $code): ?string
    {
        return match ($code) {
            110 => 'Check NAMESILO_API_KEY in the server .env — NameSilo says the key is invalid.',
            112 => 'The API key belongs to a NameSilo sub-account that cannot use this operation.',
            113 => 'This NameSilo API endpoint is disabled for the account — check API settings at namesilo.com.',
            119 => 'The NameSilo account balance is too low to pay for this registration. Top up funds at namesilo.com -> Account Funds, then retry the step.',
            261, 262 => 'The domain is no longer available to register — agree a different domain with the customer, update the order, and retry.',
            default => null,
        };
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
