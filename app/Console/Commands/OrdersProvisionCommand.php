<?php

namespace App\Console\Commands;

use App\Actions\Checkout\CompletePaidOrder;
use App\Actions\Provisioning\EnsureServiceRecords;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProvisioningStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\Order;
use App\Services\Billing\StripeService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Run (or re-run) provisioning for an order. Designed to rescue orders that got
 * stuck — e.g. a Stripe webhook that never arrived, or a provisioning step that
 * failed. Idempotent: it confirms payment, creates any missing service records,
 * and only completes the steps that are still outstanding.
 *
 *   php artisan orders:provision ORD-10007
 *   php artisan orders:provision ORD-10007 --mark-paid   # after verifying in Stripe
 *   php artisan orders:provision --stuck                 # batch self-heal (scheduled)
 */
class OrdersProvisionCommand extends Command
{
    protected $signature = 'orders:provision
        {order? : Order number (ORD-xxxxx) or numeric id}
        {--stuck : Provision every paid-but-incomplete order}
        {--mark-paid : Proceed even if Stripe cannot confirm payment (only after verifying in the Stripe dashboard)}';

    protected $description = 'Confirm payment and run outstanding provisioning steps for an order (idempotent).';

    public function handle(CompletePaidOrder $complete, EnsureServiceRecords $ensure, StripeService $stripe): int
    {
        $orders = $this->resolveOrders();

        if ($orders->isEmpty()) {
            $this->error($this->option('stuck')
                ? 'No paid-but-incomplete orders found.'
                : "Order '{$this->argument('order')}' not found.");

            return self::FAILURE;
        }

        foreach ($orders as $order) {
            $this->provision($order, $complete, $ensure, $stripe);
        }

        return self::SUCCESS;
    }

    private function provision(Order $order, CompletePaidOrder $complete, EnsureServiceRecords $ensure, StripeService $stripe): void
    {
        $this->line('');
        $this->components->info("Order {$order->order_number} — status {$order->status->value}, payment {$order->payment_status->value}");

        // 1. Not yet paid → confirm with Stripe. A missing webhook is the usual
        //    reason an order is stuck "pending".
        if (! $order->isPaid()) {
            $context = $stripe->findSucceededPayment($order);

            if ($context !== null) {
                $this->line('  Payment confirmed with Stripe.');
            } elseif ($this->option('mark-paid')) {
                $this->warn('  Could not confirm with Stripe — proceeding because --mark-paid was given.');
                $context = array_filter([
                    'payment_intent' => $order->stripe_payment_intent_id,
                    'session_id' => $order->stripe_checkout_session_id,
                ]);
            } else {
                $this->warn('  No successful Stripe charge found for this order.');
                $this->warn('  Verify it in the Stripe dashboard, then re-run with --mark-paid to force.');

                return;
            }

            try {
                // CompletePaidOrder marks paid, creates records and provisions.
                $complete->handle($order->fresh('items'), $context);
            } catch (Throwable $e) {
                $this->error("  CompletePaidOrder failed: {$e->getMessage()}");
            }

            $this->report($order->fresh());

            return;
        }

        // 2. Already paid → ensure visible records exist, reset any failed/stalled
        //    steps, then re-run the chain synchronously to finish the job.
        try {
            $ensure->handle($order->fresh('items'));
        } catch (Throwable $e) {
            $this->error("  Could not ensure service records: {$e->getMessage()}");
        }

        $reset = $this->resetStalledSteps($order);
        if ($reset > 0) {
            $this->line("  Reset {$reset} failed/stalled step(s) to pending.");
        }

        try {
            ProvisionOrderJob::dispatchSync($order->id);
        } catch (Throwable $e) {
            $this->error("  Provisioning run failed: {$e->getMessage()}");
        }

        $this->report($order->fresh());
    }

    /** Reset failed / manual-review / stuck-running steps so the chain re-runs them. */
    private function resetStalledSteps(Order $order): int
    {
        $stalled = $order->provisioningJobs()
            ->whereIn('status', [
                ProvisioningStatus::Failed->value,
                ProvisioningStatus::ManualReview->value,
                ProvisioningStatus::Running->value,
            ])
            ->get();

        foreach ($stalled as $job) {
            if ($job->attempts >= $job->max_attempts) {
                // Grant one explicit manual retry beyond the auto-retry ceiling.
                $job->forceFill(['max_attempts' => $job->attempts + 1])->save();
            }

            $job->forceFill([
                'status' => ProvisioningStatus::Pending->value,
                'error_message' => null,
                'failed_at' => null,
            ])->save();
        }

        return $stalled->count();
    }

    private function report(Order $order): void
    {
        $order->load(['domain', 'hostingAccount', 'websiteProject']);

        $this->table(['Field', 'Value'], [
            ['Order status', $order->status->value],
            ['Payment status', $order->payment_status->value],
            ['Domain', $order->domain ? "{$order->domain->domain_name} ({$order->domain->status->value})" : '—'],
            ['Hosting', $order->hostingAccount ? "{$order->hostingAccount->whm_username} ({$order->hostingAccount->status->value})" : '—'],
            ['Website project', $order->websiteProject ? "{$order->websiteProject->project_number} ({$order->websiteProject->status->value})" : '—'],
        ]);
    }

    private function resolveOrders(): Collection
    {
        if ($this->option('stuck')) {
            // Self-heal paid orders that never finished. Manual-review orders are
            // deliberately excluded — they await a human / the hourly failed-step
            // retry, so we never hammer external APIs on a known-bad order.
            $paidIncomplete = Order::with('items')
                ->where(fn ($q) => $q->whereNotNull('paid_at')->orWhere('payment_status', PaymentStatus::Succeeded->value))
                ->whereNotIn('status', [
                    OrderStatus::Completed->value,
                    OrderStatus::Cancelled->value,
                    OrderStatus::Refunded->value,
                    OrderStatus::ManualReview->value,
                ])
                ->get();

            // ALSO sweep recent "pending" orders that reached Stripe (they have a
            // PaymentIntent / session id) but were never marked paid — the classic
            // signature of a webhook that never arrived. Each is verified against
            // the Stripe API before anything happens, so an abandoned checkout is
            // simply skipped. Bounded to 30 days to keep the sweep cheap.
            $unpaidCandidates = Order::with('items')
                ->whereNull('paid_at')
                ->where('payment_status', '!=', PaymentStatus::Succeeded->value)
                ->where('status', OrderStatus::Pending->value)
                ->where(fn ($q) => $q->whereNotNull('stripe_payment_intent_id')->orWhereNotNull('stripe_checkout_session_id'))
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            return $paidIncomplete->merge($unpaidCandidates)->unique('id')->values();
        }

        $key = (string) $this->argument('order');

        if (blank($key)) {
            $this->error('Provide an order number (e.g. ORD-10007) or use --stuck.');

            return collect();
        }

        return Order::with('items')
            ->where('order_number', $key)
            ->when(is_numeric($key), fn ($q) => $q->orWhere('id', (int) $key))
            ->get();
    }
}
