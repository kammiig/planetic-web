<?php

namespace App\Http\Middleware;

use App\Support\Region;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the regional storefront for the request from its ROUTE PREFIX —
 * never from the visitor's IP.
 *
 * That restraint is the whole point. Cloudflare will not vary its cache by
 * CF-IPCountry on Free/Pro plans, and Googlebot crawls mostly from US IPs, so
 * any IP-driven change to a cacheable page's body would both poison the cache
 * and hide the GBP pages from Google. A given URL therefore returns identical
 * HTML to every visitor and every crawler.
 *
 * Geolocation is still used — but only through the no-store /region-hint
 * endpoint, which lets the page offer a client-side "switch region?" prompt
 * without the HTML itself ever differing. See RegionHintController.
 */
class ResolveRegion
{
    public function __construct() {}

    public function handle(Request $request, Closure $next, ?string $regionKey = null): Response
    {
        $region = $regionKey !== null && Region::exists($regionKey)
            ? Region::make($regionKey)
            : Region::default();

        Region::setCurrent($region);

        // Available to controllers and views without threading it through every
        // signature; the layout reads it for hreflang and the currency selector.
        $request->attributes->set('region', $region);
        view()->share('region', $region);

        $response = $next($request);

        // Region is a property of the URL, so caches may share this response
        // freely between visitors. Vary on nothing region-related.
        return $response;
    }
}
