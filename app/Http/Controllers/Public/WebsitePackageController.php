<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Product;
use App\Models\WebsitePackage;
use Illuminate\View\View;

class WebsitePackageController extends Controller
{
    public function index(): View
    {
        $package = WebsitePackage::active()
            ->with('product.activePrices')
            ->orderBy('sort_order')
            ->first();

        // Fall back to the catalogue product if no website_packages row exists yet.
        $product = $package?->product
            ?? Product::ofType(ProductType::WebsitePackage)->active()->with('activePrices')->first();

        return view('public.website-package', [
            'package' => $package,
            'product' => $product,
            'price' => $package?->price() ?? $product?->priceFor('one_time')?->amount ?? config('billing.website_package.price'),
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'faqs' => Faq::active()->forPage('website-package')->orderBy('sort_order')->get(),
        ]);
    }
}
