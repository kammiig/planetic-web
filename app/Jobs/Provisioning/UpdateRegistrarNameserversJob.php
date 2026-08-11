<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Exceptions\ProvisioningException;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Registrar\RegistrarResolver;

/**
 * Points the domain's registrar nameservers at Cloudflare (Ticket 29). Does
 * not run until Cloudflare has issued nameservers. For domains registered
 * through Cloudflare Registrar this is a verified no-op — those domains are
 * locked to Cloudflare nameservers already.
 */
class UpdateRegistrarNameserversJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::UpdateNameservers;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $domain = $order->domain()->with('cloudflareZone')->first();

        if (! $domain || ! $domain->cloudflareZone) {
            throw new ProvisioningException('Cannot update nameservers before the Cloudflare zone exists.');
        }

        $nameservers = $domain->cloudflareZone->name_servers ?? [];

        if (empty($nameservers)) {
            throw new ProvisioningException('Cloudflare has not yet assigned nameservers for this domain.');
        }

        // Safe test mode: record the nameservers without calling the registrar.
        if (config('provisioning.dry_run', false)) {
            $domain->update(['nameservers' => $nameservers, 'last_synced_at' => now()]);

            return ['simulated' => true, 'nameservers' => $nameservers];
        }

        // Target the registrar that actually holds the domain, not the current
        // default: a .co.uk outside Cloudflare's API beta is registered at
        // Porkbun, and its nameservers must be set there. Cloudflare-registered
        // domains are already on Cloudflare nameservers, so their registrar
        // reports this as a verified no-op.
        $registrar = app(RegistrarResolver::class)->forDomain($domain);
        $result = $registrar->updateNameservers($domain->domain_name, $nameservers);

        $domain->update([
            'nameservers' => $nameservers,
            'last_synced_at' => now(),
        ]);

        return $result;
    }
}
