<?php

namespace App\Console\Commands;

use App\Models\CloudflareZone;
use App\Services\DNS\CloudflareStatusSync;
use Illuminate\Console\Command;
use Throwable;

/**
 * Refreshes Cloudflare zone DNS/SSL status from the Cloudflare API so the
 * customer dashboard reflects reality once nameservers point at Cloudflare.
 *
 *   php artisan cloudflare:sync                 # all not-yet-active zones
 *   php artisan cloudflare:sync --all           # every zone
 *   php artisan cloudflare:sync example.com     # one domain (repair)
 */
class SyncCloudflareStatusCommand extends Command
{
    protected $signature = 'cloudflare:sync {domain? : A specific domain to repair} {--all : Sync every zone, not just pending ones}';

    protected $description = 'Refresh Cloudflare zone DNS and Universal SSL status from the Cloudflare API.';

    public function handle(CloudflareStatusSync $sync): int
    {
        $query = CloudflareZone::query()->with('domain');

        if ($domain = $this->argument('domain')) {
            $query->where('zone_name', strtolower((string) $domain));
        } elseif (! $this->option('all')) {
            // Default: only the zones that still need verifying.
            $query->where('status', '!=', 'active');
        }

        $zones = $query->get();

        if ($zones->isEmpty()) {
            $this->info('No Cloudflare zones to sync.');

            return self::SUCCESS;
        }

        $active = 0;
        foreach ($zones as $zone) {
            try {
                if ($sync->sync($zone)) {
                    $active++;
                }
                $this->line(sprintf(
                    '  %s — DNS: %s, SSL: %s',
                    $zone->zone_name,
                    $zone->status,
                    $zone->ssl_status ?? 'pending',
                ));
            } catch (Throwable $e) {
                $this->warn("  {$zone->zone_name}: {$e->getMessage()}");
            }
        }

        $this->info("Synced {$zones->count()} zone(s); {$active} now active.");

        return self::SUCCESS;
    }
}
