<?php

namespace App\Services;

use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidOrders
{
    public function __construct(private OrderPayments $payments, private CancelOrder $cancellation) {}

    /** @return array{checked: int, expired: int, still_pending: int, unavailable: int} */
    public function run(int $limit = 100): array
    {
        $latestAttempts = PaymentAttempt::query()
            ->selectRaw('MAX(id)')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $attempts = PaymentAttempt::query()
            ->with('order')
            ->whereIn('id', $latestAttempts)
            ->where(fn ($query) => $query
                ->whereIn('status', ['initiated', 'pending'])
                ->orWhere(fn ($failed) => $failed->where('status', 'failed')->whereNotNull('verified_at')))
            ->whereNotNull('provider_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereHas('order', fn ($orders) => $orders
                ->whereIn('status', ['pending_confirmation', 'confirmed'])
                ->whereIn('payment_status', ['pending', 'unpaid']))
            ->oldest('expires_at')
            ->limit(min(max($limit, 1), 1000))
            ->get();

        $result = ['checked' => 0, 'expired' => 0, 'still_pending' => 0, 'unavailable' => 0];

        foreach ($attempts as $attempt) {
            $result['checked']++;

            try {
                $status = $attempt->status === 'failed'
                    ? 'failed'
                    : $this->payments->verify($attempt);
                if ($status !== 'failed') {
                    $result['still_pending']++;

                    continue;
                }

                $order = $attempt->order?->fresh();
                if (! $order || ! in_array($order->status, ['pending_confirmation', 'confirmed'], true) || $order->payment_status !== 'unpaid') {
                    continue;
                }

                $this->cancellation->cancel($order, null, true, 'payment_expired');
                $result['expired']++;
            } catch (\Throwable $exception) {
                $result['unavailable']++;
                Log::warning('Expired checkout could not be reconciled; its stock remains reserved.', [
                    'payment_attempt_id' => $attempt->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $result;
    }
}
