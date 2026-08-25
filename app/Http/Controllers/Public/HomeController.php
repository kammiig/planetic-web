<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\TldPricing;
use App\Models\WebsitePackage;
use App\Support\Region;

class HomeController extends Controller
{
    public function index()
    {
        $hostingPlans = Product::query()
            ->where('type', ProductType::Hosting->value)
            ->where('is_active', true)
            // Hidden plans stay buyable by direct link but never appear in a
            // public grid — see Product::scopeListed().
            ->listed()
            ->whereHas('hostingPackage', fn ($q) => $q->where('is_active', true))
            ->with(['activePrices', 'hostingPackage'])
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (Product $p) => $p->hostingPackage?->sort_order ?? $p->sort_order)
            ->values();

        // The homepage shows a single, opinionated hosting offer (not the full
        // grid): the admin-flagged popular plan, falling back to the first one.
        $businessHosting = $hostingPlans->first(fn (Product $p) => (bool) $p->hostingPackage?->is_popular)
            ?? $hostingPlans->first();

        $websitePackage = WebsitePackage::active()
            ->with('product.activePrices')
            ->orderBy('sort_order')
            ->first();

        return view('public.home', [
            'hostingPlans' => $hostingPlans,
            'businessHosting' => $businessHosting,
            'websitePackage' => $websitePackage,
            // price() already refuses to quote the GBP config figure under
            // another currency, so this is null when the package is not sold
            // in this storefront.
            'websitePackagePrice' => $websitePackage?->price(),
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'testimonials' => Testimonial::active()->orderBy('sort_order')->get(),
            'faqs' => Faq::active()->forPage('home')->orderBy('sort_order')->get(),
            // A TLD with no price in this storefront's currency is withheld
            // rather than shown at 0.00 — .co.uk carries no USD price.
            'featuredTlds' => TldPricing::active()
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (TldPricing $t) => $t->availableIn(Region::current()->currency()))
                ->take(6)
                ->values(),
        ]);
    }
}
