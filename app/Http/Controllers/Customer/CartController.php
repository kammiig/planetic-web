<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ItemType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Support\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        try {
            $item = $this->cart->addItem($request->validated());
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                throw $e;
            }

            // Storefront pages render flash messages but not the error bag, so
            // redirecting back with only validation errors reads to the
            // customer as a button that does nothing at all.
            return back()->with('error', $e->validator->errors()->first());
        }

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

        // Stay in the storefront the item was added from.
        return redirect()->to(Region::current()->route('cart.index'))
            ->with('success', $item->name.' added to your cart.');
    }

    /**
     * Add a catalogue product straight to the cart from a plain link.
     *
     * Exists so an UNLISTED plan (products.is_hidden) can still be sold: the
     * plan is withheld from every public pricing table, and this URL is handed
     * privately to the customers who should get it. Hidden and listed products
     * behave identically here — hiding controls visibility, never availability.
     *
     * Only the product SLUG travels in the URL. The price is still resolved
     * server-side from the catalogue in the cart's locked currency, so a
     * forwarded link can never buy anything at the wrong price.
     */
    public function add(Request $request, string $slug): RedirectResponse
    {
        $product = Product::active()->with('activePrices')->where('slug', $slug)->first();

        $itemType = match ($product?->type) {
            ProductType::Hosting => ItemType::Hosting,
            ProductType::WebsitePackage => ItemType::WebsitePackage,
            // Domains need a name, so they are bought through the search page;
            // anything else has no cart line at all.
            default => null,
        };

        if (! $product || ! $itemType) {
            abort(404);
        }

        $payload = ['item_type' => $itemType->value];

        if ($itemType === ItemType::Hosting) {
            $payload['product_id'] = $product->id;
            $payload['billing_cycle'] = $this->resolveCycle($request, $product);
        }

        try {
            $item = $this->cart->addItem($payload);
        } catch (ValidationException $e) {
            return redirect()->to(Region::current()->route('cart.index'))
                ->with('error', $e->validator->errors()->first());
        }

        return redirect()->to(Region::current()->route('cart.index'))
            ->with('success', $item->name.' added to your cart.');
    }

    /**
     * Billing cycle for a link-added hosting plan: the one asked for in the
     * URL when this storefront actually prices it, otherwise whichever cycle
     * the plan does publish. A private link must not 404 a customer just
     * because the plan is yearly-only and the link said monthly.
     */
    private function resolveCycle(Request $request, Product $product): string
    {
        $requested = (string) $request->query('cycle', $request->query('billing_cycle', ''));
        $cycles = in_array($requested, ['monthly', 'yearly'], true)
            ? [$requested, $requested === 'monthly' ? 'yearly' : 'monthly']
            : ['monthly', 'yearly'];

        foreach ($cycles as $cycle) {
            if ($product->priceFor($cycle)) {
                return $cycle;
            }
        }

        // Nothing priced here — hand it to CartService, which raises the proper
        // "not available in this currency" message rather than guessing.
        return $cycles[0];
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
            ? redirect()->to(Region::current()->route('cart.index'))->with('success', 'Item removed from your cart.')
            : redirect()->to(Region::current()->route('cart.index'))->with('error', 'That item could not be removed. Please refresh the page and try again.');
    }
}
