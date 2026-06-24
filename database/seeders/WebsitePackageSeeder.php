<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\HostingPackage;
use App\Models\Product;
use App\Models\WebsitePackage;
use Illuminate\Database\Seeder;

/**
 * Seeds the editable detail for the bespoke website package, linked to the
 * existing "Complete Bespoke Website" product (price stays in product_prices).
 */
class WebsitePackageSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::ofType(ProductType::WebsitePackage)->orderBy('sort_order')->first();

        if (! $product) {
            return;
        }

        $starterHosting = HostingPackage::query()
            ->whereHas('product', fn ($q) => $q->where('slug', 'starter-hosting'))
            ->first();

        WebsitePackage::firstOrCreate(
            ['product_id' => $product->id],
            [
                'name' => 'Complete Bespoke Website',
                'tagline' => 'Everything done for you, start to finish',
                'description' => 'A complete, custom-built website designed around your brand — with your domain, hosting, SSL, DNS and email all set up for you.',
                'features' => [
                    'Custom design built for your business',
                    'Free domain for the first year',
                    'Free hosting for the first year',
                    'SSL, DNS and email set up for you',
                    'Mobile-friendly and lightning fast',
                    'Contact form and Google-ready setup',
                    'Two rounds of revisions included',
                ],
                'intake_questions' => [
                    ['label' => 'Business name', 'type' => 'text', 'required' => true],
                    ['label' => 'What does your business do?', 'type' => 'textarea', 'required' => true],
                    ['label' => 'Preferred domain name', 'type' => 'text', 'required' => false],
                    ['label' => 'Pages you need (Home, About, Services, Contact…)', 'type' => 'textarea', 'required' => false],
                    ['label' => 'Brand colours or websites you like', 'type' => 'textarea', 'required' => false],
                ],
                'includes_free_domain' => true,
                'includes_hosting' => true,
                'hosting_package_id' => $starterHosting?->id,
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }
}
