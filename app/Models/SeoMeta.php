<?php

namespace App\Models;

use App\Support\Copy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Per-page SEO metadata keyed by route name. The public layout reads the row
 * for the current route (cached) and falls back to defaults when absent.
 *
 * Regional storefronts use their own prefixed keys ("int.home"). Titles and
 * descriptions support the storefront tokens in App\Support\Copy (":price",
 * ":currency", ":symbol") so one record cannot advertise the wrong currency.
 */
class SeoMeta extends Model
{
    public const CACHE_KEY = 'seo_metas.map';

    protected $fillable = [
        'page_key', 'label', 'meta_title', 'meta_description', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'twitter_card',
        'twitter_title', 'twitter_description', 'schema_json', 'noindex',
    ];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function ogTitle(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function ogDescription(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function twitterTitle(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function twitterDescription(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    public static function forKey(?string $key): ?self
    {
        if (! $key) {
            return null;
        }

        // Cache plain row arrays (the database cache store corrupts objects),
        // then rehydrate fresh on read.
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => static::all()->toArray());

        return static::hydrate($rows)->firstWhere('page_key', $key);
    }
}
