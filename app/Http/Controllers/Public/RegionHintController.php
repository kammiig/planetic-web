<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The single place the visitor's location is read.
 *
 * Returning the geolocation hint as a small, explicitly uncacheable JSON
 * response — rather than branching inside a rendered page — is what lets every
 * public URL stay byte-identical for all visitors and crawlers. That in turn is
 * what makes the pages safely cacheable at Cloudflare (which does not vary its
 * cache by CF-IPCountry outside Enterprise plans) and keeps both storefronts
 * indexable by Googlebot, which crawls predominantly from US IP addresses.
 *
 * This endpoint never redirects. It only reports which storefront would suit
 * the visitor; the page offers a dismissible prompt and the visitor decides.
 */
class RegionHintController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $detected = null;
        $country = null;

        if (config('regions.detection.enabled', true)) {
            $header = (string) config('regions.detection.header', 'CF-IPCountry');
            $country = $request->header($header);
            $detected = Region::forCountry($country);
        }

        // An explicit choice from the selector always beats geolocation.
        $chosen = $request->cookie((string) config('regions.cookie', 'pw_region'));
        $hasChoice = is_string($chosen) && Region::exists($chosen);

        $suggested = $hasChoice ? Region::make($chosen) : $detected;

        $payload = [
            'suggested' => $suggested?->key,
            'currency' => $suggested?->currency(),
            'label' => $suggested?->name(),
            'flag' => $suggested?->flag(),
            'prefix' => $suggested?->prefix(),
            // True only for a genuine geolocation suggestion. Once the visitor
            // has chosen a region themselves there is nothing to prompt about.
            'prompt' => $suggested !== null && ! $hasChoice,
            'country' => is_string($country) ? strtoupper($country) : null,
        ];

        return response()
            ->json($payload)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            // Belt and braces: if any intermediary ignores no-store, at least
            // make the country part of the cache key.
            ->header('Vary', (string) config('regions.detection.header', 'CF-IPCountry'));
    }
}
