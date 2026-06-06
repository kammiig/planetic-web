<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id', 'billing_cycle', 'currency', 'amount', 'setup_fee',
        'stripe_price_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Amount in the smallest currency unit (pence) for Stripe. */
    public function amountInPence(): int
    {
        return (int) round(((float) $this->amount) * 100);
    }

    public function cycleLabel(): string
    {
        return match ($this->billing_cycle) {
            'monthly' => '/mo',
            'yearly' => '/yr',
            default => '',
        };
    }
}
