<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id', 'product_id', 'product_price_id', 'item_type', 'name',
        'domain_name', 'quantity', 'unit_price', 'total', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            // Explicit int casts: MySQL with emulated prepares returns these
            // as strings, which broke strict ownership comparisons.
            'cart_id' => 'integer',
            'product_id' => 'integer',
            'product_price_id' => 'integer',
            'item_type' => ItemType::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }
}
