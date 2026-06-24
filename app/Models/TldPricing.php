<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-managed per-TLD pricing. The customer-facing selling price for any
 * domain is resolved here (longest-matching suffix), so domain search and
 * checkout always use the admin-set price. cost_price/markup are admin-only.
 */
class TldPricing extends Model
{
    public const CACHE_KEY = 'tld_pricings.active';

    protected $fillable = [
        'tld', 'register_price', 'renew_price', 'transfer_price', 'cost_price',
        'markup', 'free_eligible', 'is_featured', 'is_active', 'sort_order', 'cost_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'markup' => 'decimal:2',
            'free_eligible' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'cost_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Cached collection of active TLDs keyed by their (lower-case) tld string. */
    public static function activeMap(): Collection
    {
        // Cache plain row arrays (the database cache store corrupts objects),
        // then rehydrate into models fresh on each read.
        $rows = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->where('is_active', true)->orderBy('sort_order')->get()->toArray(),
        );

        return static::hydrate($rows)->keyBy(fn (self $t) => strtolower($t->tld));
    }

    /** Resolve the pricing row for a full domain by longest-matching suffix. */
    public static function forDomain(string $domain): ?self
    {
        $domain = strtolower(trim($domain, ". \t\n"));
        $parts = explode('.', $domain);
        $map = static::activeMap();

        // Try the longest suffix first: "a.b.co.uk" -> co.uk -> uk.
        for ($i = 1; $i < count($parts); $i++) {
            $candidate = implode('.', array_slice($parts, $i));
            if ($map->has($candidate)) {
                return $map->get($candidate);
            }
        }

        return null;
    }

    /** Customer-facing registration price for a domain (null when no match). */
    public static function priceForDomain(string $domain): ?float
    {
        $row = static::forDomain($domain);

        return $row ? (float) $row->register_price : null;
    }

    public function tldLabel(): string
    {
        return '.'.ltrim($this->tld, '.');
    }
}
