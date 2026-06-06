<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class WebsitePackageController extends Controller
{
    public function index(): View
    {
        $product = Product::ofType(ProductType::WebsitePackage)->active()->with('activePrices')->first();

        return view('public.website-package', [
            'product' => $product,
            'price' => $product?->priceFor('one_time')?->amount ?? config('billing.website_package.price'),
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
        ]);
    }
}
