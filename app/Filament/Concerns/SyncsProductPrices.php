<?php

namespace App\Filament\Concerns;

use App\Models\Product;

/**
 * Lets a Filament resource page edit a catalogue price (monthly/yearly/one_time)
 * inline while keeping product_prices as the single checkout source of truth.
 * The price fields are declared `dehydrated(false)`, so they live in the form
 * state ($this->data) but never touch the resource's own model.
 */
trait SyncsProductPrices
{
    /** Populate the price form fields from the linked product when editing. */
    protected function fillPriceData(array $data, ?Product $product): array
    {
        $data['price_monthly'] = $product?->prices()->where('billing_cycle', 'monthly')->value('amount');
        $data['price_yearly'] = $product?->prices()->where('billing_cycle', 'yearly')->value('amount');
        $data['price_one_time'] = $product?->prices()->where('billing_cycle', 'one_time')->value('amount');

        return $data;
    }

    /** Write the entered prices back to product_prices after the record saves. */
    protected function syncPrices(?Product $product): void
    {
        if (! $product) {
            return;
        }

        $cycles = [
            'monthly' => 'price_monthly',
            'yearly' => 'price_yearly',
            'one_time' => 'price_one_time',
        ];

        foreach ($cycles as $cycle => $field) {
            if (! array_key_exists($field, $this->data)) {
                continue;
            }

            $amount = $this->data[$field];

            if ($amount === null || $amount === '') {
                continue;
            }

            $product->prices()->updateOrCreate(
                ['billing_cycle' => $cycle],
                ['currency' => 'GBP', 'amount' => $amount, 'is_active' => true],
            );
        }
    }
}
