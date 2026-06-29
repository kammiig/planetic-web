<?php

namespace App\Actions\Checkout;

use App\Actions\Provisioning\EnsureServiceRecords;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\AdminNotifier;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs once a Stripe webhook has confirmed payment. This is the ONLY place an
 * order transitions to paid and provisioning begins — never the success page.
 * Idempotent: safe to call again for the same order (duplicate webhooks).
 */
class CompletePaidOrder
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * Complete a £0 / free order: no Stripe charge is taken, but the order is
     * marked as requiring no payment and provisioning runs exactly as for a
     * paid order. Used for free first-year hosting + free domain bundles.
     */
    public function handleFree(Order $order): void
    {
        $this->handle($order, free: true);
    }

    /**
     * @param  array{session_id?: string, payment_intent?: string, customer?: string, stripe_invoice_id?: string}  $context
     */
    public function handle(Order $order, array $context = [], bool $free = false): void
    {
        // Already processed — do not provision twice.
        if ($order->isPaid()) {
            return;
        }

        // The webhook, the success page and the scheduled sweep may all detect
        // the same payment at once; the row lock makes exactly one of them win.
        $proceed = DB::transaction(function () use ($order, $context, $free) {
            $current = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $current || $current->isPaid()) {
                return false;
            }

            $order->forceFill([
                'status' => OrderStatus::Provisioning->value,
                'payment_status' => ($free ? PaymentStatus::NoPaymentRequired : PaymentStatus::Succeeded)->value,
                'paid_at' => now(),
                'stripe_checkout_session_id' => $context['session_id'] ?? $order->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $context['payment_intent'] ?? $order->stripe_payment_intent_id,
            ])->save();

            $this->invoices->createForOrder($order, $context['stripe_invoice_id'] ?? null);

            if ($free) {
                $this->invoices->recordFreeOrder($order);
            } elseif (! empty($context['payment_intent'])) {
                $this->invoices->recordSuccessfulPayment($order, $context['payment_intent'], $context['customer'] ?? null);
            }

            // Renewal subscriptions are created only after provisioning succeeds
            // (ProvisioningOrchestrator → ActivateOrderSubscriptions), never here
            // at payment time — so a failed registration leaves no phantom
            // renewal/auto-renew record in Billing.

            return true;
        });

        if (! $proceed) {
            return;
        }

        Log::channel('stack')->info('Payment success detected — order marked paid.', [
            'order' => $order->order_number,
            'payment_intent' => $context['payment_intent'] ?? $order->stripe_payment_intent_id,
        ]);

        // Create the customer-visible service records immediately so the
        // dashboard tabs are never empty after payment — even before (or if)
        // the external provisioning APIs run. Best-effort: a hiccup here must
        // never undo the recorded payment above.
        try {
            app(EnsureServiceRecords::class)->handle($order->fresh('items'));
        } catch (Throwable $e) {
            Log::channel('stack')->error('Could not create pending service records.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        // Order confirmation email (failures are logged, never block the flow).
        app(NotificationService::class)->send(
            $order->user,
            new OrderConfirmationMail($order->fresh('items')),
            'order_confirmation',
            trustpilotBcc: true, // Successful paid order → invite the customer to review.
        );

        // A bespoke website sale needs a human kick-off — tell the admin team.
        if ($order->containsWebsitePackage()) {
            app(AdminNotifier::class)->alert(
                'New website project purchased',
                'A customer has bought the £200 bespoke website package. The project record is in the admin panel awaiting kick-off.',
                array_filter([
                    'Order' => $order->order_number,
                    'Customer' => $order->user?->name.' <'.$order->user?->email.'>',
                    'Domain' => $order->primaryDomainName(),
                    'Total' => '£'.number_format((float) $order->total, 2),
                ]),
                url('/admin/website-projects'),
                'Open website projects',
            );
        }

        // Run provisioning. Synchronous by default (no queue worker required on
        // cPanel); a failure inside a step is captured per-step and must not
        // bubble up to fail the webhook.
        try {
            $this->dispatchProvisioning($order);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Provisioning dispatch failed.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kick off the provisioning pipeline. Runs inline when provisioning.sync is
     * enabled (default) so services appear immediately without a background
     * worker; otherwise pushes onto the queue for a worker/cron to process.
     */
    private function dispatchProvisioning(Order $order): void
    {
        if (config('provisioning.sync', true)) {
            ProvisionOrderJob::dispatchSync($order->id);

            return;
        }

        ProvisionOrderJob::dispatch($order->id);
    }

}
