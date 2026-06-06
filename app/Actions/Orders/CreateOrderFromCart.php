<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Converts a cart into a pending Order with its line items. All totals are
 * recalculated from the catalogue — the cart's stored values are themselves
 * server-computed, never client-supplied.
 */
class CreateOrderFromCart
{
    public function handle(Cart $cart, User $user): Order
    {
        return DB::transaction(function () use ($cart, $user) {
            $cart->load('items')->recalculate();

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'TMP-'.uniqid(),
                'status' => OrderStatus::Pending->value,
                'payment_status' => PaymentStatus::Pending->value,
                'currency' => $cart->currency,
                'subtotal' => $cart->subtotal,
                'discount_total' => $cart->discount_total,
                'tax_total' => $cart->tax_total,
                'total' => $cart->total,
            ]);

            $order->update(['order_number' => 'ORD-'.(10000 + $order->id)]);

            foreach ($cart->items as $cartItem) {
                $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_price_id' => $cartItem->product_price_id,
                    'item_type' => $cartItem->item_type->value,
                    'name' => $cartItem->name,
                    'domain_name' => $cartItem->domain_name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total' => (float) $cartItem->unit_price * (int) $cartItem->quantity,
                    'metadata' => $cartItem->metadata,
                ]);
            }

            // Mark the cart converted so it is not reused for another order.
            $cart->update(['status' => 'converted']);

            return $order->load('items');
        });
    }
}
