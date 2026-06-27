<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Support\DomainName;
use App\Support\Secrets;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Primary/default registrar. Porkbun API v3 is JSON over POST; credentials
 * (apikey + secretapikey) travel in the request body — never the URL — and are
 * read only from config (env). They are never logged, emailed or shown to
 * customers/admins (Security & Access §15).
 *
 * Porkbun registers domains for exactly one year (matching the "free domain for
 * the first year" rule) and bills from the account balance. The create call
 * confirms the price in pennies as an anti-surprise guard, so registration is
 * preceded by an availability/price check.
 */
class PorkbunRegistrar implements RegistrarInterface
{
    public function __construct(
        private readonly RegistrarResponseParser $parser,
    ) {}

    public function name(): string
    {
        return 'porkbun';
    }

    public function checkAvailability(string $domain): array
    {
        $domain = strtolower($domain);
        $json = $this->command('/domain/checkDomain/'.$domain, [], 'availability check');

        return $this->parser->porkbunAvailability($json, $domain);
    }

    public function getPricing(string $tld): array
    {
        $tld = $this->tldOf($tld);

        // /pricing/get is public default pricing and needs no authentication.
        $json = $this->command('/pricing/get', [], 'pricing', auth: false, method: 'GET');
        $row = $json['pricing'][$tld] ?? null;

        if (! is_array($row)) {
            return ['tld' => $tld, 'registration' => null, 'renewal' => null, 'transfer' => null, 'currency' => 'USD', 'supported' => false];
        }

        return [
            'tld' => $tld,
            'registration' => isset($row['registration']) ? (string) $row['registration'] : null,
            'renewal' => isset($row['renewal']) ? (string) $row['renewal'] : null,
            'transfer' => isset($row['transfer']) ? (string) $row['transfer'] : null,
            'currency' => 'USD',
            'supported' => true,
        ];
    }

    public function registerDomain(array $data): array
    {
        $domain = strtolower($data['domain']);
        $dryRun = (bool) ($data['dry_run'] ?? false);

        // Confirm availability and obtain the exact price Porkbun expects. The
        // create call rejects a mismatched cost, so we send the live figure.
        $check = $this->checkAvailability($domain);

        if (! $dryRun && ! $check['available']) {
            throw new RegistrarException(
                "Porkbun reports {$domain} is not available to register.",
                safeMessage: 'That domain is no longer available. Please choose another.',
                registrar: 'porkbun',
            );
        }

        $payload = [
            'cost' => (int) round(((float) ($check['price'] ?? 0)) * 100), // pennies
            'agreeToTerms' => 'yes',
            'whoisPrivacy' => ! empty($data['whois_privacy']) ? 'yes' : 'no',
        ];

        if ($dryRun) {
            $payload['dryRun'] = true;
        }

        $reply = $this->command('/domain/create/'.$domain, $payload, 'domain registration');

        if ($dryRun && isset($reply['wouldSucceed']) && ! filter_var($reply['wouldSucceed'], FILTER_VALIDATE_BOOL)) {
            $message = (string) ($reply['message'] ?? 'registration would fail');
            throw new RegistrarException("Porkbun dry-run check failed: {$message}", registrar: 'porkbun', context: $reply);
        }

        return [
            'domain' => $domain,
            'success' => true,
            // Porkbun manages domains by name (no separate domain id); the
            // order id is the closest thing to a registration reference.
            'registrar_domain_id' => $domain,
            'registrar_order_id' => isset($reply['orderId']) ? (string) $reply['orderId'] : null,
            'order_amount' => isset($reply['cost']) ? (string) $reply['cost'] : null,
            'expiry_date' => $dryRun ? null : $this->lookupExpiry($domain),
        ];
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        // Porkbun has no renewal API endpoint — domains renew automatically via
        // auto-renew. Surface this clearly rather than silently doing nothing.
        throw new RegistrarException(
            'Porkbun does not expose a renewal API; Porkbun domains renew automatically via auto-renew. Manage renewals in the Porkbun dashboard.',
            registrar: 'porkbun',
        );
    }

    public function getDomainInfo(string $domain): array
    {
        $domain = strtolower($domain);

        $reply = $this->command('/domain/listAll', ['domain' => $domain], 'domain info');

        $match = null;
        foreach ((array) ($reply['domains'] ?? []) as $row) {
            if (is_array($row) && strtolower((string) ($row['domain'] ?? '')) === $domain) {
                $match = $row;
                break;
            }
        }

        return [
            'domain' => $domain,
            'status' => $match['status'] ?? null,
            'expiry_date' => $match['expireDate'] ?? null,
            'nameservers' => $this->safeNameservers($domain),
        ];
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        $domain = strtolower($domain);

        $this->command('/domain/updateNs/'.$domain, ['ns' => array_values($nameservers)], 'nameserver update');

        return ['domain' => $domain, 'success' => true];
    }

    /**
     * Connection / credential check. Porkbun's /ping echoes the caller's IP and
     * (when keys are sent) whether the credentials are valid. Used by the
     * `registrar:test` command — not part of the cross-provider interface.
     *
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return $this->command('/ping', [], 'ping');
    }

    /**
     * Best-effort expiry lookup after registration. Never fails the
     * registration: the caller defaults to one year when this returns null.
     */
    private function lookupExpiry(string $domain): ?string
    {
        try {
            $reply = $this->command('/domain/listAll', ['domain' => $domain], 'domain info');

            foreach ((array) ($reply['domains'] ?? []) as $row) {
                if (is_array($row) && strtolower((string) ($row['domain'] ?? '')) === $domain) {
                    return $row['expireDate'] ?? null;
                }
            }
        } catch (RegistrarException) {
            // Expiry will be refreshed later by domains:sync.
        }

        return null;
    }

    /**
     * getNs requires per-domain API access; treat a failure as "unknown" rather
     * than letting it break a domain-info read.
     *
     * @return array<int, string>
     */
    private function safeNameservers(string $domain): array
    {
        try {
            $reply = $this->command('/domain/getNs/'.$domain, [], 'nameserver lookup');

            return array_values(array_filter(array_map('strval', (array) ($reply['ns'] ?? []))));
        } catch (RegistrarException) {
            return [];
        }
    }

    /**
     * Issue a Porkbun API request and return the asserted (SUCCESS) JSON body.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    /**
     * Diagnose a registration without charging: runs availability + a dry-run
     * create and captures the endpoint, the exact (credential-free) payload, the
     * HTTP status, the raw response body and the parsed reason. Never throws.
     *
     * @return array<string, mixed>
     */
    public function debugRegister(string $domain, bool $whoisPrivacy = true): array
    {
        $domain = strtolower(trim($domain));
        $tld = $this->tldOf($domain);
        $endpoint = rtrim((string) config('domain.porkbun.endpoint', 'https://api.porkbun.com/api/json/v3'), '/');

        $report = [
            'registrar' => $this->name(),
            'domain' => $domain,
            'tld' => $tld,
            'endpoint' => $endpoint.'/domain/create/'.$domain,
        ];

        // Availability + price → this price (in pennies) is the `cost` we send.
        try {
            $check = $this->checkAvailability($domain);
            $report['availability'] = ['available' => $check['available'], 'price' => $check['price'], 'premium' => $check['premium']];
            $report['cost_pennies_sent'] = (int) round(((float) ($check['price'] ?? 0)) * 100);
        } catch (Throwable $e) {
            $report['availability'] = ['error' => Secrets::redact($e->getMessage())];
        }

        $report['request_payload'] = [
            'cost' => $report['cost_pennies_sent'] ?? 0,
            'agreeToTerms' => 'yes',
            'whoisPrivacy' => $whoisPrivacy ? 'yes' : 'no',
            'dryRun' => true,
            // apikey / secretapikey are added internally and never shown.
        ];

        // No-charge dry-run create — capture the exact reason and response body.
        try {
            $result = $this->registerDomain(['domain' => $domain, 'whois_privacy' => $whoisPrivacy, 'dry_run' => true]);
            $report['http_status'] = 200;
            $report['reason'] = 'Dry-run reports the registration WOULD succeed.';
            $report['response_body'] = Secrets::redactArray($result);
        } catch (RegistrarException $e) {
            $report['reason'] = Secrets::redact($e->getMessage());
            $report['response_body'] = is_array($e->context) ? $e->context : ['detail' => Secrets::redact((string) $e->context)];
        }

        $report['registration_requirements'] = $this->getRegistrationRequirements($tld);

        return $report;
    }

    /**
     * Fetch Porkbun's registration requirements for a TLD (eligibility TLDs such
     * as .co.uk may need extra registrant fields). Returns the raw (redacted)
     * response or an error note — never throws.
     *
     * @return array<string, mixed>
     */
    public function getRegistrationRequirements(string $tldOrDomain): array
    {
        $tld = $this->tldOf($tldOrDomain);
        $config = config('domain.porkbun');
        $endpoint = rtrim((string) ($config['endpoint'] ?? 'https://api.porkbun.com/api/json/v3'), '/');

        try {
            $response = Http::timeout((int) config('domain.request_timeout', 30))
                ->acceptJson()
                ->post($endpoint.'/domain/getRegistrationRequirements/'.$tld, [
                    'apikey' => $config['api_key'] ?? null,
                    'secretapikey' => $config['secret_key'] ?? null,
                ]);

            return Secrets::redactArray(
                is_array($response->json()) ? $response->json() : ['status' => $response->status(), 'raw' => Secrets::redact($response->body())]
            );
        } catch (Throwable $e) {
            return ['error' => Secrets::redact($e->getMessage())];
        }
    }

    private function command(string $path, array $payload, string $label, bool $auth = true, string $method = 'POST'): array
    {
        $config = config('domain.porkbun');
        $endpoint = rtrim($config['endpoint'] ?? 'https://api.porkbun.com/api/json/v3', '/');

        if ($auth) {
            $apiKey = $config['api_key'] ?? null;
            $secret = $config['secret_key'] ?? null;

            if (blank($apiKey) || blank($secret)) {
                throw new RegistrarException('Porkbun API credentials are not configured.', registrar: 'porkbun');
            }

            // Credentials go in the JSON body, never the URL.
            $payload = array_merge(['apikey' => $apiKey, 'secretapikey' => $secret], $payload);
        }

        $url = $endpoint.$path;

        try {
            $http = Http::timeout(config('domain.request_timeout', 30))->acceptJson();

            $response = $method === 'GET'
                ? $http->get($url)
                : $http->post($url, $payload);
        } catch (Throwable $e) {
            $safe = Secrets::redact($e->getMessage());
            Log::channel('stack')->warning('Porkbun request failed', ['path' => $path, 'error' => $safe]);
            throw new RegistrarException("Porkbun {$label} request error: {$safe}", registrar: 'porkbun', previous: $e);
        }

        if ($response->failed()) {
            // Surface Porkbun's actual error reason — never a bare "HTTP 400".
            // Porkbun returns {status:"ERROR", message:"…"} even on 4xx, but if
            // the body is empty/non-JSON we still attach the raw (redacted) body
            // so the admin always sees something concrete. The full body is also
            // stored on the exception context for the provisioning monitor.
            $body = is_array($response->json()) ? $response->json() : [];
            $reason = trim((string) ($body['message'] ?? ''));
            $rawBody = trim(Secrets::redact($response->body()));
            $rawBody = mb_strlen($rawBody) > 500 ? mb_substr($rawBody, 0, 500).'…' : $rawBody;

            $detail = $reason !== ''
                ? Secrets::redact($reason)
                : ($rawBody !== '' ? $rawBody : '<no response body>');
            $hint = $this->parser->porkbunHint($detail);

            Log::channel('stack')->warning('Porkbun '.$label.' rejected', [
                'path' => $path,
                'status' => $response->status(),
                'reason' => $detail,
                'body' => $rawBody,
            ]);

            throw new RegistrarException(
                "Porkbun {$label} HTTP {$response->status()}: {$detail}".($hint ? " — {$hint}" : ''),
                registrar: 'porkbun',
                context: ! empty($body) ? Secrets::redactArray($body) : ['status' => $response->status(), 'body' => $rawBody],
            );
        }

        return $this->parser->porkbunReply($response->json() ?? [], $label);
    }

    /** Normalise a TLD or full domain ("example.co.uk") to its registrable TLD ("co.uk"). */
    private function tldOf(string $tldOrDomain): string
    {
        $value = ltrim(strtolower(trim($tldOrDomain)), '.');

        return str_contains($value, '.') ? DomainName::parse($value)->tld : $value;
    }
}
