<?php

namespace App\Filament\Support;

use App\Filament\Concerns\SyncsProductPrices;
use App\Support\Money;
use App\Support\Region;
use Filament\Forms\Components\TextInput;

/**
 * Builds the per-currency catalogue price inputs shared by the hosting and
 * website package forms — one field per billing cycle per regional storefront,
 * matching the field names SyncsProductPrices reads and writes.
 *
 * Currencies come from config/regions.php, so adding a third storefront adds
 * its price inputs to both admin forms without touching either.
 */
class PriceFields
{
    /**
     * @param  array<string, string>  $cycles  billing cycle => label
     * @return array<int, TextInput>
     */
    public static function make(array $cycles): array
    {
        $fields = [];

        foreach (SyncsProductPrices::priceCurrencies() as $currency => $suffix) {
            $symbol = Money::symbol($currency);
            $regionName = self::regionNameFor($currency);

            foreach ($cycles as $cycle => $label) {
                $fields[] = TextInput::make(SyncsProductPrices::priceField($cycle, $suffix))
                    ->label($label.' ('.$currency.')')
                    ->helperText($regionName.' storefront')
                    ->numeric()
                    ->prefix($symbol)
                    ->minValue(0)
                    ->dehydrated(false);
            }
        }

        return $fields;
    }

    private static function regionNameFor(string $currency): string
    {
        foreach (Region::all() as $region) {
            if ($region->currency() === $currency) {
                return $region->name();
            }
        }

        return $currency;
    }
}
