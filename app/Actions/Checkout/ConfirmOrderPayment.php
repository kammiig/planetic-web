<?php

namespace App\Actions\Checkout;

use App\Models\Order;
use App\Services\Billing\StripeService;

/**
 * Verifies an order's payment directly with the Stripe API and, when the
 * charge has genuinely succeeded, completes the order (mark paid → service
 * records → provisioning → emails) via CompletePaidOrder.
 *
 * This is the webhook-independent completion path: the browser's claim is
 * never trusted — Stripe itself is asked server-to-server. Idempotent, so the
 * webhook, the success page and the scheduled sweep can all race safely.
 */
class ConfirmOrderPayment
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly CompletePaidOrder $complete,
    ) {}

    /** @return bool true when the order is (now) paid */
    public function handle(Order $order): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        $context = $this->stripe->findSucceededPayment($order);

        if ($context === null) {
            return false;
        }

        $this->complete->handle($order->fresh('items'), $context);

        return true;
    }
}
