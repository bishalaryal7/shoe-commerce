<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\User;
use App\Notifications\StoreUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReturns
{
    public function canRequest(Order $order, User $user): bool
    {
        $window = max(1, (int) config('shop.return_window_days', 30));

        return $order->user_id === $user->id
            && $order->status === 'fulfilled'
            && $order->payment_status === 'paid'
            && $order->delivered_at !== null
            && $order->delivered_at->lessThanOrEqualTo(now())
            && $order->delivered_at->greaterThanOrEqualTo(now()->subDays($window));
    }

    public function request(Order $order, User $user, array $data): OrderReturnRequest
    {
        return DB::transaction(function () use ($order, $user, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $this->canRequest($lockedOrder, $user)) {
                throw ValidationException::withMessages(['return' => __('This order is outside the return request window.')]);
            }

            $item = $lockedOrder->items()->whereKey($data['order_item_id'])->first();
            if (! $item) {
                throw ValidationException::withMessages(['order_item_id' => __('Choose an item from this order.')]);
            }

            $hasActiveRequest = OrderReturnRequest::where('order_item_id', $item->id)
                ->whereIn('status', ['requested', 'approved', 'completed'])->exists();
            if ($hasActiveRequest) {
                throw ValidationException::withMessages(['order_item_id' => __('A return request already exists for this item.')]);
            }

            $request = OrderReturnRequest::create([
                'order_id' => $lockedOrder->id,
                'order_item_id' => $item->id,
                'user_id' => $user->id,
                'action' => $data['action'],
                'reason' => $data['reason'],
                'details' => isset($data['details']) ? trim($data['details']) : null,
            ]);

            User::where('is_admin', true)->each(fn (User $staff) => $staff->notify(new StoreUpdate(
                __('A customer requested a return or exchange'),
                __('Review the customer’s request in the team workspace.'),
                '/admin?tab=Returns',
                'order_return',
            )));

            return $request;
        });
    }

    public function review(OrderReturnRequest $request, User $staff, array $data): void
    {
        DB::transaction(function () use ($request, $staff, $data) {
            $locked = OrderReturnRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $allowed = $locked->status === 'requested'
                ? ['approved', 'declined']
                : ($locked->status === 'approved' ? ['completed'] : []);

            if (! in_array($data['status'], $allowed, true)) {
                abort(422, __('This return request cannot move to that status.'));
            }

            $locked->update([
                'status' => $data['status'],
                'staff_note' => array_key_exists('staff_note', $data)
                    ? trim((string) $data['staff_note'])
                    : $locked->staff_note,
                'reviewed_by' => $staff->id,
            ]);

            $locked->user?->notify(new StoreUpdate(
                __('Your return or exchange request was updated'),
                __('Open your order page to read the latest request status.'),
                '/orders/track/'.$locked->order->reference,
                'order_return_update',
            ));
        });
    }
}
