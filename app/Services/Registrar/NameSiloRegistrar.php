<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Support\DomainName;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Primary registrar. NameSilo's API is GET-based and returns JSON when
 * type=json. The API key is read only from config (env) and never exposed.
 */
class NameSiloRegistrar implements RegistrarInterface
{
    public function __construct(
        private readonly RegistrarResponseParser $parser,
    ) {}

    public function name(): string
    {
        return 'namesilo';
    }

    public function checkAvailability(string $domain): array
    {
        $reply = $this->request('checkRegisterAvailability', ['domains' => strtolower($domain)], 'availability check');

        return $this->parser->nameSiloAvailability($reply, $domain);
    }

    public function getPricing(string $tld): array
    {
        $tld = $this->tldOf($tld);
        $reply = $this->request('getPrices', [], 'pricing');
        $row = $reply[$tld] ?? null;

        if (! is_array($row)) {
            return ['tld' => $tld, 'registration' => null, 'renewal' => null, 'transfer' => null, 'currency' => 'USD', 'supported' => false];
        }

        return [
            'tld' => $tld,
            'registration' => $this->scalar($row['registration'] ?? null),
            // NameSilo names the renewal price "renew".
            'renewal' => $this->scalar($row['renew'] ?? $row['renewal'] ?? null),
            'transfer' => $this->scalar($row['transfer'] ?? null),
            'currency' => 'USD',
            'supported' => true,
        ];
    }

    /** Normalise a TLD or full domain to its registrable TLD. */
    private function tldOf(string $tldOrDomain): string
    {
        $value = ltrim(strtolower(trim($tldOrDomain)), '.');

        return str_contains($value, '.') ? DomainName::parse($value)->tld : $value;
    }

    /** A price field may be a scalar or a per-year list; reduce to a string. */
    private function scalar(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return $value === null || $value === false ? null : (string) $value;
    }

    public function registerDomain(array $data): array
    {
        $params = [
            'domain' => strtolower($data['domain']),
            'years' => $data['years'] ?? config('domain.defaults.years', 1),
            'private' => ! empty($data['whois_privacy']) ? 1 : 0,
            'auto_renew' => ! empty($data['auto_renew']) ? 1 : 0,
        ];

        // Inline registrant contact (NameSilo also supports a stored contact_id).
        if (isset($data['contact_id'])) {
            $params['contact_id'] = $data['contact_id'];
        } elseif (isset($data['contact'])) {
            $params = array_merge($params, $this->mapContact($data['contact']));
        }

        if (! empty($data['nameservers'])) {
            foreach (array_values($data['nameservers']) as $i => $ns) {
                $params['ns'.($i + 1)] = $ns;
            }
        }

        $reply = $this->request('registerDomain', $params, 'domain registration');

        return [
            'domain' => $params['domain'],
            'success' => true,
            'registrar_domain_id' => $reply['domain_id'] ?? null,
            'registrar_order_id' => isset($reply['order_id']) ? (string) $reply['order_id'] : null,
            'order_amount' => isset($reply['order_amount']) ? (string) $reply['order_amount'] : null,
            'expiry_date' => $this->extractExpiry($reply),
        ];
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        $reply = $this->request('renewDomain', [
            'domain' => strtolower($domain),
            'years' => $years,
        ], 'domain renewal');

        return [
            'domain' => strtolower($domain),
            'success' => true,
            'order_amount' => isset($reply['order_amount']) ? (string) $reply['order_amount'] : null,
            'expiry_date' => $this->extractExpiry($reply),
        ];
    }

    public function getDomainInfo(string $domain): array
    {
        $reply = $this->request('getDomainInfo', ['domain' => strtolower($domain)], 'domain info');

        $nameservers = [];
        $ns = $reply['nameservers']['nameserver'] ?? null;
        if (is_array($ns)) {
            $nameservers = array_values(array_filter(array_map(
                fn ($n) => is_array($n) ? ($n['nameserver'] ?? null) : $n,
                array_is_list($ns) ? $ns : [$ns],
            )));
        }

        return [
            'domain' => strtolower($domain),
            'status' => $reply['status'] ?? null,
            'expiry_date' => $this->extractExpiry($reply),
            'nameservers' => $nameservers,
        ];
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        $params = ['domain' => strtolower($domain)];
        foreach (array_values($nameservers) as $i => $ns) {
            $params['ns'.($i + 1)] = $ns;
        }

        $this->request('changeNameServers', $params, 'nameserver update');

        return ['domain' => strtolower($domain), 'success' => true];
    }

    /**
     * Issue a NameSilo API request and return the asserted reply node.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function request(string $operation, array $params, string $label): array
    {
        $config = config('domain.namesilo');
        $apiKey = $config['api_key'] ?? null;

        if (blank($apiKey)) {
            throw new RegistrarException('NameSilo API key is not configured.', registrar: 'namesilo');
        }

        $query = array_merge([
            'version' => 1,
            'type' => 'json',
            'key' => $apiKey,
        ], $params);

        try {
            $response = Http::timeout(config('domain.request_timeout', 30))
                ->acceptJson()
                ->get(rtrim($config['endpoint'], '/').'/'.$operation, $query);
        } catch (Throwable $e) {
            Log::channel('stack')->warning('NameSilo request failed', ['operation' => $operation, 'error' => $e->getMessage()]);
            throw new RegistrarException("NameSilo {$label} request error: {$e->getMessage()}", registrar: 'namesilo', previous: $e);
        }

        if ($response->failed()) {
            throw new RegistrarException("NameSilo {$label} HTTP {$response->status()}.", registrar: 'namesilo', context: $response->body());
        }

        return $this->parser->nameSiloReply($response->json() ?? [], $operation);
    }

    /** @param array<string, mixed> $contact @return array<string, mixed> */
    private function mapContact(array $contact): array
    {
        return array_filter([
            'fn' => $contact['first_name'] ?? null,
            'ln' => $contact['last_name'] ?? null,
            'cp' => $contact['company_name'] ?? null,
            'ad' => $contact['address_line_1'] ?? null,
            'cy' => $contact['city'] ?? null,
            'st' => $contact['state'] ?? null,
            'zp' => $contact['postcode'] ?? null,
            'ct' => $contact['country'] ?? null,
            'em' => $contact['email'] ?? null,
            'ph' => $contact['phone'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /** @param array<string, mixed> $reply */
    private function extractExpiry(array $reply): ?string
    {
        $expiry = $reply['expires'] ?? $reply['expiry'] ?? $reply['renew_date'] ?? null;

        return $expiry ? (string) $expiry : null;
    }
}
