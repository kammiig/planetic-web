<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_id', 'invoice_id', 'provider', 'provider_payment_id',
        'provider_customer_id', 'amount', 'currency', 'status', 'failure_reason',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Never expose internal failure detail to customers — hide the raw
     * gateway reason by default in serialised output.
     */
    protected $hidden = ['failure_reason'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
