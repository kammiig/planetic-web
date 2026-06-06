<?php

namespace App\Models;

use App\Enums\ProductType;
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, ProductType|string $type): Builder
    {
        return $query->where('type', $type instanceof ProductType ? $type->value : $type);
    }

    /** Cheapest active price for display ("from £x"). */
    public function priceFor(string $billingCycle = 'one_time'): ?ProductPrice
    {
        return $this->activePrices->firstWhere('billing_cycle', $billingCycle)
            ?? $this->activePrices->sortBy('amount')->first();
    }
}
