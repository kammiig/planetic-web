<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\TldPricing;
use App\Models\WebsitePackage;

class HomeController extends Controller
{
    public function index()
    {
        $hostingPlans = Product::query()
            ->where('type', ProductType::Hosting->value)
            ->where('is_active', true)
            ->whereHas('hostingPackage', fn ($q) => $q->where('is_active', true))
            ->with(['activePrices', 'hostingPackage'])
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (Product $p) => $p->hostingPackage?->sort_order ?? $p->sort_order)
            ->values();

        $websitePackage = WebsitePackage::active()
            ->with('product.activePrices')
            ->orderBy('sort_order')
            ->first();

        return view('public.home', [
            'hostingPlans' => $hostingPlans,
            'websitePackage' => $websitePackage,
            'websitePackagePrice' => $websitePackage?->price() ?? config('billing.website_package.price'),
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'testimonials' => Testimonial::active()->orderBy('sort_order')->get(),
            'faqs' => Faq::active()->forPage('home')->orderBy('sort_order')->get(),
            'featuredTlds' => TldPricing::active()->where('is_featured', true)->orderBy('sort_order')->take(6)->get(),
        ]);
    }
}
