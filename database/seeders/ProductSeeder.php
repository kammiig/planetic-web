<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seeds the sellable catalogue. Prices are in GBP and are the single
     * server-side source of truth — the frontend never supplies prices.
     */
    public function run(): void
    {
        $catalogue = [
            [
                'name' => 'Complete Bespoke Website',
                'type' => ProductType::WebsitePackage,
                'description' => 'A complete bespoke website designed and built for you. Includes a free domain and hosting for the first year. Renewal applies after the first year.',
                'sort_order' => 1,
                'prices' => [
                    ['billing_cycle' => 'one_time', 'amount' => (float) config('billing.website_package.price', 200.00)],
                ],
            ],
            [
                'name' => 'Starter Hosting',
                'type' => ProductType::Hosting,
                'description' => 'Fast, secure cPanel hosting with free SSL and Cloudflare setup — ideal for a brochure website.',
                'sort_order' => 2,
                'prices' => [
                    ['billing_cycle' => 'monthly', 'amount' => 4.99],
                    ['billing_cycle' => 'yearly', 'amount' => 49.00],
                ],
            ],
            [
                'name' => 'Business Hosting',
                'type' => ProductType::Hosting,
                'description' => 'More power and storage for growing businesses, with multiple sites and mailboxes.',
                'sort_order' => 3,
                'prices' => [
                    ['billing_cycle' => 'monthly', 'amount' => 9.99],
                    ['billing_cycle' => 'yearly', 'amount' => 99.00],
                ],
            ],
            [
                'name' => 'Pro Hosting',
                'type' => ProductType::Hosting,
                'description' => 'High-performance hosting for busy sites that need headroom and speed.',
                'sort_order' => 4,
                'prices' => [
                    ['billing_cycle' => 'monthly', 'amount' => 19.99],
                    ['billing_cycle' => 'yearly', 'amount' => 199.00],
                ],
            ],
            [
                'name' => 'Agency / Ecommerce Hosting',
                'type' => ProductType::Hosting,
                'description' => 'Our most capable plan for agencies and online stores running multiple high-traffic sites.',
                'sort_order' => 5,
                'prices' => [
                    ['billing_cycle' => 'monthly', 'amount' => 39.99],
                    ['billing_cycle' => 'yearly', 'amount' => 399.00],
                ],
            ],
            [
                'name' => 'Domain Registration',
                'type' => ProductType::Domain,
                'description' => 'Register your domain with WHOIS privacy and automatic Cloudflare DNS setup.',
                'sort_order' => 6,
                'prices' => [
                    ['billing_cycle' => 'yearly', 'amount' => 12.99],
                ],
            ],
            [
                'name' => 'Website Maintenance',
                'type' => ProductType::Maintenance,
                'description' => 'Ongoing updates, backups, security monitoring and small content changes.',
                'sort_order' => 7,
                'prices' => [
                    ['billing_cycle' => 'monthly', 'amount' => 29.00],
                ],
            ],
        ];

        foreach ($catalogue as $entry) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($entry['name'])],
                [
                    'name' => $entry['name'],
                    'type' => $entry['type']->value,
                    'description' => $entry['description'],
                    'is_active' => true,
                    'sort_order' => $entry['sort_order'],
                ],
            );

            foreach ($entry['prices'] as $price) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'billing_cycle' => $price['billing_cycle'],
                    ],
                    [
                        'currency' => 'GBP',
                        'amount' => $price['amount'],
                        'setup_fee' => 0,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
