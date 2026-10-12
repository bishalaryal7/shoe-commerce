<?php

namespace App\Services;

use App\Jobs\SendOrderEmail;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Notifications\StoreUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderEmails
{
    public function record(Order $order, string $event): void
    {
        $original = OrderEmail::where('order_id', $order->id)->where('event', 'created')->first();
        $locale = $original?->payload['locale'] ?? app()->getLocale();
        $record = OrderEmail::firstOrCreate(['order_id' => $order->id, 'event' => $event], ['payload' => [
            'locale' => in_array($locale, ['en', 'ne'], true) ? $locale : 'en',
            'name' => $order->name, 'email' => $order->email, 'reference' => $order->reference, 'currency' => $order->currency,
            'subtotal' => $order->subtotal, 'shipping_amount' => $order->shipping_amount, 'tax_amount' => $order->tax_amount, 'total' => $order->total,
            'payment_method' => $order->payment_method, 'status' => $order->status, 'payment_status' => $order->payment_status,
            'tracking_carrier' => $order->tracking_carrier, 'tracking_number' => $order->tracking_number,
            'discount_amount' => $order->discount_amount, 'promotion_code' => $order->promotion_code,
            'items' => $order->items->map(fn ($item) => ['name' => $item->name, 'size' => $item->size, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all(),
        ]]);
        if ($record->wasRecentlyCreated) {
            if ($order->user) {
                [$title, $message] = match ($event) {
                    'confirmed' => [__('Your order is confirmed'), __('The shop confirmed your order and will prepare it for delivery.')],
                    'shipped' => [__('Your order has shipped'), __('Your order is on its way. Open tracking for the latest details.')],
                    'fulfilled' => [__('Your order was delivered'), __('Your order has been marked as delivered. We hope you love your shoes!')],
                    'cancelled' => [__('Your order was cancelled'), __('The shop cancelled your order. Open the order page for details.')],
                    'payment_expired' => [__('Your checkout expired'), __('Payment was not completed before checkout expired. The reserved sizes are available again.')],
                    'paid' => [__('Payment received'), __('Your payment was confirmed for this order.')],
                    'refunded' => [__('Payment refunded'), __('A refund was recorded for this order.')],
                    default => [__('We received your order'), __('Your order request is saved. Open the order page to follow its progress.')],
                };
                $order->user->notify(new StoreUpdate($title, $message, '/orders/track/'.$order->reference, 'order'));
            }

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
