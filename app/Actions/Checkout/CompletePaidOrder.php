<?php

namespace App\Actions\Checkout;

use App\Enums\ItemType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\WebsiteProjectStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * Runs once a Stripe webhook has confirmed payment. This is the ONLY place an
 * order transitions to paid and provisioning begins — never the success page.
 * Idempotent: safe to call again for the same order (duplicate webhooks).
 */
class CompletePaidOrder
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * @param  array{session_id?: string, payment_intent?: string, customer?: string, stripe_invoice_id?: string}  $context
     */
    public function handle(Order $order, array $context = []): void
    {
        // Already processed — do not provision twice.
        if ($order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order, $context) {
            $order->forceFill([
                'status' => OrderStatus::Provisioning->value,
                'payment_status' => PaymentStatus::Succeeded->value,
                'paid_at' => now(),
                'stripe_checkout_session_id' => $context['session_id'] ?? $order->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $context['payment_intent'] ?? $order->stripe_payment_intent_id,
            ])->save();

            $this->invoices->createForOrder($order, $context['stripe_invoice_id'] ?? null);

            if (! empty($context['payment_intent'])) {
                $this->invoices->recordSuccessfulPayment($order, $context['payment_intent'], $context['customer'] ?? null);
            }

            $this->createWebsiteProject($order->fresh('items'));
            $this->createSubscriptions($order->fresh('items'));
        });

        // Order confirmation email (failures are logged, never block the flow).
        app(NotificationService::class)->send(
            $order->user,
            new OrderConfirmationMail($order->fresh('items')),
            'order_confirmation',
        );

        // Hand off to the queued provisioning pipeline.
        ProvisionOrderJob::dispatch($order->id);
    }

    private function createWebsiteProject(Order $order): void
    {
        if (! $order->containsWebsitePackage() || $order->websiteProject()->exists()) {
            return;
        }

        $project = $order->websiteProject()->create([
            'user_id' => $order->user_id,
            'project_number' => 'TMP-'.uniqid(),
            'status' => WebsiteProjectStatus::InformationRequired->value,
            'business_name' => $order->user->company_name,
        ]);

        $project->update(['project_number' => 'PRJ-'.(10000 + $project->id)]);
    }

    private function createSubscriptions(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->item_type !== ItemType::Hosting) {
                continue;
            }

            $cycle = $item->metadata['billing_cycle'] ?? 'monthly';
            $nextRenewal = $cycle === 'yearly' ? now()->addYear() : now()->addMonth();

            $order->user->subscriptions()->create([
                'product_id' => $item->product_id,
                'status' => SubscriptionStatus::Active->value,
                'billing_cycle' => $cycle,
                'currency' => $order->currency,
                'amount' => $item->unit_price,
                'current_period_start' => now()->toDateString(),
                'current_period_end' => $nextRenewal->toDateString(),
                'next_renewal_date' => $nextRenewal->toDateString(),
            ]);
        }
    }
}
