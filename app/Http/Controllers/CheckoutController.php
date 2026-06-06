<?php

namespace App\Http\Controllers;

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

    public function index(): View|RedirectResponse
    {
        $cart = $this->cart->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('info', 'Your cart is empty.');
        }

        return view('checkout.checkout', [
            'cart' => $cart,
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
        ]);
    }

    public function start(CheckoutRequest $request, CreateOrderFromCart $createOrder, StripeService $stripe): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $cart = $this->cart->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Persist billing details to the customer's profile.
        $user->update($request->billingData());

        $order = $createOrder->handle($cart, $user);

        try {
            $session = $stripe->createCheckoutSession($order);
            $order->update(['stripe_checkout_session_id' => $session->id]);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Stripe checkout session creation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            $order->update(['status' => OrderStatus::Failed->value]);

            return redirect()->route('checkout.index')
                ->with('error', 'We could not start secure checkout right now. Please try again in a few moments.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'stripe_checkout_session_id' => $session->id,
            ]);
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request): View
    {
        // The success page NEVER provisions — it only reflects backend status.
        $order = null;
        if ($sessionId = $request->query('session_id')) {
            $order = Order::where('stripe_checkout_session_id', $sessionId)
                ->when($request->user(), fn ($q) => $q->where('user_id', $request->user()->id))
                ->first();
        }

        return view('checkout.success', ['order' => $order]);
    }

    public function cancel(): View
    {
        return view('checkout.cancel');
    }
}
