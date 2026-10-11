<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Variant;
use Illuminate\Validation\ValidationException;

class OrderRepository
{
    public function byCheckoutToken(string $token): ?Order
    {
        return Order::where('checkout_token', $token)->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function reserveItems(Order $order, array $lines, ?int $actor): void
    {
        foreach ($lines as $line) {
            if (! Variant::whereKey($line['id'])->where('stock', '>=', $line['quantity'])->decrement('stock', $line['quantity'])) {
                throw ValidationException::withMessages(['cart' => 'A selected size is no longer available in that quantity. Please review your bag.']);
            }
            InventoryMovement::create(['variant_id' => $line['id'], 'order_id' => $order->id, 'actor_id' => $actor, 'quantity' => -$line['quantity'], 'reason' => 'order_reserved']);
            $order->items()->create(['variant_id' => $line['id'], 'name' => $line['name'], 'sku' => $line['sku'], 'size' => $line['size'], 'color' => $line['color'], 'quantity' => $line['quantity'], 'unit_price' => $line['unit_price']]);
        }
        OrderEvent::create(['order_id' => $order->id, 'actor_id' => $actor, 'event' => 'created']);
    }

    public function lock(int $id): Order
    {
        return Order::lockForUpdate()->findOrFail($id);
    }

    public function update(Order $order, array $data): void
    {
        $order->update($data);
    }

    public function recordEvent(Order $order, string $event, ?int $actor, array $details = []): void
    {
        OrderEvent::create(['order_id' => $order->id, 'actor_id' => $actor, 'event' => $event, 'details' => $details ?: null]);
    }
}
