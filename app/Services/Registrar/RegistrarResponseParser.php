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

    /**
     * Assert a Porkbun response succeeded (status === SUCCESS) and return the
     * decoded JSON. Porkbun reports failures as {status:"ERROR", message:"…"}.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public function porkbunReply(array $json, string $operation): array
    {
        if (strtoupper((string) ($json['status'] ?? '')) === 'SUCCESS') {
            return $json;
        }

        $message = (string) ($json['message'] ?? 'unknown error');
        $hint = $this->porkbunHint($message);

        throw new RegistrarException(
            "Porkbun {$operation} failed: {$message}".($hint ? " — {$hint}" : ''),
            registrar: 'porkbun',
            context: $json,
        );
    }

    /**
     * Plain-English admin guidance for the Porkbun failures we expect to see.
     * Shown in the provisioning monitor and admin alerts — never to customers.
     */
    public function porkbunHint(string $message): ?string
    {
        $message = strtolower($message);

        $mentionsContact = str_contains($message, 'contact') || str_contains($message, 'registrant')
            || str_contains($message, 'phone') || str_contains($message, 'address')
            || str_contains($message, 'postal') || str_contains($message, 'postcode')
            || str_contains($message, 'state') || str_contains($message, 'country');

        return match (true) {
            str_contains($message, 'rate limit') || str_contains($message, 'rate_limit') || str_contains($message, 'checks within') || str_contains($message, 'too many')
                => 'Porkbun rate-limited the request (its domain availability checks allow roughly one every 10 seconds). This is transient — registration now prices via the non-rate-limited pricing endpoint, so just retry the step in a few seconds.',
            str_contains($message, 'api key') || str_contains($message, 'credential') || str_contains($message, 'invalid key')
                => 'Check PORKBUN_API_KEY / PORKBUN_SECRET_KEY in the server .env — Porkbun rejected the credentials.',
            str_contains($message, 'balance') || str_contains($message, 'fund') || str_contains($message, 'insufficient')
                => 'The Porkbun account balance is too low to pay for this registration. Top up funds in the Porkbun dashboard, then retry the step.',
            str_contains($message, 'api access') || str_contains($message, 'opted in') || str_contains($message, 'not enabled')
                => 'Enable "API Access" for this domain in the Porkbun control panel before nameserver/DNS calls will work.',
            str_contains($message, 'not available') || str_contains($message, 'unavailable') || str_contains($message, 'taken')
                => 'The domain is no longer available to register — agree a different domain with the customer, update the order, and retry.',
            $mentionsContact
                => 'Porkbun rejected the registrant/contact details. Fix the default WHOIS contact on your Porkbun account: full name, a valid international phone (e.g. +44…), street address, city, postcode and a 2-letter country code. Some TLDs (e.g. .co.uk) require a complete, valid UK registrant. Correct the contact, then retry the step.',
            str_contains($message, 'requirement') || str_contains($message, 'eligibility') || str_contains($message, 'not allowed') || str_contains($message, 'cannot be registered')
                => 'This TLD has extra registration requirements. Review Porkbun\'s requirements for the TLD and make sure the account\'s default registrant contact satisfies them, then retry.',
            str_contains($message, 'cost') || str_contains($message, 'price') || str_contains($message, 'amount')
                => 'The price sent did not match Porkbun\'s current price. Run "cloudflare/tld" price sync or check the TLD price, then retry.',
            default => null,
        };
    }

    /**
     * Interpret a Porkbun checkDomain reply for a specific domain. The payload
     * nests the result under `response` ({avail:"yes"|"no", price, premium}).
     *
     * @param  array<string, mixed>  $json
     * @return array{domain: string, available: bool, premium: bool, price: ?string, currency: string}
     */
    public function porkbunAvailability(array $json, string $domain): array
    {
        $domain = strtolower($domain);
        $node = is_array($json['response'] ?? null) ? $json['response'] : $json;

        $avail = strtolower((string) ($node['avail'] ?? ''));

        if ($avail === '') {
            // Could not determine — never allow checkout for uncertain availability.
            throw new RegistrarException("Porkbun availability for {$domain} was indeterminate.", registrar: 'porkbun', context: $json);
        }

        return [
            'domain' => $domain,
            'available' => in_array($avail, ['yes', 'true', '1'], true),
            'premium' => in_array(strtolower((string) ($node['premium'] ?? 'no')), ['yes', 'true', '1'], true),
            'price' => isset($node['price']) ? (string) $node['price'] : null,
            'currency' => 'USD',
        ];
    }
}
