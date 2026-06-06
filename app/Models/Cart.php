<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'session_id', 'currency', 'subtotal',
        'discount_total', 'tax_total', 'total', 'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Recalculate cart totals from its items, server-side only.
     * Never trust a price supplied by the frontend.
     */
    public function recalculate(): static
    {
        $subtotal = $this->items()->sum('total');

        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal - (float) $this->discount_total + (float) $this->tax_total);
        $this->save();

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }
}
