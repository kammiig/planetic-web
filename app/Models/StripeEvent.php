<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency ledger for Stripe webhook events. The unique stripe_event_id
 * guarantees a given event is only ever processed once, even if Stripe
 * delivers it multiple times.
 */
class StripeEvent extends Model
{
    protected $fillable = [
        'stripe_event_id', 'type', 'status', 'payload', 'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessed(): void
    {
        $this->forceFill(['status' => 'processed', 'processed_at' => now()])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill(['status' => 'failed', 'error_message' => $message])->save();
    }
}
