<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(private OrderEmails $emails, private StockAlerts $alerts) {}

    public function cancel(Order $order, ?int $actorId, bool $staff = false): void
    {
        DB::transaction(function () use ($order, $actorId, $staff) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($locked->status, $staff ? ['pending_confirmation', 'confirmed'] : ['pending_confirmation']), 422, __('This order can no longer be cancelled here.'));
            abort_if(in_array($locked->payment_status, ['pending', 'review', 'paid']), 422, __('Verify or refund the online payment before cancelling this order.'));
            foreach ($locked->items as $item) {
                Variant::whereKey($item->variant_id)->increment('stock', $item->quantity);
                InventoryMovement::create(['variant_id' => $item->variant_id, 'order_id' => $locked->id, 'actor_id' => $actorId, 'quantity' => $item->quantity, 'reason' => 'order_cancelled']);
                $variant = Variant::with('product')->find($item->variant_id);
                if ($variant) {
                    $this->alerts->restocked($variant);
                }
            }
            $locked->update(['status' => 'cancelled']);
            OrderEvent::create(['order_id' => $locked->id, 'actor_id' => $actorId, 'event' => 'cancelled']);
            $this->emails->record($locked, 'cancelled');
        });
    }
}
