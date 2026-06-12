<?php

namespace App\Services\Cart;

use App\Actions\Domains\CheckDomainAvailability;
use App\Enums\ItemType;
use App\Enums\ProductType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Support\DomainName;
use Illuminate\Support\Collection;
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

        $cycle = $data['billing_cycle'] ?? 'monthly';
        if (! in_array($cycle, ['monthly', 'yearly'], true)) {
            $cycle = 'monthly';
        }

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

    /*
    |--------------------------------------------------------------------------
    | Domain choice (hosting & website package orders)
    |--------------------------------------------------------------------------
    | WHM cannot create a cPanel account without a domain, so any order that
    | includes hosting must carry one before payment. The checkout collects the
    | customer's choice — register a new domain, use one they already own, or
    | (website package only) provide it later — and stores it on the cart
    | items, from where it flows onto the order items and provisioning.
    */

    /** Cart items that need a domain attached before provisioning can run. */
    public function itemsNeedingDomain(Cart $cart): Collection
    {
        return $cart->items->filter(fn (CartItem $i) => in_array(
            $i->item_type, [ItemType::WebsitePackage, ItemType::Hosting], true
        ));
    }

    public function needsDomainChoice(Cart $cart): bool
    {
        return $this->itemsNeedingDomain($cart)->isNotEmpty();
    }

    /**
     * "Decide later" is only allowed for the website package — our team picks
     * the project up manually anyway. A standalone hosting plan must have a
     * domain before payment (business rule).
     */
    public function canDeferDomain(Cart $cart): bool
    {
        return $cart->items->contains('item_type', ItemType::WebsitePackage)
            && ! $cart->items->contains('item_type', ItemType::Hosting);
    }

    /** The first year of the domain is free with the website package or a flagged hosting plan. */
    public function domainIsFree(Cart $cart): bool
    {
        if ($cart->items->contains('item_type', ItemType::WebsitePackage)) {
            return true;
        }

        return $cart->items
            ->filter(fn (CartItem $i) => $i->item_type === ItemType::Hosting)
            ->contains(fn (CartItem $i) => (bool) $i->product?->hostingPackage?->includes_free_domain);
    }

    /**
     * The current domain choice: explicit (stored on hosting/website items),
     * derived from a domain-registration line already in the cart, or none.
     *
     * @return array{source: ?string, domain: ?string}
     */
    public function domainChoice(Cart $cart): array
    {
        $carrier = $this->itemsNeedingDomain($cart)
            ->first(fn (CartItem $i) => filled($i->metadata['domain_source'] ?? null));

        if ($carrier) {
            return [
                'source' => $carrier->metadata['domain_source'],
                'domain' => $carrier->domain_name,
            ];
        }

        $domainLine = $cart->items->firstWhere('item_type', ItemType::DomainRegistration);

        if ($domainLine && filled($domainLine->domain_name)) {
            return ['source' => 'new', 'domain' => $domainLine->domain_name];
        }

        return ['source' => null, 'domain' => null];
    }

    /**
     * Whether checkout may proceed to payment. Returns a customer-facing error
     * when the order includes hosting but no usable domain choice was made.
     */
    public function domainRequirementError(Cart $cart): ?string
    {
        if (! $this->needsDomainChoice($cart)) {
            return null;
        }

        $choice = $this->domainChoice($cart);

        if ($choice['source'] === 'later') {
            return $this->canDeferDomain($cart)
                ? null
                : 'Hosting needs a domain — please choose or enter one before paying.';
        }

        if (filled($choice['domain'])) {
            return null;
        }

        return 'Please choose a domain for your order before paying — register a new one or use a domain you already own.';
    }

    /**
     * Persist the customer's domain choice onto the cart.
     *
     * @param  string  $source  new | existing | later
     */
    public function setDomainChoice(string $source, ?string $domainName = null): Cart
    {
        $cart = $this->currentCart()->load('items.product.hostingPackage');

        if (! $this->needsDomainChoice($cart)) {
            throw ValidationException::withMessages([
                'domain_source' => 'This order does not need a domain.',
            ]);
        }

        if ($source === 'later') {
            if (! $this->canDeferDomain($cart)) {
                throw ValidationException::withMessages([
                    'domain_source' => 'Hosting requires a domain — you can register a new one or use a domain you already own.',
                ]);
            }

            $this->applyDomainToItems($cart, null, 'later');
            $this->removeAutoAddedDomainLine($cart);

            return $cart->load('items')->recalculate();
        }

        $domain = DomainName::normalise((string) $domainName);

        if (! DomainName::isValid($domain)) {
            throw ValidationException::withMessages([
                'domain_name' => 'Please enter a valid domain name, e.g. yourbusiness.com.',
            ]);
        }

        if ($source === 'new') {
            $availability = app(CheckDomainAvailability::class)->handle($domain);

            if (! ($availability['available'] ?? false)) {
                throw ValidationException::withMessages([
                    'domain_name' => $domain.' is not available to register. Try another name, or choose "Use a domain I already own".',
                ]);
            }

            $this->applyDomainToItems($cart, $domain, 'new');
            $this->ensureDomainLine($cart, $domain);
        } else {
            // A domain the customer already owns (registered elsewhere) — we
            // never charge for or try to register it.
            $this->applyDomainToItems($cart, $domain, 'existing');
            $this->removeAutoAddedDomainLine($cart);
        }

        return $cart->load('items')->recalculate();
    }

    private function applyDomainToItems(Cart $cart, ?string $domain, string $source): void
    {
        foreach ($this->itemsNeedingDomain($cart) as $item) {
            $name = $item->item_type === ItemType::WebsitePackage
                ? 'Complete Bespoke Website'.($domain ? ' (with '.$domain.')' : '')
                : $item->name;

            $item->update([
                'domain_name' => $domain,
                'name' => $name,
                'metadata' => array_merge($item->metadata ?? [], ['domain_source' => $source]),
            ]);
        }
    }

    /**
     * Make sure a domain-registration line exists for a newly registered
     * domain — free (£0) when included with the website package or a flagged
     * hosting plan, otherwise priced from the catalogue.
     */
    private function ensureDomainLine(Cart $cart, string $domain): void
    {
        $free = $this->domainIsFree($cart);
        $existing = $cart->items->firstWhere('item_type', ItemType::DomainRegistration);

        [$product, $price, , $unitPrice] = $this->domainPricing($domain);
        $unitPrice = $free ? 0.0 : $unitPrice;
        $name = 'Domain registration: '.$domain.($free ? ' (free first year)' : '');

        if ($existing) {
            $existing->update([
                'domain_name' => $domain,
                'name' => $name,
                'unit_price' => $unitPrice,
                'total' => $unitPrice,
                'metadata' => array_merge($existing->metadata ?? [], [
                    'billing_cycle' => 'yearly',
                    'auto_added' => $existing->metadata['auto_added'] ?? true,
                    'free_first_year' => $free,
                ]),
            ]);

            return;
        }

        $cart->items()->create([
            'product_id' => $product?->id,
            'product_price_id' => $price?->id,
            'item_type' => ItemType::DomainRegistration->value,
            'name' => $name,
            'domain_name' => $domain,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total' => $unitPrice,
            'metadata' => ['billing_cycle' => 'yearly', 'auto_added' => true, 'free_first_year' => $free],
        ]);
    }

    /** Remove a domain line we added automatically (never one the customer added). */
    private function removeAutoAddedDomainLine(Cart $cart): void
    {
        $cart->items
            ->filter(fn (CartItem $i) => $i->item_type === ItemType::DomainRegistration
                && ($i->metadata['auto_added'] ?? false))
            ->each->delete();
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
