<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $hostingPlans = Product::query()
            ->where('type', 'hosting')
            ->where('is_active', true)
            ->with('activePrices')
            ->orderBy('sort_order')
            ->get();

        return view('public.home', [
            'hostingPlans' => $hostingPlans,
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'websitePackagePrice' => config('billing.website_package.price'),
        ]);
    }
}
