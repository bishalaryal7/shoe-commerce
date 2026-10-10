<?php

namespace App\Services;

use App\Jobs\SendOrderEmail;
use App\Models\Order;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderEmails
{
    public function record(Order $order, string $event): void
    {
        $record = OrderEmail::firstOrCreate(['order_id' => $order->id, 'event' => $event], ['payload' => [
            'name' => $order->name, 'email' => $order->email, 'reference' => $order->reference, 'currency' => $order->currency,
            'subtotal' => $order->subtotal, 'shipping_amount' => $order->shipping_amount, 'tax_amount' => $order->tax_amount, 'total' => $order->total,
            'status' => $order->status, 'payment_status' => $order->payment_status,
            'items' => $order->items->map(fn ($item) => ['name' => $item->name, 'size' => $item->size, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all(),
        ]]);
        if ($record->wasRecentlyCreated) {
            DB::afterCommit(function () use ($record) {
                try {
                    SendOrderEmail::dispatch($record->id);
                } catch (\Throwable $e) {
                    Log::warning('Order email remains in outbox for retry.', ['email_id' => $record->id]);
                }
            });
        }
    }
}
