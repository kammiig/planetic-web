<?php

namespace App\Actions\Provisioning;

use App\Enums\HostingStatus;
use App\Enums\ItemType;
use App\Enums\SubscriptionStatus;
use App\Models\HostingAccount;
use App\Models\Order;

/**
 * Creates renewal subscriptions for an order's services — but ONLY once they are
 * actually provisioned and active. Called when provisioning completes, so a
 * failed domain/hosting never produces a phantom renewal/auto-renew record.
 * Idempotent (keyed on the hosting account), so repeated calls never duplicate.
 */
class ActivateOrderSubscriptions
{
    public function handle(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->item_type !== ItemType::Hosting) {
                continue;
            }

            // The active hosting account for this order/plan. If hosting never
            // came up (e.g. domain registration failed), there is none — and we
            // deliberately create no renewal record.
            $account = HostingAccount::where('order_id', $order->id)
                ->where('status', HostingStatus::Active->value)
                ->whereHas('hostingPackage', fn ($q) => $q->where('product_id', $item->product_id))
                ->first()
                ?? HostingAccount::where('order_id', $order->id)
                    ->where('status', HostingStatus::Active->value)
                    ->first();

            if (! $account) {
                continue;
            }

            $cycle = $item->metadata['billing_cycle'] ?? 'monthly';
            $nextRenewal = $cycle === 'yearly' ? now()->addYear() : now()->addMonth();

            $order->user->subscriptions()->firstOrCreate(
                ['hosting_account_id' => $account->id],
                [
                    'product_id' => $item->product_id,
                    'status' => SubscriptionStatus::Active->value,
                    'billing_cycle' => $cycle,
                    'currency' => $order->currency,
                    'amount' => $item->unit_price,
                    'current_period_start' => now()->toDateString(),
                    'current_period_end' => $nextRenewal->toDateString(),
                    'next_renewal_date' => $nextRenewal->toDateString(),
                ],
            );
        }
    }
}
