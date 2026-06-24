<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $plans = Product::ofType(ProductType::Hosting)
            ->active()
            ->whereHas('hostingPackage', fn ($q) => $q->where('is_active', true))
            ->with(['activePrices', 'hostingPackage'])
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (Product $p) => $p->hostingPackage?->sort_order ?? $p->sort_order)
            ->values();

        return view('public.hosting', [
            'plans' => $plans,
            'faqs' => Faq::active()->forPage('hosting')->orderBy('sort_order')->get(),
        ]);
    }
}
