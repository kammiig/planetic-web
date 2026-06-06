<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\NotificationService;

/**
 * Marks an order as failed after a declined/failed payment. No services are
 * provisioned; the customer can retry checkout (Security & Access §11.4).
 */
class MarkOrderAsFailed
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(Order $order, ?string $paymentIntentId = null, ?string $reason = null): void
    {
        // Never override an already-paid order.
        if ($order->isPaid()) {
            return;
        }

        $order->forceFill([
            'status' => OrderStatus::Failed->value,
            'payment_status' => PaymentStatus::Failed->value,
        ])->save();

        $this->invoices->recordFailedPayment($order, $paymentIntentId, $reason);

        app(NotificationService::class)->send($order->user, new PaymentFailedMail($order), 'payment_failed');
    }
}
