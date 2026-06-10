<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Inspect the full provisioning state of an order without changing anything.
 *
 *   php artisan orders:debug ORD-10007
 */
class OrdersDebugCommand extends Command
{
    protected $signature = 'orders:debug {order : Order number (ORD-xxxxx) or numeric id}';

    protected $description = 'Show order payment + provisioning status and each linked service record.';

    public function handle(): int
    {
        $order = $this->resolveOrder($this->argument('order'));

        if (! $order) {
            $this->error("Order '{$this->argument('order')}' not found.");

            return self::FAILURE;
        }

        $order->load(['items', 'provisioningJobs', 'domain', 'hostingAccount', 'websiteProject', 'invoice', 'payments']);

        $this->components->info("Order {$order->order_number}");
        $this->table(['Field', 'Value'], [
            ['Order status', $order->status->value],
            ['Payment status', $order->payment_status->value],
            ['Paid at', (string) ($order->paid_at ?? '—')],
            ['Total', $order->currency.' '.number_format((float) $order->total, 2)],
            ['Stripe payment intent', $order->stripe_payment_intent_id ?? '—'],
            ['Stripe checkout session', $order->stripe_checkout_session_id ?? '—'],
            ['Invoice', $order->invoice?->invoice_number ?? '—'],
            ['Payments recorded', (string) $order->payments->count()],
            ['Items', $order->items->map(fn ($i) => $i->item_type->value.($i->domain_name ? " ({$i->domain_name})" : ''))->implode(', ')],
        ]);

        // Provisioning ledger.
        if ($order->provisioningJobs->isEmpty()) {
            $this->warn('No provisioning steps recorded yet.');
        } else {
            $this->line('');
            $this->components->info('Provisioning steps');
            $this->table(
                ['Step', 'Status', 'Attempts', 'Last error'],
                $order->provisioningJobs->map(fn ($j) => [
                    $j->job_type->value,
                    $j->status->value,
                    $j->attempts.'/'.$j->max_attempts,
                    Str::limit((string) $j->error_message, 60) ?: '—',
                ])->all(),
            );
        }

        // Service records.
        $this->line('');
        $this->components->info('Service records');
        $this->table(['Service', 'Present', 'Status', 'Detail'], [
            ['Domain', $order->domain ? 'yes' : 'no', $order->domain?->status->value ?? '—', $order->domain?->domain_name ?? '—'],
            ['Hosting', $order->hostingAccount ? 'yes' : 'no', $order->hostingAccount?->status->value ?? '—', $order->hostingAccount?->whm_username ?? '—'],
            ['Website project', $order->websiteProject ? 'yes' : 'no', $order->websiteProject?->status->value ?? '—', $order->websiteProject?->project_number ?? '—'],
        ]);

        // Last error across the ledger.
        $lastError = $order->provisioningJobs
            ->filter(fn ($j) => filled($j->error_message))
            ->sortByDesc('failed_at')
            ->first();

        if ($lastError) {
            $this->line('');
            $this->error("Last provisioning error ({$lastError->job_type->value}): {$lastError->error_message}");
        }

        if (! $order->isPaid()) {
            $this->line('');
            $this->warn('This order is NOT marked paid. Run: php artisan orders:provision '.$order->order_number);
        }

        return self::SUCCESS;
    }

    private function resolveOrder(string $key): ?Order
    {
        return Order::query()
            ->where('order_number', $key)
            ->when(is_numeric($key), fn ($q) => $q->orWhere('id', (int) $key))
            ->first();
    }
}
