<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Support\DomainName;
use App\Support\Secrets;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Primary registrar. Cloudflare Registrar API (public beta, April 2026) —
 * REST/JSON under /accounts/{account_id}/registrar, authenticated with a scoped
 * bearer token that is read only from config (env) and never logged, emailed or
 * shown to customers/admins (Security & Access §15).
 *
 * Why it is the primary registrar here:
 *  - Domains register at cost (registry + ICANN fee, no markup).
 *  - Cloudflare Registrar domains are locked to Cloudflare nameservers, which is
 *    exactly where this platform points every domain anyway — so the separate
 *    "update nameservers at the registrar" step becomes a verified no-op.
 *  - Billing is the account's default payment method, so there is no prepaid
 *    balance to keep topped up.
 *
 * Beta limitations that this class handles explicitly:
 *  - Only a subset of Cloudflare's supported extensions are registrable through
 *    the API. Unsupported ones come back as {registrable:false,
 *    reason:"extension_not_supported_via_api"} — surfaced as a FALLBACK-ELIGIBLE
 *    exception so FallbackRegistrar can route the domain to Porkbun instead of
 *    failing the order. (.uk is Cloudflare's own documented example of this.)
 *  - There is no renewal, transfer or contact-update endpoint yet; renewals run
 *    on Cloudflare auto-renew, matching the Porkbun behaviour.
 *  - Registration may complete asynchronously (HTTP 202), so this polls the
 *    registration-status endpoint and is idempotent on retry.
 */
class CloudflareRegistrar implements RegistrarInterface
{
    /** Cloudflare's reason code for "this TLD works in the dashboard but not via the API yet". */
    public const REASON_EXTENSION_UNSUPPORTED = 'extension_not_supported_via_api';

    /** Terminal + non-terminal workflow states returned by registration-status. */
    private const STATE_SUCCEEDED = 'succeeded';

    private const STATE_IN_PROGRESS = 'in_progress';

    public function __construct(
        private readonly RegistrarResponseParser $parser,
    ) {}

    public function name(): string
    {
        return 'cloudflare';
    }

    public function checkAvailability(string $domain): array
    {
        $domain = strtolower(trim($domain));

        // Cached briefly so the registration path can reuse the checkout search
        // result instead of paying for a second round-trip, mirroring Porkbun.
        return Cache::remember('cloudflare-availability:'.$domain, now()->addMinutes(5), function () use ($domain) {
            $result = $this->command('post', '/domain-check', ['domains' => [$domain]], 'availability check');

            return $this->parser->cloudflareAvailability($result, $domain);
        });
    }

    /**
     * Cloudflare exposes no bulk TLD price list — pricing comes back per domain
     * from domain-check. A random (therefore near-certainly unregistered) probe
     * SLD is used to read the registry price for the extension. The probe is
     * only ever *checked*, never registered.
     */
    public function getPricing(string $tld): array
    {
        $tld = $this->tldOf($tld);
        $unsupported = ['tld' => $tld, 'registration' => null, 'renewal' => null, 'transfer' => null, 'currency' => 'USD', 'supported' => false];

        $probe = 'pw-price-'.Str::lower(Str::random(12)).'.'.$tld;

        try {
            $result = $this->command('post', '/domain-check', ['domains' => [$probe]], 'pricing');
        } catch (RegistrarException) {
            // Price sync must never be fatal — the admin price book keeps its
            // existing cost figures and TldPriceSync records this TLD as skipped.
            return $unsupported;
        }

        $node = $this->domainNode($result, $probe);
        $pricing = is_array($node['pricing'] ?? null) ? $node['pricing'] : null;

        if ($pricing === null || ! isset($pricing['registration_cost'])) {
            return $unsupported;
        }

        return [
            'tld' => $tld,
            'registration' => (string) $pricing['registration_cost'],
            'renewal' => isset($pricing['renewal_cost']) ? (string) $pricing['renewal_cost'] : null,
            // Cloudflare charges no transfer fee (transfers only add a year at
            // the renewal price), so renewal is the honest transfer figure.
            'transfer' => isset($pricing['renewal_cost']) ? (string) $pricing['renewal_cost'] : null,
            'currency' => (string) ($pricing['currency'] ?? 'USD'),
            'supported' => true,
        ];
    }

    public function registerDomain(array $data): array
    {
        $domain = strtolower(trim($data['domain']));
        $dryRun = (bool) ($data['dry_run'] ?? false);

        // Idempotency guard. Registration can complete asynchronously and the
        // provisioning step is retryable, so a retry must never buy the domain
        // twice — registrations are non-refundable. If Cloudflare already holds
        // this registration, adopt it instead of creating another.
        if (! $dryRun && ($existing = $this->existingRegistration($domain)) !== null) {
            return $this->registrationResult($domain, $existing);
        }

        // Confirms the extension is registrable through the API beta *and* that
        // the name is still free. Throws fallback-eligible when Cloudflare's API
        // cannot handle the TLD, so the order routes to the fallback registrar.
        $check = $this->checkAvailability($domain);

        if (! $check['available']) {
            throw new RegistrarException(
                "Cloudflare reports {$domain} is not available to register.",
                safeMessage: 'That domain is no longer available. Please choose another.',
                registrar: $this->name(),
            );
        }

        if ($check['premium'] && ! ($data['allow_premium'] ?? false)) {
            // Premium pricing needs an explicit fee acknowledgement; refusing is
            // safer than silently charging a customer a premium registry fee.
            throw new RegistrarException(
                "{$domain} is a premium domain (".($check['price'] ?? '?').' '.$check['currency'].') and premium registration is not enabled.',
                safeMessage: 'That domain is a premium name and cannot be registered automatically. Please choose another.',
                registrar: $this->name(),
                context: ['premium' => true, 'price' => $check['price']],
            );
        }

        if ($dryRun) {
            // Cloudflare has no dry-run mode; domain-check is the no-charge
            // equivalent and has already confirmed price + registrability.
            return [
                'domain' => $domain,
                'success' => true,
                'registrar' => $this->name(),
                'registrar_domain_id' => null,
                'registrar_order_id' => null,
                'order_amount' => $check['price'],
                'expiry_date' => null,
                'dry_run' => true,
            ];
        }

        $payload = ['domain_name' => $domain];

        if ($contact = $this->registrantContact($data['contact'] ?? [])) {
            $payload['contacts'] = ['registrant' => $contact];
        }

        $result = $this->command('post', '/registrations', $payload, 'domain registration');

        return $this->registrationResult($domain, $this->awaitCompletion($domain, $result));
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        // The Registrar API beta has no renewal endpoint — Cloudflare renews
        // automatically ~30 days before expiry (with retries) against the
        // account's default payment method. Surface this rather than no-op.
        throw new RegistrarException(
            'Cloudflare Registrar does not yet expose a renewal API; Cloudflare domains renew automatically via auto-renew '
            .'against the account payment method. Manage renewals in the Cloudflare dashboard.',
            registrar: $this->name(),
        );
    }

    public function getDomainInfo(string $domain): array
    {
        $domain = strtolower(trim($domain));

        $result = $this->command('get', '/registrations/'.rawurlencode($domain), [], 'domain info');
        $registration = $this->registrationNode($result);

        return [
            'domain' => $domain,
            'status' => isset($registration['status']) ? (string) $registration['status'] : null,
            'expiry_date' => isset($registration['expires_at']) ? (string) $registration['expires_at'] : null,
            // Cloudflare Registrar domains always run on the account's Cloudflare
            // nameservers; the authoritative list lives on the zone.
            'nameservers' => array_values(array_filter(array_map('strval', (array) ($registration['name_servers'] ?? [])))),
        ];
    }

    /**
     * Cloudflare Registrar domains are locked to Cloudflare nameservers — there
     * is no API (and no dashboard option outside Business/Enterprise custom
     * nameservers) to point them elsewhere. Since this platform points every
     * domain at Cloudflare anyway, a request for Cloudflare nameservers is a
     * verified no-op rather than an error; anything else is rejected loudly so a
     * misconfiguration can never silently leave DNS pointing at the wrong host.
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        $domain = strtolower(trim($domain));
        $foreign = array_values(array_filter(
            array_map(fn ($ns) => strtolower(trim((string) $ns)), $nameservers),
            fn ($ns) => $ns !== '' && ! str_ends_with($ns, '.ns.cloudflare.com'),
        ));

        if ($foreign !== []) {
            throw new RegistrarException(
                "Cloudflare Registrar cannot point {$domain} at non-Cloudflare nameservers ("
                .implode(', ', $foreign).'). Cloudflare-registered domains must use Cloudflare nameservers; '
                .'transfer the domain to another registrar if external nameservers are required.',
                registrar: $this->name(),
                context: ['requested_nameservers' => $nameservers],
            );
        }

        return ['domain' => $domain, 'success' => true, 'no_op' => true];
    }

    /**
     * Connection / credential check for the `registrar:test` command. Lists the
     * account's registrations (page size 1) — the cheapest call that proves the
     * token, the account id and the Registrar scope are all correct.
     *
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        $this->command('get', '/registrations', ['per_page' => 1], 'ping');

        return [
            'status' => 'SUCCESS',
            'credentialsValid' => true,
            'account_id' => $this->accountId(),
        ];
    }

    /**
     * Adopt an existing registration if Cloudflare already holds this domain for
     * the account. Returns null when it does not (or cannot be read), so a
     * genuine first registration is never blocked by a transient read failure.
     *
     * @return array<string, mixed>|null
     */
    private function existingRegistration(string $domain): ?array
    {
        try {
            $result = $this->command('get', '/registrations/'.rawurlencode($domain), [], 'existing registration lookup', allowFailure: true);
        } catch (RegistrarException) {
            return null;
        }

        $registration = $this->registrationNode($result);

        return filled($registration['domain_name'] ?? null) ? $registration : null;
    }

    /**
     * Registration may return 201 (done) or 202 (queued). Poll the workflow to a
     * terminal state, bounded by config so a queue worker is never held open
     * indefinitely. A still-running workflow raises a retryable error rather than
     * reporting a success that has not happened.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function awaitCompletion(string $domain, array $result): array
    {
        $attempts = max(1, (int) config('domain.cloudflare.registration_poll_attempts', 5));
        $interval = max(0, (int) config('domain.cloudflare.registration_poll_seconds', 3));

        for ($attempt = 1; ; $attempt++) {
            $state = strtolower((string) ($result['state'] ?? ''));
            $completed = filter_var($result['completed'] ?? false, FILTER_VALIDATE_BOOL);

            if ($completed || $state === self::STATE_SUCCEEDED) {
                return $this->registrationNode($result, $result);
            }

            if ($state !== '' && $state !== self::STATE_IN_PROGRESS) {
                // failed | action_required | blocked — terminal and not success.
                throw new RegistrarException(
                    "Cloudflare registration for {$domain} ended in state '{$state}'.".$this->stateDetail($result),
                    registrar: $this->name(),
                    context: Secrets::redactArray($result),
                );
            }

            if ($attempt >= $attempts) {
                break;
            }

            if ($interval > 0) {
                sleep($interval);
            }

            $result = $this->command(
                'get',
                '/registrations/'.rawurlencode($domain).'/registration-status',
                [],
                'registration status',
            );
        }

        throw new RegistrarException(
            "Cloudflare registration for {$domain} is still in progress after {$attempts} status checks. "
            .'The charge may already have been made — retry this step to pick the registration up once Cloudflare finishes '
            .'(the retry adopts the existing registration instead of buying the domain again).',
            registrar: $this->name(),
            context: Secrets::redactArray($result),
        );
    }

    /** Human-readable detail attached to a non-successful workflow state. */
    private function stateDetail(array $result): string
    {
        $context = is_array($result['context'] ?? null) ? $result['context'] : [];
        $detail = $context['message'] ?? $context['reason'] ?? $result['message'] ?? null;

        return filled($detail) ? ' '.Secrets::redact((string) $detail) : '';
    }

    /**
     * Normalise a completed registration into the interface's result shape.
     *
     * @param  array<string, mixed>  $registration
     * @return array{domain: string, success: bool, registrar: string, registrar_domain_id: ?string, registrar_order_id: ?string, order_amount: ?string, expiry_date: ?string}
     */
    private function registrationResult(string $domain, array $registration): array
    {
        return [
            'domain' => $domain,
            'success' => true,
            'registrar' => $this->name(),
            // Cloudflare keys registrations by domain name; there is no separate id.
            'registrar_domain_id' => (string) ($registration['domain_name'] ?? $domain),
            'registrar_order_id' => isset($registration['id']) ? (string) $registration['id'] : null,
            'order_amount' => null,
            'expiry_date' => isset($registration['expires_at']) ? (string) $registration['expires_at'] : null,
        ];
    }

    /**
     * The registration object lives at result.context.registration on workflow
     * responses and at the top level on the resource endpoint.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>|null  $fallback
     * @return array<string, mixed>
     */
    private function registrationNode(array $result, ?array $fallback = null): array
    {
        $context = is_array($result['context'] ?? null) ? $result['context'] : [];

        if (is_array($context['registration'] ?? null)) {
            return $context['registration'];
        }

        if (filled($result['domain_name'] ?? null) && isset($result['status'])) {
            return $result;
        }

        return $fallback ?? $result;
    }

    /**
     * Map the platform's registrant contact onto Cloudflare's contact schema.
     * Returns null when no contact was supplied, in which case Cloudflare uses
     * the account's configured registrant.
     *
     * @param  array<string, mixed>  $contact
     * @return array<string, mixed>|null
     */
    private function registrantContact(array $contact): ?array
    {
        if ($contact === []) {
            return null;
        }

        $name = trim(($contact['first_name'] ?? '').' '.($contact['last_name'] ?? ''));
        $street = trim(implode(', ', array_filter([
            $contact['address_line_1'] ?? null,
            $contact['address_line_2'] ?? null,
        ])));

        return array_filter([
            'email' => $contact['email'] ?? null,
            'phone' => $contact['phone'] ?? null,
            'postal_info' => array_filter([
                'name' => $name !== '' ? $name : null,
                'organization' => $contact['company_name'] ?? null,
                'address' => array_filter([
                    'street' => $street !== '' ? $street : null,
                    'city' => $contact['city'] ?? null,
                    'state' => $contact['state'] ?? null,
                    'postal_code' => $contact['postcode'] ?? null,
                    'country_code' => filled($contact['country'] ?? null) ? strtoupper((string) $contact['country']) : null,
                ], fn ($v) => $v !== null && $v !== ''),
            ], fn ($v) => $v !== null && $v !== []),
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * Pull one domain's node out of a domain-check result.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function domainNode(array $result, string $domain): array
    {
        foreach ((array) ($result['domains'] ?? []) as $node) {
            if (is_array($node) && strtolower((string) ($node['name'] ?? '')) === strtolower($domain)) {
                return $node;
            }
        }

        return [];
    }

    /**
     * Issue a Cloudflare Registrar API request and return the `result` node,
     * asserting the standard {success, errors, result} envelope succeeded.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function command(string $method, string $path, array $payload, string $label, bool $allowFailure = false): array
    {
        $config = (array) config('domain.cloudflare');
        $token = $config['api_token'] ?? null;
        $accountId = $this->accountId();

        if (blank($token)) {
            throw new RegistrarException(
                'Cloudflare Registrar API token is not configured. Set CLOUDFLARE_REGISTRAR_API_TOKEN (or CLOUDFLARE_API_TOKEN) in the server .env.',
                registrar: $this->name(),
            );
        }

        if (blank($accountId)) {
            throw new RegistrarException(
                'Cloudflare account id is not configured. Set CLOUDFLARE_ACCOUNT_ID in the server .env.',
                registrar: $this->name(),
            );
        }

        $endpoint = rtrim((string) ($config['endpoint'] ?? 'https://api.cloudflare.com/client/v4'), '/');
        $url = $endpoint.'/accounts/'.$accountId.'/registrar'.$path;

        try {
            $client = $this->client($token);
            $response = match ($method) {
                'get' => $client->get($url, $payload),
                'patch' => $client->patch($url, $payload),
                'delete' => $client->delete($url),
                default => $client->post($url, $payload),
            };
        } catch (Throwable $e) {
            $safe = Secrets::redact($e->getMessage());
            Log::channel('stack')->warning('Cloudflare Registrar request failed', ['path' => $path, 'error' => $safe]);

            throw new RegistrarException("Cloudflare Registrar {$label} request error: {$safe}", registrar: $this->name(), previous: $e);
        }

        $json = is_array($response->json()) ? $response->json() : [];

        if ($response->failed()) {
            if ($allowFailure) {
                return [];
            }

            $detail = $this->firstError($json) ?: trim(Secrets::redact($response->body()));
            $detail = $detail !== '' ? $detail : '<no response body>';
            $hint = $this->parser->cloudflareHint($detail, $response->status());

            Log::channel('stack')->warning('Cloudflare Registrar '.$label.' rejected', [
                'path' => $path,
                'status' => $response->status(),
                'reason' => $detail,
            ]);

            throw new RegistrarException(
                "Cloudflare Registrar {$label} HTTP {$response->status()}: {$detail}".($hint ? " — {$hint}" : ''),
                registrar: $this->name(),
                context: Secrets::redactArray($json ?: ['status' => $response->status()]),
            );
        }

        return $this->parser->cloudflareReply($json, $label);
    }

    private function client(string $token): PendingRequest
    {
        return Http::withToken($token)
            ->acceptJson()
            ->timeout((int) config('domain.request_timeout', 30));
    }

    private function accountId(): ?string
    {
        $accountId = config('domain.cloudflare.account_id');

        return filled($accountId) ? (string) $accountId : null;
    }

    /** @param array<string, mixed> $json */
    private function firstError(array $json): string
    {
        $error = $json['errors'][0] ?? null;

        if (is_array($error)) {
            $message = trim((string) ($error['message'] ?? ''));
            $code = $error['code'] ?? null;

            return $message !== '' ? Secrets::redact($message).($code ? " (code {$code})" : '') : '';
        }

        return '';
    }

    /** Normalise a TLD or full domain ("example.co.uk") to its registrable TLD ("co.uk"). */
    private function tldOf(string $tldOrDomain): string
    {
        $value = ltrim(strtolower(trim($tldOrDomain)), '.');

        return str_contains($value, '.') ? DomainName::parse($value)->tld : $value;
    }
}
