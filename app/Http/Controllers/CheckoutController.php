<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\CompletePaidOrder;
use App\Actions\Checkout\ConfirmOrderPayment;
use App\Actions\Orders\CreateOrderFromCart;
use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\Billing\StripeService;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    /**
     * Render the multi-step, on-site checkout. Payment is taken here with the
     * Stripe Payment Element — the customer is never redirected to a hosted page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cart->currentCart()->load('items.product.hostingPackage');
        $pendingOrder = $this->pendingCheckoutOrder($request);

        if ($cart->items->isEmpty() && ! $pendingOrder) {
            return redirect()->route('cart.index')->with('info', 'Your cart is empty.');
        }

        // Prefer the live cart; fall back to an in-progress order (e.g. after a
        // refresh mid-payment, once the cart has already been converted).
        $usingOrder = $cart->items->isEmpty() && $pendingOrder;

        // Hosting / website-package orders must pick a domain before paying.
        // (When resuming a converted order the choice is already baked in.)
        $needsDomain = ! $usingOrder && $this->cart->needsDomainChoice($cart);

        return view('checkout.checkout', [
            'lineItems' => $usingOrder ? $pendingOrder->items : $cart->items,
            'total' => $usingOrder ? (float) $pendingOrder->total : (float) $cart->total,
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'publishableKey' => (string) config('stripe.public_key'),
            'needsDomain' => $needsDomain,
            'domainChoice' => $needsDomain ? $this->cart->domainChoice($cart) : ['source' => null, 'domain' => null],
            'canDeferDomain' => $needsDomain && $this->cart->canDeferDomain($cart),
            'domainIsFree' => $needsDomain && $this->cart->domainIsFree($cart),
            'initialStep' => (string) $request->query('step', ''),
        ]);
    }

    /**
     * AJAX: store the customer's domain choice (register new / use existing /
     * decide later) on the cart. Validation failures return 422 JSON; the
     * availability of a "new" domain is verified server-side.
     */
    public function setDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_source' => ['required', 'in:new,existing,later'],
            'domain_name' => ['required_unless:domain_source,later', 'nullable', 'string', 'max:253'],
        ], [
            'domain_name.required_unless' => 'Please enter a domain name.',
        ]);

        $this->cart->setDomainChoice($validated['domain_source'], $validated['domain_name'] ?? null);

        return response()->json(['ok' => true]);
    }

    /**
     * AJAX: validate billing, create (or reuse) the order and a PaymentIntent,
     * and return the client_secret so the browser can confirm payment on-site.
     * The secret key stays on the backend; only the client_secret is returned.
     */
    public function paymentIntent(CheckoutRequest $request, CreateOrderFromCart $createOrder, StripeService $stripe): JsonResponse
    {
        // Hosting cannot be provisioned without a domain — enforce the domain
        // choice server-side before any money is taken.
        $cart = $this->cart->currentCart()->load('items.product.hostingPackage');

        if ($cart->items->isNotEmpty() && ($error = $this->cart->domainRequirementError($cart))) {
            return response()->json(['error' => $error], 422);
        }

        // Persist billing details to the customer's profile.
        $request->user()->update($request->billingData());

        $order = $this->resolveCheckoutOrder($request, $createOrder);

        if (! $order) {
            return response()->json([
                'error' => 'Your cart is empty. Please add a product before paying.',
            ], 422);
        }

        try {
            $intent = $stripe->createOrReusePaymentIntent($order);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Stripe PaymentIntent creation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'We could not start secure payment right now. Please try again in a few moments.',
            ], 502);
        }

        return response()->json([
            'client_secret' => $intent->client_secret,
            'publishable_key' => (string) config('stripe.public_key'),
            'order_number' => $order->order_number,
            'amount' => (float) $order->total,
            'currency' => strtoupper((string) $order->currency),
        ]);
    }

    /**
     * AJAX: complete a £0 / free order WITHOUT Stripe. Stripe rejects a £0
     * PaymentIntent, so free first-year bundles are confirmed here directly:
     * the order is marked as requiring no payment and provisioning starts
     * immediately (register domain, create hosting, Cloudflare zone/DNS, email).
     * When the platform is configured to require a card for renewals, a Stripe
     * SetupIntent is used to save the card first (never a charge).
     */
    public function completeFree(CheckoutRequest $request, CreateOrderFromCart $createOrder, CompletePaidOrder $complete, StripeService $stripe): JsonResponse
    {
        $cart = $this->cart->currentCart()->load('items.product.hostingPackage');

        if ($cart->items->isNotEmpty() && ($error = $this->cart->domainRequirementError($cart))) {
            return response()->json(['error' => $error], 422);
        }

        $request->user()->update($request->billingData());

        $order = $this->resolveCheckoutOrder($request, $createOrder);

        if (! $order) {
            return response()->json(['error' => 'Your cart is empty. Please add a product before placing your order.'], 422);
        }

        // Safety net: never complete a payable order for free.
        if ((float) $order->total > 0.0) {
            return response()->json(['error' => 'This order requires payment.'], 422);
        }

        // Optionally require a saved card (for future renewals) before completing.
        if (setting('checkout.require_card_for_free_orders', false)) {
            if ($paymentMethod = $request->input('payment_method')) {
                // Card was just saved via a SetupIntent on the client.
                $stripe->setDefaultPaymentMethod($request->user(), $paymentMethod);
            } elseif (! $stripe->hasSavedCard($request->user())) {
                try {
                    $setup = $stripe->createSetupIntent($order);
                } catch (Throwable $e) {
                    Log::channel('stack')->error('Stripe SetupIntent creation failed', [
                        'order' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json(['error' => 'We could not start card setup right now. Please try again in a few moments.'], 502);
                }

                return response()->json([
                    'setup_required' => true,
                    'client_secret' => $setup->client_secret,
                    'publishable_key' => (string) config('stripe.public_key'),
                    'order_number' => $order->order_number,
                ]);
            }
        }

        try {
            $complete->handleFree($order);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Free order completion failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'We could not place your order right now. Please try again in a few moments.'], 502);
        }

        $request->session()->forget('checkout_order_id');

        return response()->json([
            'ok' => true,
            'free' => true,
            'order_number' => $order->order_number,
            'redirect' => route('checkout.success').'?order='.urlencode($order->order_number),
        ]);
    }

    /**
     * Confirmation page. The browser's redirect is never trusted: when the
     * order is still unpaid we ask Stripe directly (server-to-server) whether
     * the charge succeeded, and only then complete the order. Idempotent with
     * the webhook and the scheduled sweep — whichever runs first wins, the
     * rest are no-ops. This means services are provisioned even if the Stripe
     * webhook endpoint is missing or misconfigured.
     */
    public function success(Request $request, ConfirmOrderPayment $confirm): View
    {
        $order = $this->locateOrderForConfirmation($request);

        if ($order) {
            if (! $order->isPaid()) {
                try {
                    $confirm->handle($order);
                } catch (Throwable $e) {
                    // Never break the confirmation page — the scheduled sweep
                    // (orders:provision --stuck) will finish the job.
                    Log::channel('stack')->error('Success-page payment confirmation failed.', [
                        'order' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $order->refresh();

            // Start a fresh checkout next time — this order is now with Stripe.
            $request->session()->forget('checkout_order_id');
        }

        return view('checkout.success', ['order' => $order]);
    }

    public function cancel(): View
    {
        return view('checkout.cancel');
    }

    /**
     * The pending, unpaid order already started in this checkout session, if any.
     * Lets a refresh or repeated request reuse the same order + PaymentIntent
     * instead of creating duplicates.
     */
    private function pendingCheckoutOrder(Request $request): ?Order
    {
        if (! $request->user()) {
            return null;
        }

        $orderId = $request->session()->get('checkout_order_id');

        if (! $orderId) {
            return null;
        }

        $order = Order::with('items')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $order || $order->isPaid() || $order->status === OrderStatus::Cancelled) {
            return null;
        }

        return $order;
    }

    private function resolveCheckoutOrder(CheckoutRequest $request, CreateOrderFromCart $createOrder): ?Order
    {
        if ($existing = $this->pendingCheckoutOrder($request)) {
            return $existing;
        }

        $cart = $this->cart->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return null;
        }

        $order = $createOrder->handle($cart, $request->user());
        $request->session()->put('checkout_order_id', $order->id);

        return $order;
    }

    private function locateOrderForConfirmation(Request $request): ?Order
    {
        $userId = $request->user()?->id;

        // Free (£0) orders are completed server-side and carry their order number.
        if ($orderNumber = $request->query('order')) {
            return Order::where('order_number', $orderNumber)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->first();
        }

        if ($paymentIntent = $request->query('payment_intent')) {
            return Order::where('stripe_payment_intent_id', $paymentIntent)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->first();
        }

        if ($sessionId = $request->query('session_id')) {
            return Order::where('stripe_checkout_session_id', $sessionId)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->first();
        }

        return null;
    }
}
