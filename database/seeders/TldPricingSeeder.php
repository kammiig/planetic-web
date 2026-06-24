<?php

namespace Database\Seeders;

use App\Models\TldPricing;
use Illuminate\Database\Seeder;

/**
 * Seeds an initial domain (TLD) price book in GBP. register_price is the
 * customer-facing selling price; cost_price is an admin-only reference figure
 * that can later be synced from Porkbun. Idempotent via updateOrCreate on tld.
 */
class TldPricingSeeder extends Seeder
{
    public function run(): void
    {
        $tlds = [
            // tld, register, renew, transfer, cost, featured, free_eligible, sort
            ['co.uk', 8.99, 8.99, 8.99, 6.50, true, true],
            ['com', 12.99, 14.99, 12.99, 9.50, true, true],
            ['uk', 8.99, 8.99, 8.99, 6.50, true, true],
            ['net', 14.99, 16.99, 14.99, 11.00, false, true],
            ['org', 13.99, 15.99, 13.99, 10.50, true, true],
            ['io', 39.99, 44.99, 39.99, 32.00, true, false],
            ['co', 24.99, 27.99, 24.99, 20.00, false, false],
            ['online', 29.99, 34.99, 29.99, 4.00, false, true],
            ['shop', 27.99, 31.99, 27.99, 3.50, false, true],
            ['store', 49.99, 54.99, 49.99, 5.00, false, false],
            ['dev', 16.99, 18.99, 16.99, 13.00, false, true],
            ['app', 16.99, 18.99, 16.99, 13.00, false, true],
            ['info', 19.99, 21.99, 19.99, 3.00, false, true],
            ['biz', 17.99, 19.99, 17.99, 13.50, false, true],
            ['me', 19.99, 22.99, 19.99, 15.00, false, true],
        ];

        foreach ($tlds as $i => [$tld, $register, $renew, $transfer, $cost, $featured, $free]) {
            TldPricing::updateOrCreate(
                ['tld' => $tld],
                [
                    'register_price' => $register,
                    'renew_price' => $renew,
                    'transfer_price' => $transfer,
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
