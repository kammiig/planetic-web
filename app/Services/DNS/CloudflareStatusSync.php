<?php

namespace App\Services\DNS;

use App\Models\CloudflareZone;
use App\Models\Domain;

/**
 * Refreshes a Cloudflare zone's stored status (and Universal SSL status) from
 * the Cloudflare API. Once a customer points their registrar nameservers at
 * Cloudflare, the zone transitions to "active" on Cloudflare's side; this sync
 * brings our dashboard in line so DNS/SSL stop showing "waiting" indefinitely.
 */
class CloudflareStatusSync
{
    public function __construct(private readonly CloudflareService $cloudflare) {}

    /**
     * Pull the latest state for a zone and persist it. Returns true when the
     * zone is active after syncing. Network/API errors are swallowed (the
     * underlying calls allow failure) so a sync attempt never breaks a page.
     */
    public function sync(CloudflareZone $zone): bool
    {
        if (blank($zone->zone_id)) {
            return $zone->isActive();
        }

        $info = $this->cloudflare->getZone($zone->zone_id);

        if ($info !== null) {
            $zone->status = $info['status'];

            if (! empty($info['name_servers'])) {
                $zone->name_servers = $info['name_servers'];
            }

            // Universal SSL is provisioned once the zone is active.
            if ($info['status'] === 'active') {
                $zone->ssl_status = $this->cloudflare->getUniversalSslStatus($zone->zone_id) ?? 'active';
            }
        }

        $zone->last_synced_at = now();
        $zone->save();

        // Keep the domain's stored nameservers in step so the dashboard shows
        // the Cloudflare nameservers the registrar should be pointing at.
        if ($info !== null && ! empty($info['name_servers']) && $zone->domain) {
            $zone->domain->update(['nameservers' => $info['name_servers']]);
        }

        return $zone->isActive();
    }

    /** Convenience: sync the zone attached to a domain, if any. */
    public function syncDomain(Domain $domain): bool
    {
        $domain->loadMissing('cloudflareZone');

        return $domain->cloudflareZone ? $this->sync($domain->cloudflareZone) : false;
    }

    /**
     * Lazy refresh used on customer page views: only calls Cloudflare when the
     * zone is not yet active and has not been checked very recently, so opening
     * the page after nameserver propagation flips it to active without a wait.
     */
    public function refreshIfStale(?CloudflareZone $zone, int $minIntervalSeconds = 120): void
    {
        if (! $zone || $zone->isActive()) {
            return;
        }

        if ($zone->last_synced_at && $zone->last_synced_at->gt(now()->subSeconds($minIntervalSeconds))) {
            return;
        }

        try {
            $this->sync($zone);
        } catch (\Throwable) {
            // Never let a status refresh break the page.
        }
    }
}
