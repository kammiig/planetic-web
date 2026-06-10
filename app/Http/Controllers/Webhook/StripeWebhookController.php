<?php

namespace App\Http\Controllers\Webhook;

use App\Actions\Checkout\CompletePaidOrder;
use App\Actions\Orders\MarkOrderAsFailed;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Renewals\UnsuspendPaidHostingJob;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\Subscription;
use App\Services\Billing\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Stripe webhook receiver. Security rules (Security & Access §9):
 *  - Always verify the signature; reject invalid signatures (400).
 *  - Store processed event ids; ignore duplicates safely.
 *  - Provision only after a verified webhook — never from the success page.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly CompletePaidOrder $completePaidOrder,
        private readonly MarkOrderAsFailed $markOrderAsFailed,
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        // 1. Verify the signature.
        try {
            $event = $this->stripe->constructWebhookEvent($payload, (string) $signature);
        } catch (Throwable $e) {
            Log::channel('stack')->warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        Log::channel('stack')->info('Stripe webhook received.', [
            'event' => $event->id,
            'type' => $event->type,
        ]);

        // 2. Idempotency — ignore events we've already processed.
        $record = StripeEvent::firstOrNew(['stripe_event_id' => $event->id]);
        if ($record->exists && $record->status === 'processed') {
            Log::channel('stack')->info('Stripe webhook duplicate skipped.', [
                'event' => $event->id,
                'type' => $event->type,
            ]);

            return response('Already processed', Response::HTTP_OK);
        }

        $record->fill(['type' => $event->type, 'status' => 'received'])->save();

        // 3. Handle the event.
        try {
            $this->handleEvent($event);
            $record->markProcessed();
        } catch (Throwable $e) {
            $record->markFailed($e->getMessage());
            Log::channel('stack')->error('Stripe webhook handling error', [
                'event' => $event->id,
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            // Return 200 so Stripe does not hammer us; the failure is logged
            // and visible to admins for retry.
            return response('Handled with errors', Response::HTTP_OK);
        }

        return response('OK', Response::HTTP_OK);
    }

    private function handleEvent(Event $event): void
    {
        $object = $event->data->object;

        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($object),
            'payment_intent.succeeded' => $this->onPaymentIntentSucceeded($object),
            'payment_intent.payment_failed' => $this->onPaymentFailed($object),
            'invoice.payment_failed' => $this->onInvoicePaymentFailed($object),
            'invoice.paid' => $this->onInvoicePaid($object),
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->log($event->type, $object),
            default => null,
        };
    }

    private function onCheckoutCompleted(object $session): void
    {
        // Only act on a paid session.
        if (($session->payment_status ?? null) !== 'paid') {
            return;
        }

        $order = $this->resolveOrder($session->metadata->order_id ?? null);
        if (! $order) {
            return;
        }

        $this->completePaidOrder->handle($order, [
            'session_id' => $session->id ?? null,
            'payment_intent' => $session->payment_intent ?? null,
            'customer' => $session->customer ?? null,
        ]);
    }

    private function onPaymentIntentSucceeded(object $intent): void
    {
        // Secondary confirmation — idempotent with checkout.session.completed.
        $order = $this->resolveOrder($intent->metadata->order_id ?? null);
        if (! $order) {
            return;
        }

        $this->completePaidOrder->handle($order, [
            'payment_intent' => $intent->id ?? null,
            'customer' => $intent->customer ?? null,
        ]);
    }

    private function onPaymentFailed(object $intent): void
    {
        $order = $this->resolveOrder($intent->metadata->order_id ?? null);
        if (! $order) {
            return;
        }

        $reason = $intent->last_payment_error->message ?? 'Payment failed';
        $this->markOrderAsFailed->handle($order, $intent->id ?? null, $reason);
    }

    private function onInvoicePaymentFailed(object $invoice): void
    {
        $subscription = $this->resolveSubscription($invoice->subscription ?? null);
        if (! $subscription) {
            $this->log('invoice.payment_failed', $invoice);

            return;
        }

        // Mark past due and start the grace period; hosting is only suspended
        // later by the scheduled renewals:check once grace has elapsed.
        $subscription->update(['status' => SubscriptionStatus::PastDue->value]);

        Log::channel('stack')->warning('Subscription renewal payment failed', [
            'subscription' => $subscription->id,
            'user' => $subscription->user_id,
        ]);
    }

    private function onInvoicePaid(object $invoice): void
    {
        $subscription = $this->resolveSubscription($invoice->subscription ?? null);
        if (! $subscription) {
            $this->log('invoice.paid', $invoice);

            return;
        }

        // Renewal paid → reactivate and extend the period.
        $next = $subscription->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();
        $subscription->update([
            'status' => SubscriptionStatus::Active->value,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => $next->toDateString(),
            'next_renewal_date' => $next->toDateString(),
        ]);

        // If the linked hosting was suspended for non-payment, reactivate it.
        if ($subscription->hosting_account_id) {
            $account = $subscription->hostingAccount;
            if ($account && $account->isSuspended()) {
                UnsuspendPaidHostingJob::dispatch($account->id);
            }
        }
    }

    private function resolveSubscription(mixed $stripeSubscriptionId): ?Subscription
    {
        if (blank($stripeSubscriptionId)) {
            return null;
        }

        return Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
    }

    private function resolveOrder(mixed $orderId): ?Order
    {
        if (blank($orderId)) {
            Log::channel('stack')->warning('Stripe webhook with no order_id metadata — flagged for review.');

            return null;
        }

        $order = Order::with('items')->find($orderId);

        if (! $order) {
            Log::channel('stack')->warning('Stripe webhook references unknown order — manual review.', ['order_id' => $orderId]);
        }

        return $order;
    }

    private function log(string $type, object $object): void
    {
        Log::channel('stack')->info("Stripe event received: {$type}", ['id' => $object->id ?? null]);
    }
}
