<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Per-page SEO metadata keyed by route name. The public layout reads the row
 * for the current route (cached) and falls back to defaults when absent.
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
