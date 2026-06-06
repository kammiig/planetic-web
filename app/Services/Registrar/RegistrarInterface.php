<?php

namespace App\Services\Registrar;

/**
 * Registrar abstraction so the platform is never locked into one provider.
 * NameSilo is primary, Namecheap is the backup — selected via the
 * DOMAIN_REGISTRAR env var (config/domain.php). Every method returns a
 * normalised array (shaped by RegistrarResponseParser); raw provider errors
 * are converted to RegistrarException and never surfaced to customers.
 */
interface RegistrarInterface
{
    /**
     * @return array{domain: string, available: bool, premium: bool, price: ?string, currency: string}
     */
    public function checkAvailability(string $domain): array;

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

    /** Identifier stored on the domain record, e.g. "namesilo". */
    public function name(): string;
}
