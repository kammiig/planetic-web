<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

/**
 * Creates and updates invoice + payment records. We never store card numbers
 * or CVV — only Stripe references (Security & Access §9.4).
 */
class InvoiceService
{
    /**
     * Create (idempotently) the invoice for a paid order.
     */
    public function createForOrder(Order $order, ?string $stripeInvoiceId = null): Invoice
    {
        $existing = $order->invoice()->first();
        if ($existing) {
            return $existing;
        }

        $invoice = $order->invoice()->create([
            'user_id' => $order->user_id,
            'invoice_number' => 'TMP-'.uniqid(),
            'stripe_invoice_id' => $stripeInvoiceId,
            'currency' => $order->currency,
            'subtotal' => $order->subtotal,
            'tax_total' => $order->tax_total,
            'total' => $order->total,
            'amount_paid' => $order->total,
            'amount_due' => 0,
            'status' => InvoiceStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $invoice->update(['invoice_number' => 'INV-'.(100000 + $invoice->id)]);

        return $invoice;
    }

    /**
     * Record a successful payment against an order (idempotent on the Stripe
     * payment id).
     */
    public function recordSuccessfulPayment(Order $order, string $providerPaymentId, ?string $customerId = null): Payment
    {
        return Payment::updateOrCreate(
            ['provider_payment_id' => $providerPaymentId],
            [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'invoice_id' => $order->invoice()->value('id'),
                'provider' => 'stripe',
                'provider_customer_id' => $customerId,
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => PaymentStatus::Succeeded->value,
                'paid_at' => now(),
            ],
        );
    }

    /**
     * Record a failed payment attempt with its (internal) reason.
     */
    public function recordFailedPayment(Order $order, ?string $providerPaymentId, ?string $reason = null): Payment
    {
        return Payment::updateOrCreate(
            ['provider_payment_id' => $providerPaymentId ?: 'failed-'.uniqid()],
            [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'provider' => 'stripe',
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => PaymentStatus::Failed->value,
                'failure_reason' => $reason,
            ],
        );
    }
}
