<?php

namespace App\Services\Registrar;

/**
 * Registrar abstraction so the platform is never locked into one provider.
 * Porkbun is the default/primary registrar; NameSilo and Namecheap remain
 * optional fallbacks — selected via the DEFAULT_REGISTRAR env var
 * (config/domain.php). Every method returns a normalised array (shaped by
 * RegistrarResponseParser); raw provider errors are converted to
 * RegistrarException and never surfaced to customers.
 */
interface RegistrarInterface
{
    /**
     * @return array{domain: string, available: bool, premium: bool, price: ?string, currency: string}
     */
    public function checkAvailability(string $domain): array;

    /**
     * Wholesale registrar pricing for a TLD (or the TLD of a full domain),
     * when the provider exposes it. `supported` is false when the registrar
     * has no pricing API. Amounts are the registrar's own currency (USD).
     *
     * @return array{tld: string, registration: ?string, renewal: ?string, transfer: ?string, currency: string, supported: bool}
     */
    public function getPricing(string $tld): array;

    /**
     * @param  array<string, mixed>  $data  domain, years, contact details, privacy flags
     * @return array{domain: string, success: bool, registrar_domain_id: ?string, registrar_order_id: ?string, order_amount: ?string, expiry_date: ?string}
     */
    public function registerDomain(array $data): array;

    /**
     * @return array{domain: string, success: bool, order_amount: ?string, expiry_date: ?string}
     */
    public function renewDomain(string $domain, int $years = 1): array;

    /**
     * @return array{domain: string, status: ?string, expiry_date: ?string, nameservers: array<int, string>}
     */
    public function getDomainInfo(string $domain): array;

    /**
     * @param  array<int, string>  $nameservers
     * @return array{domain: string, success: bool}
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /** Identifier stored on the domain record, e.g. "porkbun". */
    public function name(): string;
}
