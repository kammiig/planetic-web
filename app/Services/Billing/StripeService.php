<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Thin wrapper around the Stripe SDK. The secret key lives only in config
 * (env) and is never exposed to the frontend. Provisioning is never triggered
 * here — only after a verified webhook confirms payment.
 */
class StripeService
{
    public function client(): StripeClient
    {
        return new StripeClient(config('stripe.secret_key'));
    }

    /**
     * Ensure the user has a Stripe customer id, creating one if necessary.
     */
    public function ensureCustomer(User $user): string
    {
        if (filled($user->stripe_customer_id)) {
            return $user->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => (string) $user->id],
        ]);

        $user->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    /**
     * Create a Stripe Checkout session for an order. Uses one-time payment mode
     * and charges the order total now; recurring renewals are tracked locally
     * by the renewal engine. The order id travels in metadata so the webhook
     * can reconcile the payment.
     */
    public function createCheckoutSession(Order $order): Session
    {
        $customerId = $this->ensureCustomer($order->user);

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency' => config('stripe.currency', 'gbp'),
                'product_data' => ['name' => $item->name],
                'unit_amount' => (int) round(((float) $item->unit_price) * 100),
            ],
            'quantity' => (int) $item->quantity,
        ])->all();

        return $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'customer' => $customerId,
            'line_items' => $lineItems,
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'payment_intent_data' => [
                'metadata' => ['order_id' => (string) $order->id],
            ],
        ]);
    }

    /**
     * Verify a webhook payload against the signing secret and return the event.
     *
     * @throws \UnexpectedValueException|\Stripe\Exception\SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signatureHeader,
            (string) config('stripe.webhook_secret'),
            (int) config('stripe.webhook_tolerance', 300),
        );
    }
}
