<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        $cart = $this->cart->currentCart()->load('items');

        return view('checkout.cart', [
            'cart' => $cart,
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse|RedirectResponse
    {
        $item = $this->cart->addItem($request->validated());
        $cart = $this->cart->currentCart()->load('items');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cart' => [
                    'id' => $cart->id,
                    'subtotal' => number_format((float) $cart->subtotal, 2, '.', ''),
                    'total' => number_format((float) $cart->total, 2, '.', ''),
                    'currency' => $cart->currency,
                    'item_count' => $cart->items->count(),
                ],
            ]);
        }

        return redirect()->route('cart.index')->with('success', $item->name.' added to your cart.');
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse|RedirectResponse
    {
        $removed = $this->cart->removeItem($cartItem);

        if ($request->expectsJson()) {
            return $removed
                ? response()->json(['success' => true])
                : response()->json(['success' => false, 'message' => 'That item could not be removed. Please refresh the page and try again.'], 422);
        }

        return $removed
            ? redirect()->route('cart.index')->with('success', 'Item removed from your cart.')
            : redirect()->route('cart.index')->with('error', 'That item could not be removed. Please refresh the page and try again.');
    }
}
