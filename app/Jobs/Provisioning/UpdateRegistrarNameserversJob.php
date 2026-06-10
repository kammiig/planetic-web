<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Exceptions\ProvisioningException;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Registrar\RegistrarInterface;

/**
 * Points the domain's registrar nameservers at Cloudflare (Ticket 29). Does
 * not run until Cloudflare has issued nameservers.
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

        $registrar = app(RegistrarInterface::class);
        $result = $registrar->updateNameservers($domain->domain_name, $nameservers);

        $domain->update([
            'nameservers' => $nameservers,
            'last_synced_at' => now(),
        ]);

        return $result;
    }
}
