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

        $serverIp = $order->hostingAccount()->value('server_ip') ?: config('whm.server_ip');

        if (blank($serverIp)) {
            throw new ProvisioningException('No server IP available for DNS records.');
        }

        $zone = $domain->cloudflareZone;
        $cloudflare = app(CloudflareService::class);
        $records = app(DefaultDnsRecordBuilder::class)->build($domain->domain_name, $serverIp);

        $created = 0;
        foreach ($records as $record) {
            // Skip records we have already created (idempotent on retry).
            $exists = $zone->dnsRecords()
                ->where('type', $record['type'])
                ->where('name', $record['name'])
                ->exists();

            if ($exists) {
                continue;
            }

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

            // Safe test mode: persist the record locally without calling Cloudflare.
            if (config('provisioning.dry_run', false)) {
                $domain->dnsRecords()->create([
                    'user_id' => $order->user_id,
                    'cloudflare_zone_id' => $zone->id,
                    'cloudflare_record_id' => 'dry-run-'.$record['type'].'-'.$created,
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'content' => $record['content'],
                    'ttl' => $record['ttl'],
                    'proxied' => $record['proxied'],
                    'priority' => $record['priority'] ?? null,
                    'status' => 'active',
                ]);
                $created++;

                continue;
            }

            try {
                $cfRecord = $cloudflare->createDnsRecord($zone->zone_id, $payload);
            } catch (CloudflareException $e) {
                // One bad record shouldn't abort the rest; record it and move on.
                $domain->dnsRecords()->create([
                    'user_id' => $order->user_id,
                    'cloudflare_zone_id' => $zone->id,
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'content' => $record['content'],
                    'ttl' => $record['ttl'],
                    'proxied' => $record['proxied'],
                    'priority' => $record['priority'] ?? null,
                    'status' => 'failed',
                ]);

                continue;
            }

            $domain->dnsRecords()->create([
                'user_id' => $order->user_id,
                'cloudflare_zone_id' => $zone->id,
                'cloudflare_record_id' => $cfRecord['id'],
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'],
                'priority' => $record['priority'] ?? null,
                'status' => 'active',
            ]);
            $created++;
        }

        return ['records_created' => $created];
    }
}
