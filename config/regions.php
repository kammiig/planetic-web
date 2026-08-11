<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Regional Storefronts
    |--------------------------------------------------------------------------
    |
    | The site is served as one tree per region, each on its own URL prefix:
    |
    |     planeticweb.com/          → UK      (GBP, canonical root, x-default)
    |     planeticweb.com/int/      → World   (USD)
    |
    | SEPARATE URLS ARE DELIBERATE AND LOAD-BEARING. Two independent constraints
    | force them, and both are solved by the same decision:
    |
    |  1. Caching. Cloudflare does not honour `Vary: CF-IPCountry` on Free/Pro
    |     plans and custom cache keys are Enterprise-only. Varying content by
    |     country on ONE url means the first visitor's country decides what is
    |     cached for everybody.
    |  2. Indexing. Googlebot crawls predominantly from US IPs. Same-url
    |     geo-variation means Google only ever sees one version, so the GBP
    |     pages never get indexed — losing exactly the UK rankings that matter.
    |
    | Therefore the region of a request is decided SOLELY by its URL prefix.
    | Geolocation never changes what a given URL returns; it only powers a
    | client-side "you might want the other region" suggestion. See
    | App\Support\Region and App\Http\Middleware\ResolveRegion.
    |
    */

    'default' => env('DEFAULT_REGION', 'uk'),

    /*
    | Cookie recording an explicit region choice from the selector. It drives
    | the suggestion banner and the post-login landing region only — it must
    | NEVER be used to vary the body of a cacheable page.
    */
    'cookie' => 'pw_region',

    'cookie_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Geolocation
    |--------------------------------------------------------------------------
    |
    | Cloudflare injects CF-IPCountry on every proxied request (free on all
    | plans), so no GeoIP database is shipped or updated on the server. When the
    | header is absent — direct origin hits, local development, a non-proxied
    | record — detection simply yields null and the default region stands.
    |
    */

    'detection' => [
        'enabled' => filter_var(env('REGION_DETECTION_ENABLED', true), FILTER_VALIDATE_BOOL),
        'header' => env('REGION_COUNTRY_HEADER', 'CF-IPCountry'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Regions
    |--------------------------------------------------------------------------
    |
    | `countries` maps ISO-3166-1 alpha-2 codes to a region; anything unmatched
    | falls to the region flagged `catch_all`.
    |
    | `tax.registered` reflects a real-world fact, not a display preference:
    | until the business is VAT registered there must be no VAT line at all.
    | Showing "VAT £0.00" implies a registration that does not exist. Set
    | VAT_REGISTERED=true (and VAT_NUMBER) once HMRC registration completes.
    |
    */

    'regions' => [

        'uk' => [
            'name' => 'United Kingdom',
            'short' => 'UK',
            'flag' => '🇬🇧',
            'prefix' => '',
            'currency' => 'GBP',
            'symbol' => '£',
            'locale' => 'en_GB',
            'hreflang' => 'en-GB',
            'catch_all' => false,
            'countries' => ['GB', 'GG', 'JE', 'IM'],
            'default_tld' => 'co.uk',
            'tax' => [
                'registered' => filter_var(env('VAT_REGISTERED', false), FILTER_VALIDATE_BOOL),
                'rate' => (float) env('VAT_RATE', 0.20),
                'label' => 'VAT',
                'number' => env('VAT_NUMBER'),
                // UK consumer pricing is quoted tax-inclusive by law.
                'inclusive' => true,
            ],
        ],

        'int' => [
            'name' => 'International',
            'short' => 'International',
            'flag' => '🌍',
            'prefix' => 'int',
            'currency' => 'USD',
            'symbol' => '$',
            'locale' => 'en_US',
            'hreflang' => 'en',
            'catch_all' => true,
            'countries' => [],
            'default_tld' => 'com',
            'tax' => [
                // No UK VAT is charged on services supplied outside the UK, and
                // no US sales-tax nexus is assumed. Prices are shown as final.
                'registered' => false,
                'rate' => 0.0,
                'label' => 'Tax',
                'number' => null,
                'inclusive' => true,
            ],
        ],

    ],

];
