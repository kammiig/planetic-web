<?php

namespace App\Services\Cart;

use App\Enums\ItemType;
use App\Enums\ProductType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Support\DomainName;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Cart management. All pricing is resolved server-side from the product
 * catalogue — a price supplied by the frontend is never trusted
 * (Security & Access §9, Frontend Spec §25.1).
 */
class CartService
{
    /**
     * Resolve the active cart for the current visitor, creating one if needed.
     * The cart id is tracked in the session so it survives login (session data
     * persists through regeneration), at which point it is claimed by the user.
     */
    public function currentCart(): Cart
    {
        $cart = null;

        if ($cartId = session('cart_id')) {
            $cart = Cart::with('items')->where('id', $cartId)->where('status', 'active')->first();
        }

        if (! $cart && Auth::check()) {
            $cart = Cart::with('items')
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'currency' => 'GBP',
            ]);
        }

        // Claim a guest cart once the visitor logs in.
        if (Auth::check() && $cart->user_id === null) {
            $cart->update(['user_id' => Auth::id()]);
        }

        session(['cart_id' => $cart->id]);

        return $cart;
    }

    /**
     * Add an item to the cart, computing its price server-side.
     *
     * @param  array<string, mixed>  $data  validated {item_type, product_id?, domain_name?, billing_cycle?}
     */
    public function addItem(array $data): CartItem
    {
        $cart = $this->currentCart();
        $type = ItemType::from($data['item_type']);
        $domainName = isset($data['domain_name']) ? DomainName::normalise($data['domain_name']) : null;

        $this->guardDuplicates($cart, $type, $domainName);

        [$product, $price, $name, $unitPrice, $billingCycle] = $this->resolvePricing($type, $data, $domainName);

        $item = $cart->items()->create([
            'product_id' => $product?->id,
            'product_price_id' => $price?->id,
            'item_type' => $type->value,
            'name' => $name,
            'domain_name' => $domainName,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total' => $unitPrice,
            'metadata' => array_filter(['billing_cycle' => $billingCycle]),
        ]);

        $cart->load('items')->recalculate();

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $this->currentCart();

        if ($item->cart_id !== $cart->id) {
            return; // never let a visitor remove another cart's item
        }

        $item->delete();
        $cart->load('items')->recalculate();
    }

    /**
     * @return array{0: ?Product, 1: ?\App\Models\ProductPrice, 2: string, 3: float, 4: ?string}
     */
    private function resolvePricing(ItemType $type, array $data, ?string $domainName): array
    {
        return match ($type) {
            ItemType::WebsitePackage => $this->websitePackagePricing($domainName),
            ItemType::Hosting => $this->hostingPricing($data),
            ItemType::DomainRegistration => $this->domainPricing($domainName),
            default => throw ValidationException::withMessages([
                'item_type' => 'That item cannot be added to the cart.',
            ]),
        };
    }

    private function websitePackagePricing(?string $domainName): array
    {
        $product = Product::ofType(ProductType::WebsitePackage)->active()->with('activePrices')->first();
        $price = $product?->priceFor('one_time');

        $name = 'Complete Bespoke Website';
        if ($domainName) {
            $name .= ' (with '.$domainName.')';
        }

        return [$product, $price, $name, (float) ($price?->amount ?? config('billing.website_package.price')), 'one_time'];
    }

    private function hostingPricing(array $data): array
    {
        $product = Product::ofType(ProductType::Hosting)->active()->with('activePrices')->find($data['product_id'] ?? null);

        if (! $product) {
            throw ValidationException::withMessages(['product_id' => 'That hosting plan is not available.']);
        }

        $cycle = in_array($data['billing_cycle'] ?? 'monthly', ['monthly', 'yearly'], true)
            ? $data['billing_cycle']
            : 'monthly';

        $price = $product->priceFor($cycle);

        if (! $price) {
            throw ValidationException::withMessages(['billing_cycle' => 'That billing cycle is not available for this plan.']);
        }

        return [$product, $price, $product->name.' ('.$cycle.')', (float) $price->amount, $cycle];
    }

    private function domainPricing(?string $domainName): array
    {
        if (! $domainName || ! DomainName::isValid($domainName)) {
            throw ValidationException::withMessages(['domain_name' => 'Please provide a valid domain name.']);
        }

        $product = Product::ofType(ProductType::Domain)->active()->with('activePrices')->first();
        $price = $product?->priceFor('yearly');

        return [$product, $price, 'Domain registration: '.$domainName, (float) ($price?->amount ?? 12.99), 'yearly'];
    }

    private function guardDuplicates(Cart $cart, ItemType $type, ?string $domainName): void
    {
        // Only one website package per cart.
        if ($type === ItemType::WebsitePackage && $cart->items->contains('item_type', ItemType::WebsitePackage)) {
            throw ValidationException::withMessages([
                'item_type' => 'Your cart already contains the website package.',
            ]);
        }

        // Never add the same domain twice.
        if ($domainName && $cart->items->firstWhere('domain_name', $domainName)) {
            throw ValidationException::withMessages([
                'domain_name' => $domainName.' is already in your cart.',
            ]);
        }
    }
}
