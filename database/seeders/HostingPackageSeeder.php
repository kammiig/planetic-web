<?php

namespace Database\Seeders;

use App\Models\HostingPackage;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostingPackageSeeder extends Seeder
{
    /**
     * Maps each public hosting product to its real WHM package name. The WHM
     * package name is what is sent to the WHM createacct API.
     */
    public function run(): void
    {
        $packages = [
            'Starter Hosting' => [
                'whm_package_name' => 'planetic_starter',
                'disk_limit_mb' => 10240,
                'bandwidth_limit_mb' => 102400,
                'email_accounts_limit' => 10,
                'database_limit' => 5,
                'domain_limit' => 1,
            ],
            'Business Hosting' => [
                'whm_package_name' => 'planetic_business',
                'disk_limit_mb' => 51200,
                'bandwidth_limit_mb' => 512000,
                'email_accounts_limit' => 50,
                'database_limit' => 25,
                'domain_limit' => 5,
            ],
            'Pro Hosting' => [
                'whm_package_name' => 'planetic_pro',
                'disk_limit_mb' => 102400,
                'bandwidth_limit_mb' => 1024000,
                'email_accounts_limit' => 200,
                'database_limit' => 100,
                'domain_limit' => 15,
            ],
            'Agency / Ecommerce Hosting' => [
                'whm_package_name' => 'planetic_agency',
                'disk_limit_mb' => 204800,
                'bandwidth_limit_mb' => null, // unmetered
                'email_accounts_limit' => null,
                'database_limit' => null,
                'domain_limit' => null,
            ],
        ];

        foreach ($packages as $productName => $attributes) {
            $product = Product::where('slug', Str::slug($productName))->first();

            if (! $product) {
                continue;
            }

            HostingPackage::updateOrCreate(
                ['product_id' => $product->id],
                array_merge($attributes, [
                    'name' => $productName,
                    'is_active' => true,
                ]),
            );
        }
    }
}
