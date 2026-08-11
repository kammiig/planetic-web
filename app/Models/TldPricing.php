<?php

namespace App\Models;

use App\Support\Region;
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
        'tld', 'register_price', 'register_price_usd', 'renew_price', 'renew_price_usd',
        'transfer_price', 'transfer_price_usd', 'cost_price',
        'markup', 'free_eligible', 'is_featured', 'is_active', 'sort_order', 'cost_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'register_price_usd' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'renew_price_usd' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'transfer_price_usd' => 'decimal:2',
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

    /**
     * Customer-facing registration price for a domain in a given currency
     * (defaults to the current storefront's). Null when the TLD is unlisted, or
     * when it carries no price in that currency — a TLD without a USD price is
     * simply not sold in the international storefront rather than being
     * converted at some exchange rate the admin never chose.
     */
    public static function priceForDomain(string $domain, ?string $currency = null): ?float
    {
        return static::forDomain($domain)?->registerPrice($currency);
    }

    /** Registration price in a currency, or null when not published in it. */
    public function registerPrice(?string $currency = null): ?float
    {
        return $this->priceIn('register_price', $currency);
    }

    public function renewPrice(?string $currency = null): ?float
    {
        return $this->priceIn('renew_price', $currency);
    }

    public function transferPrice(?string $currency = null): ?float
    {
        return $this->priceIn('transfer_price', $currency);
    }

    /** Whether this TLD is sold in the given currency at all. */
    public function availableIn(?string $currency = null): bool
    {
        return $this->registerPrice($currency) !== null;
    }

    /**
     * GBP lives in the base column; every other supported currency has its own
     * suffixed column (register_price_usd). An unsupported currency, or an
     * empty column, yields null.
     */
    private function priceIn(string $column, ?string $currency = null): ?float
    {
        $currency = strtoupper($currency ?: Region::current()->currency());

        if ($currency !== 'GBP') {
            $column .= '_'.strtolower($currency);
        }

        $value = $this->getAttribute($column);

        return $value === null || $value === '' ? null : (float) $value;
    }

    public function tldLabel(): string
    {
        return '.'.ltrim($this->tld, '.');
    }
}
