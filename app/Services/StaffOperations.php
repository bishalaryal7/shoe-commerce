<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\InventoryRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class StaffOperations
{
    public function __construct(private InventoryRepository $inventory, private OrderRepository $orders, private OrderEmails $emails, private CancelOrder $cancellation) {}

    public function stock(int $variantId, int $stock, int $actor): void
    {
        DB::transaction(fn () => $this->inventory->adjust($variantId, $stock, $actor));
    }

    public function transition(Order $order, string $status, int $actor): void
    {
        if ($status === 'cancelled') {
            $this->cancellation->cancel($order, $actor, true);

            return;
        }
        DB::transaction(function () use ($order, $status, $actor) {
            $locked = $this->orders->lock($order->id);
            $allowed = ['pending_confirmation' => ['confirmed'], 'confirmed' => ['fulfilled']];
            if (! in_array($status, $allowed[$locked->status] ?? [])) {
                abort(422, __('This order cannot move to that status.'));
            }
            if ($status === 'fulfilled' && $locked->payment_status !== 'paid') {
                abort(422, __('Record or verify payment before fulfilling this order.'));
            }
            $this->orders->update($locked, ['status' => $status]);
            $this->orders->recordEvent($locked, $status, $actor);
            $this->emails->record($locked, $status);
        });
    }

    public function shipping(Order $order, array $data, int $actor): void
    {
        DB::transaction(function () use ($order, $data, $actor) {
            $locked = $this->orders->lock($order->id);
            if ($data['status'] === 'shipped') {
                abort_unless($locked->status === 'confirmed' && $locked->payment_status === 'paid', 422, __('Only paid, confirmed orders can be shipped.'));
                $this->orders->update($locked, ['status' => 'shipped', 'tracking_carrier' => trim($data['tracking_carrier']), 'tracking_number' => trim($data['tracking_number']), 'shipped_at' => now()]);
                $this->orders->recordEvent($locked, 'shipped', $actor, ['carrier' => trim($data['tracking_carrier']), 'tracking_number' => trim($data['tracking_number'])]);
                $this->emails->record($locked, 'shipped');

                return;
            }
            abort_unless($locked->status === 'shipped', 422, __('Only shipped orders can be marked delivered.'));
            $this->orders->update($locked, ['status' => 'fulfilled', 'delivered_at' => now()]);
            $this->orders->recordEvent($locked, 'fulfilled', $actor);
            $this->emails->record($locked, 'fulfilled');
        });
    }

    public function offlinePayment(Order $order, string $reference, int $actor): void
    {
        DB::transaction(function () use ($order, $reference, $actor) {
            $locked = $this->orders->lock($order->id);
            if (! in_array($locked->payment_method, ['request', 'cod']) || $locked->payment_status !== 'unpaid' || ! in_array($locked->status, ['pending_confirmation', 'confirmed'])) {
                abort(422, __('This order cannot record an offline payment.'));
            }
            $this->orders->update($locked, ['payment_method' => 'offline', 'payment_status' => 'paid', 'paid_at' => now(), 'status' => 'confirmed', 'offline_payment_reference' => $reference]);
            $this->orders->recordEvent($locked, 'offline_payment_received', $actor, ['reference' => $reference, 'amount' => $locked->total, 'currency' => $locked->currency]);
            $this->emails->record($locked, 'paid');
        });
    }
}
