<?php

namespace App\Filament\Concerns;

use App\Models\Product;
use App\Support\Region;

/**
 * Lets a Filament resource page edit catalogue prices (monthly/yearly/one_time)
 * inline while keeping product_prices as the single checkout source of truth.
 * The price fields are declared `dehydrated(false)`, so they live in the form
 * state ($this->data) but never touch the resource's own model.
 *
 * Prices are edited per currency — one field per cycle per regional currency
 * (price_monthly, price_monthly_usd, …). They are deliberately independent
 * figures rather than a base price plus an exchange rate: "$249" is a pricing
 * decision, "$253.40" is an arithmetic accident. A currency left blank simply
 * means the product is not sold in that storefront.
 */
trait SyncsProductPrices
{
    /** Billing cycles that carry a price field, in form order. */
    private const CYCLES = ['monthly', 'yearly', 'one_time'];

    /**
     * Currencies to expose, keyed by currency code => form field suffix.
     * The default region's currency owns the unsuffixed field so existing
     * forms, tests and saved state keep working unchanged.
     *
     * @return array<string, string>
     */
    public static function priceCurrencies(): array
    {
        $out = [];
        $default = Region::default()->currency();

        foreach (Region::all() as $region) {
            $currency = $region->currency();
            $out[$currency] ??= $currency === $default ? '' : '_'.strtolower($currency);
        }

        return $out;
    }

    /** The form field name for a cycle + currency, e.g. "price_monthly_usd". */
    public static function priceField(string $cycle, string $suffix): string
    {
        return 'price_'.$cycle.$suffix;
    }

    /** Populate the price form fields from the linked product when editing. */
    protected function fillPriceData(array $data, ?Product $product): array
    {
        foreach (self::priceCurrencies() as $currency => $suffix) {
            foreach (self::CYCLES as $cycle) {
                $data[self::priceField($cycle, $suffix)] = $product?->prices()
                    ->where('billing_cycle', $cycle)
                    ->where('currency', $currency)
                    ->value('amount');
            }
        }

        return $data;
    }

    /** Write the entered prices back to product_prices after the record saves. */
    protected function syncPrices(?Product $product): void
    {
        if (! $product) {
            return;
        }

        foreach (self::priceCurrencies() as $currency => $suffix) {
            foreach (self::CYCLES as $cycle) {
                $field = self::priceField($cycle, $suffix);

                if (! array_key_exists($field, $this->data)) {
                    continue;
                }

                $amount = $this->data[$field];

                // Blank clears the price, withdrawing the product from that
                // storefront rather than leaving a stale figure on sale.
                if ($amount === null || $amount === '') {
                    $product->prices()
                        ->where('billing_cycle', $cycle)
                        ->where('currency', $currency)
                        ->delete();

                    continue;
                }

                $product->prices()->updateOrCreate(
                    ['billing_cycle' => $cycle, 'currency' => $currency],
                    ['amount' => $amount, 'is_active' => true],
                );
            }
        }
    }
}
