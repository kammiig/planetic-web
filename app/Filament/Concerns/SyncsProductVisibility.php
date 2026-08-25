<?php

namespace App\Filament\Concerns;

use App\Filament\Support\ProductVisibility;
use App\Models\Product;

/**
 * Lets a plan form hide its plan from the public pricing tables without
 * deactivating it.
 *
 * The flag lives on the catalogue product (products.is_hidden), but the admin
 * edits the *plan* — so, like the price fields, the toggle is declared
 * `dehydrated(false)` and written through to the product after the plan saves.
 *
 * Hiding is visibility only: a hidden plan still prices, adds to the cart and
 * checks out exactly as before. That is the whole point — it is how a plan is
 * sold to named customers by link and to nobody else.
 */
trait SyncsProductVisibility
{
    /** Populate the hide toggle from the linked product when editing. */
    protected function fillVisibilityData(array $data, ?Product $product): array
    {
        $data[ProductVisibility::FIELD] = (bool) $product?->is_hidden;

        return $data;
    }

    /** Write the hide toggle back to the product after the plan saves. */
    protected function syncVisibility(?Product $product): void
    {
        if (! $product || ! array_key_exists(ProductVisibility::FIELD, $this->data)) {
            return;
        }

        $product->update(['is_hidden' => (bool) $this->data[ProductVisibility::FIELD]]);
    }
}
