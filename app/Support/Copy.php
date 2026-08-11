<?php

namespace App\Support;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Substitutes storefront tokens in admin-editable marketing copy.
 *
 * Copy stored in the database ("with the :price website package your domain is
 * free…") outlives any one storefront, so it cannot hardcode a currency. These
 * tokens let one admin-written sentence render correctly in every region:
 *
 *   :price      the website package price, in this storefront's currency
 *   :currency   the ISO code, e.g. GBP / USD
 *   :symbol     the currency symbol, e.g. £ / $
 *
 * Text containing no token is returned untouched, so existing content keeps
 * working exactly as written.
 *
 * DELIBERATELY NOT APPLIED TO TESTIMONIALS. A testimonial is a real customer's
 * words; rewriting a figure inside a quotation would put a price in someone's
 * mouth that they never said. Quotes stay verbatim — if a UK customer said
 * "£200", that is what they said.
 */
class Copy
{
    /** Returns null/'' unchanged so callers can keep using ?: fallbacks. */
    public static function localise(?string $text): ?string
    {
        if ($text === null || $text === '' || ! str_contains($text, ':')) {
            return $text;
        }

        $region = Region::current();
        $price = self::packagePrice($region);

        return strtr($text, [
            ':price' => $price === null ? '' : Money::compact($price, $region->currency()),
            ':currency' => $region->currency(),
            ':symbol' => $region->symbol(),
        ]);
    }

    /**
     * The website package price in a region's currency, or null when the
     * package is not sold there. Cached per region — this runs on every piece
     * of admin copy rendered on a page.
     */
    private static function packagePrice(Region $region): ?float
    {
        return Cache::remember(
            'storefront.copy-price.'.$region->key,
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

                // The configured figure is GBP, so it is only valid there.
                return $region->currency() === 'GBP'
                    ? (float) config('billing.website_package.price', 200)
                    : null;
            },
        );
    }
}
