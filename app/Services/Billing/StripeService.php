<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\PaymentIntent;
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
     * Create (or safely reuse) a PaymentIntent for an order so payment can be
     * taken on-site with the Stripe Payment Element. The secret key never leaves
     * the backend; only the returned client_secret is exposed to the browser.
     *
     * Idempotency is layered:
     *  - We persist the intent id on the order and reuse it while it is still
     *    payable, so a page refresh or repeated AJAX call never spawns a second
     *    intent (and therefore never a duplicate charge or duplicate order).
     *  - The create call also carries a deterministic idempotency key as a
     *    backstop against a concurrent double-submit.
     * The order id travels in metadata so the verified webhook can reconcile the
     * payment and trigger provisioning — never the browser.
     */
    public function createOrReusePaymentIntent(Order $order): PaymentIntent
    {
        $client = $this->client();
        $customerId = $this->ensureCustomer($order->user);
        $amount = $this->minorAmount((float) $order->total);
        $currency = config('stripe.currency', 'gbp');

        // Reuse an existing intent for this order while it can still be paid.
        if (filled($order->stripe_payment_intent_id)) {
            try {
                $intent = $client->paymentIntents->retrieve($order->stripe_payment_intent_id);

                $payable = in_array($intent->status, [
                    'requires_payment_method', 'requires_confirmation', 'requires_action', 'processing',
                ], true);

                if ($payable) {
                    // Keep the amount in sync if the basket changed while still editable.
                    if ((int) $intent->amount !== $amount
                        && in_array($intent->status, ['requires_payment_method', 'requires_confirmation'], true)) {
                        $intent = $client->paymentIntents->update($intent->id, ['amount' => $amount]);
                    }

                    return $intent;
                }
            } catch (\Throwable $e) {
                Log::channel('stack')->warning('Could not reuse Stripe PaymentIntent; creating a fresh one.', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $intent = $client->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'description' => config('app.name').' order '.$order->order_number,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ], [
            'idempotency_key' => 'pi_order_'.$order->id,
        ]);

        $order->forceFill(['stripe_payment_intent_id' => $intent->id])->save();

        return $intent;
    }

    /** Convert a major-unit amount (e.g. pounds) to Stripe's minor units (pence). */
    public function minorAmount(float $amount): int
    {
        return (int) round($amount * 100);
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
