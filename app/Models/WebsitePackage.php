<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-editable detail for a bespoke website-development package. Price lives
 * in the linked Product's product_prices (one_time); this model owns the
 * marketing content, feature list, project intake questions and inclusions.
 */
class WebsitePackage extends Model
{
    protected $fillable = [
        'product_id', 'name', 'tagline', 'description', 'features',
        'intake_questions', 'includes_free_domain', 'includes_hosting',
        'hosting_package_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'intake_questions' => 'array',
            'includes_free_domain' => 'boolean',
            'includes_hosting' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hostingPackage(): BelongsTo
    {
        return $this->belongsTo(HostingPackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** One-time price from the linked product, falling back to config. */
    public function price(): float
    {
        return (float) ($this->product?->priceFor('one_time')?->amount
            ?? config('billing.website_package.price', 200.00));
    }
}
