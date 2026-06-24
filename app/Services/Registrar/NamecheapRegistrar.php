<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SimpleXMLElement;
use Throwable;

/**
 * Backup registrar. Namecheap's API is GET-based and returns XML. The most
 * important operation for this platform is setting custom nameservers to
 * Cloudflare; registration/renewal are also supported.
 */
class NamecheapRegistrar implements RegistrarInterface
{
    public function name(): string
    {
        return 'namecheap';
    }

    public function checkAvailability(string $domain): array
    {
        $xml = $this->request('namecheap.domains.check', ['DomainList' => strtolower($domain)], 'availability check');
        $result = $xml->CommandResponse->DomainCheckResult ?? null;

        if ($result === null) {
            throw new RegistrarException("Namecheap availability for {$domain} was indeterminate.", registrar: 'namecheap');
        }

        $available = ((string) $result['Available']) === 'true';
        $premium = ((string) $result['IsPremiumName']) === 'true';

        return [
            'domain' => strtolower($domain),
            'available' => $available,
            'premium' => $premium,
            'price' => $premium ? (string) $result['PremiumRegistrationPrice'] : null,
            'currency' => 'USD',
        ];
    }

    public function getPricing(string $tld): array
    {
        $tld = strtolower(trim($tld));
        $tld = str_contains($tld, '.') ? substr($tld, strpos($tld, '.') + 1) : ltrim($tld, '.');

        // Namecheap's pricing API (namecheap.users.getPricing) is not wired —
        // the platform prices domains from its own GBP catalogue. Reported as
        // unsupported so callers fall back cleanly ("getPricing if available").
        return ['tld' => $tld, 'registration' => null, 'renewal' => null, 'transfer' => null, 'currency' => 'USD', 'supported' => false];
    }

    public function registerDomain(array $data): array
    {
        [$sld, $tld] = $this->splitDomain($data['domain']);
        $contact = $data['contact'] ?? [];

        $params = array_merge([
            'DomainName' => strtolower($data['domain']),
            'Years' => $data['years'] ?? config('domain.defaults.years', 1),
            'AddFreeWhoisguard' => ! empty($data['whois_privacy']) ? 'yes' : 'no',
            'WGEnabled' => ! empty($data['whois_privacy']) ? 'yes' : 'no',
        ], $this->contactParams($contact));

        if (! empty($data['nameservers'])) {
            $params['Nameservers'] = implode(',', $data['nameservers']);
        }

        $xml = $this->request('namecheap.domains.create', $params, 'domain registration');
        $result = $xml->CommandResponse->DomainCreateResult ?? null;

        return [
            'domain' => strtolower($data['domain']),
            'success' => $result !== null && ((string) $result['Registered']) === 'true',
            'registrar_domain_id' => $result ? (string) $result['DomainID'] : null,
            'registrar_order_id' => $result ? (string) $result['OrderID'] : null,
            'order_amount' => $result ? (string) $result['ChargedAmount'] : null,
            'expiry_date' => null,
        ];
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        $xml = $this->request('namecheap.domains.renew', [
            'DomainName' => strtolower($domain),
            'Years' => $years,
        ], 'domain renewal');
        $result = $xml->CommandResponse->DomainRenewResult ?? null;

        return [
            'domain' => strtolower($domain),
            'success' => $result !== null && ((string) $result['Renew']) === 'true',
            'order_amount' => $result ? (string) $result['ChargedAmount'] : null,
            'expiry_date' => $result ? (string) ($result->DomainDetails->ExpiredDate ?? '') ?: null : null,
        ];
    }

    public function getDomainInfo(string $domain): array
    {
        $xml = $this->request('namecheap.domains.getInfo', ['DomainName' => strtolower($domain)], 'domain info');
        $result = $xml->CommandResponse->DomainGetInfoResult ?? null;

        $nameservers = [];
        if ($result && isset($result->DnsDetails->Nameserver)) {
            foreach ($result->DnsDetails->Nameserver as $ns) {
                $nameservers[] = (string) $ns;
            }
        }

        return [
            'domain' => strtolower($domain),
            'status' => $result ? (string) $result['Status'] : null,
            'expiry_date' => $result ? (string) ($result->DomainDetails->ExpiredDate ?? '') ?: null : null,
            'nameservers' => $nameservers,
        ];
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        [$sld, $tld] = $this->splitDomain($domain);

        $xml = $this->request('namecheap.domains.dns.setCustom', [
            'SLD' => $sld,
            'TLD' => $tld,
            'NameServers' => implode(',', $nameservers),
        ], 'nameserver update');

        $result = $xml->CommandResponse->DomainDNSSetCustomResult ?? null;

        return [
            'domain' => strtolower($domain),
            'success' => $result !== null && ((string) $result['Updated']) === 'true',
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function request(string $command, array $params, string $label): SimpleXMLElement
    {
        $config = config('domain.namecheap');

        foreach (['api_user', 'api_key', 'username', 'client_ip', 'endpoint'] as $required) {
            if (blank($config[$required] ?? null)) {
                throw new RegistrarException("Namecheap config '{$required}' is missing.", registrar: 'namecheap');
            }
        }

        $query = array_merge([
            'ApiUser' => $config['api_user'],
            'ApiKey' => $config['api_key'],
            'UserName' => $config['username'],
            'ClientIp' => $config['client_ip'],
            'Command' => $command,
        ], $params);

        try {
            $response = Http::timeout(config('domain.request_timeout', 30))->get($config['endpoint'], $query);
        } catch (Throwable $e) {
            throw new RegistrarException("Namecheap {$label} request error: {$e->getMessage()}", registrar: 'namecheap', previous: $e);
        }

        if ($response->failed()) {
            throw new RegistrarException("Namecheap {$label} HTTP {$response->status()}.", registrar: 'namecheap');
        }

        $xml = @simplexml_load_string($response->body());
        if ($xml === false) {
            throw new RegistrarException("Namecheap {$label}: invalid XML response.", registrar: 'namecheap');
        }

        if (((string) $xml['Status']) !== 'OK') {
            $error = isset($xml->Errors->Error) ? (string) $xml->Errors->Error : 'unknown error';
            throw new RegistrarException("Namecheap {$label} failed: {$error}", registrar: 'namecheap', context: $error);
        }

        return $xml;
    }

    /** @return array{0: string, 1: string} */
    private function splitDomain(string $domain): array
    {
        $domain = strtolower($domain);
        $dot = strpos($domain, '.');

        return [Str::before($domain, '.'), substr($domain, $dot + 1)];
    }

    /** @param array<string, mixed> $contact @return array<string, mixed> */
    private function contactParams(array $contact): array
    {
        $map = [
            'FirstName' => 'first_name', 'LastName' => 'last_name', 'Address1' => 'address_line_1',
            'City' => 'city', 'StateProvince' => 'state', 'PostalCode' => 'postcode',
            'Country' => 'country', 'Phone' => 'phone', 'EmailAddress' => 'email',
        ];

        $params = [];
        foreach (['Registrant', 'Tech', 'Admin', 'AuxBilling'] as $type) {
            foreach ($map as $ncField => $localField) {
                if (! empty($contact[$localField])) {
                    $params[$type.$ncField] = $contact[$localField];
                }
            }
        }

        return $params;
    }
}
