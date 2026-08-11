<?php

namespace App\Providers;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\TldPricing;
use App\Support\Region;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Supplies the storefront figures that marketing copy quotes — the website
 * package price and the cheapest domain — to every public view, in the
 * currency of the region being browsed.
 *
 * These used to be hardcoded ("£200", "from £9.56") in headlines, legal pages
 * and blog CTAs. Sharing them here means one storefront's prices can never leak
 * into another's copy, and a price change in admin updates every mention.
 *
 * Values are cached per region and flushed by the TldPricing cache bust, so the
 * extra queries do not land on every request.
 */
class RegionViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['public.*', 'partials.*', 'components.*', 'checkout.*', 'customer.*'], function ($view) {
            $region = Region::current();
            $data = $view->getData();

            // Never overwrite a value a controller deliberately passed in.
            if (! array_key_exists('websitePackagePrice', $data)) {
                $view->with('websitePackagePrice', $this->websitePackagePrice($region));
            }

            if (! array_key_exists('cheapestTld', $data)) {
                $view->with('cheapestTld', $this->cheapestTld($region));
            }
        });
    }

    private function websitePackagePrice(Region $region): ?float
    {
        return Cache::remember(
            'storefront.package-price.'.$region->key,
            now()->addMinutes(10),
            function () use ($region) {
                $amount = Product::ofType(ProductType::WebsitePackage)
                    ->active()
                    ->with('activePrices')
                    ->first()
                    ?->priceFor('one_time', $region->currency())
                    ?->amount;

                if ($amount !== null) {
                    return (float) $amount;
                }

                // The configured figure is GBP, so it is only a valid fallback
                // for the GBP storefront.
                return $region->currency() === 'GBP'
                    ? (float) config('billing.website_package.price', 200)
                    : null;
            },
        );
    }

    /** Cheapest published domain registration price in this storefront. */
    private function cheapestTld(Region $region): ?float
    {
        return Cache::remember(
            'storefront.cheapest-tld.'.$region->key,
            now()->addMinutes(10),
            fn () => TldPricing::activeMap()
                ->map(fn (TldPricing $t) => $t->registerPrice($region->currency()))
                ->filter(fn (?float $p) => $p !== null)
                ->min(),
        );
    }
}
