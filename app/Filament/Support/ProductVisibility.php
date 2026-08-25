<?php

namespace App\Filament\Support;

use App\Enums\ProductType;
use App\Models\Product;
use App\Support\Region;

/**
 * Shared bits of the "unlisted product" admin feature: the name of the form
 * toggle, and the private add-to-cart URL an admin sends to a customer.
 *
 * Hiding is visibility only. A hidden product still prices, adds to the cart
 * and checks out exactly like a listed one — it just never appears in a public
 * pricing table, so the link below is the only way anyone reaches it.
 */
class ProductVisibility
{
    /** Form field carrying the hide toggle (written through to the product). */
    public const FIELD = 'product_is_hidden';

    /**
     * Direct add-to-cart URL for a product, in the default storefront.
     *
     * Only hosting and website packages have one: a domain needs a name chosen
     * at search time, so there is no meaningful one-click link for it.
     */
    public static function directCartLink(?Product $product): ?string
    {
        if (! $product?->slug) {
            return null;
        }

        if (! in_array($product->type, [ProductType::Hosting, ProductType::WebsitePackage], true)) {
            return null;
        }

        return Region::default()->route('cart.add', ['slug' => $product->slug]);
    }
}
