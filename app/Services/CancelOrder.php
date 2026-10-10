<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function cancel(Order $order, ?int $actorId, bool $staff = false): void
    {
        DB::transaction(function () use ($order, $actorId, $staff) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($locked->status, $staff ? ['pending_confirmation', 'confirmed'] : ['pending_confirmation']), 422, 'This order can no longer be cancelled here.');
            abort_if(in_array($locked->payment_status, ['pending', 'review', 'paid']), 422, 'Verify or refund the online payment before cancelling this order.');
            foreach ($locked->items as $item) {
                Variant::whereKey($item->variant_id)->increment('stock', $item->quantity);
                InventoryMovement::create(['variant_id' => $item->variant_id, 'order_id' => $locked->id, 'actor_id' => $actorId, 'quantity' => $item->quantity, 'reason' => 'order_cancelled']);
            }
            $locked->update(['status' => 'cancelled']);
            OrderEvent::create(['order_id' => $locked->id, 'actor_id' => $actorId, 'event' => 'cancelled']);
            app(OrderEmails::class)->record($locked, 'cancelled');
        });
    }
}
