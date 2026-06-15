<?php

namespace App\Console\Commands;

use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use App\Jobs\Provisioning\CreateCloudflareDnsRecordsJob;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Re-syncs a domain's Cloudflare DNS records to the hosting account's current
 * server IP. Repairs records created with a stale/wrong IP (the create step is
 * idempotent and updates existing records in place — no duplicates).
 *
 *   php artisan dns:resync ORD-10022
 *   php artisan dns:resync --all          # every active hosting account
 */
class ResyncDnsCommand extends Command
{
    protected $signature = 'dns:resync
        {order? : Order number (ORD-xxxxx) or numeric id}
        {--all : Re-sync every order that has a hosting account and a Cloudflare zone}';

    protected $description = 'Repair Cloudflare DNS records to match the hosting account server IP (idempotent).';

    public function handle(): int
    {
        $orders = $this->resolveOrders();

        if ($orders->isEmpty()) {
            $this->error($this->option('all')
                ? 'No orders with both a hosting account and a Cloudflare zone were found.'
                : "Order '{$this->argument('order')}' not found, or it has no Cloudflare zone.");

            return self::FAILURE;
        }

        foreach ($orders as $order) {
            $this->resync($order);
        }

        return self::SUCCESS;
    }

    private function resync(Order $order): void
    {
        $ip = $order->hostingAccount()->value('server_ip');
        $this->components->info("Order {$order->order_number} — domain {$order->primaryDomainName()}, server IP ".($ip ?: 'unknown'));

        if (blank($ip)) {
            $this->warn('  No server IP on the hosting account yet — skipping (run orders:provision first).');

            return;
        }

        // Reset the step so the (idempotent) job re-runs and updates each
        // record's content to the current IP rather than skipping as "done".
        $order->provisioningJobs()
            ->where('job_type', ProvisioningJobType::CreateDnsRecords->value)
            ->update(['status' => ProvisioningStatus::Pending->value, 'error_message' => null, 'failed_at' => null]);

        try {
            CreateCloudflareDnsRecordsJob::dispatchSync($order->id);
            $this->line('  DNS records re-synced to '.$ip.'.');
        } catch (Throwable $e) {
            $this->error('  DNS re-sync failed: '.$e->getMessage());
        }
    }

    private function resolveOrders(): Collection
    {
        if ($this->option('all')) {
            return Order::query()
                ->whereHas('hostingAccount')
                ->whereHas('domain.cloudflareZone')
                ->with('items')
                ->get();
        }

        $key = (string) $this->argument('order');

        if (blank($key)) {
            $this->error('Provide an order number (e.g. ORD-10022) or use --all.');

            return collect();
        }

        return Order::query()
            ->where('order_number', $key)
            ->when(is_numeric($key), fn ($q) => $q->orWhere('id', (int) $key))
            ->whereHas('domain.cloudflareZone')
            ->with('items')
            ->get();
    }
}
