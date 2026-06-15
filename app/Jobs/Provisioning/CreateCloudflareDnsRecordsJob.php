<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Exceptions\CloudflareException;
use App\Exceptions\ProvisioningException;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\DNS\CloudflareService;
use App\Services\DNS\DefaultDnsRecordBuilder;

/**
 * Creates the default DNS record set in Cloudflare (Ticket 32). Website
 * records are proxied; mail/cPanel records stay DNS-only. Idempotent: a
 * record already present (by type+name) is not recreated on retry.
 */
class CreateCloudflareDnsRecordsJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::CreateDnsRecords;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $domain = $order->domain()->with('cloudflareZone')->first();

        if (! $domain || ! $domain->cloudflareZone) {
            throw new ProvisioningException('Cannot create DNS records before the Cloudflare zone exists.');
        }

        $dryRun = (bool) config('provisioning.dry_run', false);

        // The WHM-assigned IP on the hosting account is authoritative — it is
        // the server the account actually lives on. config('whm.server_ip') is
        // only a fallback for domain-only edge cases; relying on it for a
        // hosting order produced records pointing at the wrong server.
        $serverIp = $order->hostingAccount()->value('server_ip') ?: config('whm.server_ip');

        if (blank($serverIp)) {
            // In dry-run there may be no real server IP configured; records are
            // only simulated, so a placeholder is fine. In live mode a missing
            // server IP is a genuine config error worth surfacing.
            if ($dryRun) {
                $serverIp = '127.0.0.1';
            } else {
                throw new ProvisioningException('No server IP available for DNS records — the hosting account has no assigned IP yet.');
            }
        }

        $zone = $domain->cloudflareZone;
        $cloudflare = app(CloudflareService::class);
        $records = app(DefaultDnsRecordBuilder::class)->build($domain->domain_name, $serverIp);
        $dryRun = (bool) config('provisioning.dry_run', false);

        $created = 0;
        $updated = 0;
        foreach ($records as $i => $record) {
            // Identity of a logical record. Only MX repeats on the same name
            // (3 exchangers on '@'), so MX is keyed by content too; everything
            // else (A/CNAME/SPF/DMARC) is one-per-name, so its content (e.g. the
            // server IP) UPDATES in place rather than spawning a duplicate.
            $matchAttrs = ['type' => $record['type'], 'name' => $record['name']];
            if ($record['type'] === 'MX') {
                $matchAttrs['content'] = $record['content'];
            }

            $local = $zone->dnsRecords()->where($matchAttrs)->first();

            $payload = [
                'type' => $record['type'],
                'name' => $record['name'] === '@' ? $domain->domain_name : $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'],
            ];
            if (isset($record['priority'])) {
                $payload['priority'] = $record['priority'];
            }

            $attributes = [
                'user_id' => $order->user_id,
                'cloudflare_zone_id' => $zone->id,
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'],
                'priority' => $record['priority'] ?? null,
                'status' => 'active',
            ];

            // Safe test mode: persist locally without calling Cloudflare.
            if ($dryRun) {
                $domain->dnsRecords()->updateOrCreate($matchAttrs, $attributes + [
                    'cloudflare_record_id' => $local?->cloudflare_record_id ?: 'dry-run-'.$record['type'].'-'.$i,
                ]);
                $local ? $updated++ : $created++;

                continue;
            }

            try {
                if ($local && filled($local->cloudflare_record_id)) {
                    // Already in Cloudflare → update it (keeps content/proxy in sync).
                    $cloudflare->updateDnsRecord($zone->zone_id, $local->cloudflare_record_id, $payload);
                    $local->update($attributes);
                    $updated++;

                    continue;
                }

                $cfRecord = $cloudflare->createDnsRecord($zone->zone_id, $payload);
            } catch (CloudflareException $e) {
                // One bad record shouldn't abort the rest; record it and move on.
                $domain->dnsRecords()->updateOrCreate($matchAttrs, $attributes + ['status' => 'failed']);

                continue;
            }

            $domain->dnsRecords()->updateOrCreate($matchAttrs, $attributes + ['cloudflare_record_id' => $cfRecord['id']]);
            $created++;
        }

        return ['records_created' => $created, 'records_updated' => $updated];
    }
}
