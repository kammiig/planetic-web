<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Exceptions\CloudflareException;
use App\Exceptions\ProvisioningException;
use App\Models\CloudflareZone;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\DNS\CloudflareService;

/**
 * Creates the Cloudflare zone for the order's domain (Ticket 28). Idempotent:
 * an existing zone (ours) is reused rather than duplicated.
 */
class CreateCloudflareZoneJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::CreateCloudflareZone;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $domain = $order->domain()->first();

        if (! $domain) {
            throw new ProvisioningException('Cannot create a Cloudflare zone before the domain exists.');
        }

        if (filled($domain->cloudflare_zone_id)) {
            return ['skipped' => true, 'reason' => 'zone_exists'];
        }

        $cloudflare = app(CloudflareService::class);

        try {
            $zone = $cloudflare->createZone($domain->domain_name);
        } catch (CloudflareException $e) {
            if ($e->zoneExists) {
                $zone = $cloudflare->findZoneByName($domain->domain_name);
                if (! $zone) {
                    // Exists but not under our account — needs human review.
                    throw new ProvisioningException('Cloudflare zone already exists and could not be reused.', manualReview: true);
                }
            } else {
                throw $e;
            }
        }

        $record = CloudflareZone::updateOrCreate(
            ['zone_id' => $zone['id']],
            [
                'user_id' => $order->user_id,
                'domain_id' => $domain->id,
                'zone_name' => $zone['name'],
                'status' => $zone['status'],
                'name_servers' => $zone['name_servers'],
                'ssl_mode' => config('cloudflare.default_ssl_mode', 'full'),
                'always_use_https' => config('cloudflare.always_use_https', true),
                'created_on_cloudflare_at' => now(),
                'last_synced_at' => now(),
            ],
        );

        $domain->update([
            'cloudflare_zone_id' => $record->id,
            'nameservers' => $zone['name_servers'],
        ]);

        // Best-effort security settings — do not fail the step on these.
        try {
            $cloudflare->setSslMode($zone['id'], config('cloudflare.default_ssl_mode', 'full'));
            if (config('cloudflare.always_use_https', true)) {
                $cloudflare->enableAlwaysUseHttps($zone['id']);
            }
        } catch (CloudflareException) {
            // Non-critical; the zone is created.
        }

        return ['zone_id' => $zone['id'], 'name_servers' => $zone['name_servers']];
    }
}
