<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $plans = Product::ofType(ProductType::Hosting)
            ->active()
            ->with(['activePrices', 'hostingPackage'])
            ->orderBy('sort_order')
            ->get();

        return view('public.hosting', [
            'plans' => $plans,
        ]);
    }
}
