<?php

namespace App\Services\Registrar;

use App\Models\Domain;
use App\Providers\IntegrationServiceProvider;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a registrar client by name.
 *
 * Post-registration operations (nameserver updates, info syncs, renewals) must
 * talk to the registrar that ACTUALLY holds the domain, which is not always the
 * configured default: FallbackRegistrar routes TLDs outside Cloudflare's API
 * beta to Porkbun, and the default can be changed at any time in admin while
 * older domains stay where they were registered. Resolving from the domain
 * record keeps those operations pointed at the right provider.
 */
class RegistrarResolver
{
    public function __construct(private readonly Container $container) {}

    /** The configured default (already wrapped in fallback routing). */
    public function default(): RegistrarInterface
    {
        return $this->container->make(RegistrarInterface::class);
    }

    /**
     * A specific registrar by name, without fallback wrapping. Unknown names
     * fall back to the configured default so a legacy or hand-edited value can
     * never crash provisioning.
     */
    public function named(?string $name): RegistrarInterface
    {
        if (! in_array($name, IntegrationServiceProvider::REGISTRARS, true)) {
            return $this->default();
        }

        return match ($name) {
            'cloudflare' => $this->container->make(CloudflareRegistrar::class),
            'porkbun' => $this->container->make(PorkbunRegistrar::class),
            'namesilo' => $this->container->make(NameSiloRegistrar::class),
            default => $this->container->make(NamecheapRegistrar::class),
        };
    }

    /** The registrar that holds this domain. */
    public function forDomain(Domain $domain): RegistrarInterface
    {
        return $this->named($domain->registrar);
    }
}
