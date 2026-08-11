<?php

namespace Database\Seeders;

use App\Models\TldPricing;
use Illuminate\Database\Seeder;

/**
 * Seeds an initial domain (TLD) price book for both regional storefronts.
 * register_price is the customer-facing GBP selling price and
 * register_price_usd its international counterpart; cost_price is an admin-only
 * reference figure that can later be synced from the registrar. Idempotent via
 * updateOrCreate on tld.
 *
 * The USD figures are deliberate round numbers, NOT the GBP prices run through
 * an exchange rate — "$11.99" is a price, "$11.38" is an arithmetic accident.
 *
 * .co.uk and .uk carry no USD price: they are UK-only names, so the
 * international storefront simply does not offer them (TldPricing::availableIn
 * returns false and they drop out of search suggestions).
 */
class TldPricingSeeder extends Seeder
{
    public function run(): void
    {
        $tlds = [
            // tld, register, renew, transfer, cost, featured, free_eligible, usdRegister, usdRenew
            ['co.uk', 8.99, 8.99, 8.99, 6.50, true, true, null, null],
            ['com', 12.99, 14.99, 12.99, 9.50, true, true, 15.99, 17.99],
            ['uk', 8.99, 8.99, 8.99, 6.50, true, true, null, null],
            ['net', 14.99, 16.99, 14.99, 11.00, false, true, 18.99, 20.99],
            ['org', 13.99, 15.99, 13.99, 10.50, true, true, 17.99, 19.99],
            ['io', 39.99, 44.99, 39.99, 32.00, true, false, 49.99, 55.99],
            ['co', 24.99, 27.99, 24.99, 20.00, false, false, 31.99, 34.99],
            ['online', 29.99, 34.99, 29.99, 4.00, false, true, 37.99, 43.99],
            ['shop', 27.99, 31.99, 27.99, 3.50, false, true, 34.99, 39.99],
            ['store', 49.99, 54.99, 49.99, 5.00, false, false, 62.99, 68.99],
            ['dev', 16.99, 18.99, 16.99, 13.00, false, true, 21.99, 23.99],
            ['app', 16.99, 18.99, 16.99, 13.00, false, true, 21.99, 23.99],
            ['info', 19.99, 21.99, 19.99, 3.00, false, true, 24.99, 27.99],
            ['biz', 17.99, 19.99, 17.99, 13.50, false, true, 22.99, 24.99],
            ['me', 19.99, 22.99, 19.99, 15.00, false, true, 24.99, 28.99],
        ];

        foreach ($tlds as $i => [$tld, $register, $renew, $transfer, $cost, $featured, $free, $usdRegister, $usdRenew]) {
            TldPricing::updateOrCreate(
                ['tld' => $tld],
                [
                    'register_price' => $register,
                    'register_price_usd' => $usdRegister,
                    'renew_price' => $renew,
                    'renew_price_usd' => $usdRenew,
                    'transfer_price' => $transfer,
                    'transfer_price_usd' => $usdRegister,
                    'cost_price' => $cost,
                    'markup' => round($register - $cost, 2),
                    'free_eligible' => $free,
                    'is_featured' => $featured,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }
    }
}
