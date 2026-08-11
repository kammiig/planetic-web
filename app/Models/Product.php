<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Support\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function activePrices(): HasMany
    {
        return $this->prices()->where('is_active', true);
    }

    public function hostingPackage(): HasOne
    {
        return $this->hasOne(HostingPackage::class);
    }

    public function websitePackage(): HasOne
    {
        return $this->hasOne(WebsitePackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, ProductType|string $type): Builder
    {
        return $query->where('type', $type instanceof ProductType ? $type->value : $type);
    }

    /** Cheapest active price for display ("from £x"). */
    /**
     * The active price for a billing cycle in a currency (defaults to the
     * current storefront's).
     *
     * Prices are never converted between currencies: each storefront sells from
     * its own product_prices row, so a product with no row in the requested
     * currency returns null and is simply not purchasable there. Falling back to
     * another currency's row would quote a GBP figure under a "$" symbol.
     */
    public function priceFor(string $billingCycle = 'one_time', ?string $currency = null): ?ProductPrice
    {
        $currency = strtoupper($currency ?: Region::current()->currency());

        $inCurrency = $this->activePrices
            ->filter(fn (ProductPrice $price) => strtoupper((string) $price->currency) === $currency);

        return $inCurrency->firstWhere('billing_cycle', $billingCycle)
            ?? $inCurrency->sortBy('amount')->first();
    }

    /** Whether this product is sold in the given currency at all. */
    public function availableIn(?string $currency = null): bool
    {
        return $this->priceFor('one_time', $currency) !== null
            || $this->priceFor('monthly', $currency) !== null
            || $this->priceFor('yearly', $currency) !== null;
    }
}
