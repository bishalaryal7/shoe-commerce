<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentAttempt;
use App\Payments\Gateways;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderPayments
{
    public function __construct(private Gateways $gateways, private OrderEmails $emails) {}

    public function start(Order $order, string $provider): PaymentAttempt
    {
        $gateway = $this->gateways->get($provider);
        if (! $gateway->available() || $gateway->currency() !== $order->currency) {
            throw ValidationException::withMessages(['payment_method' => 'This payment method is not available for your order.']);
        }
        if ($provider === 'khalti' && $order->total < 1000) {
            throw ValidationException::withMessages(['payment_method' => 'Khalti requires a minimum payment of Rs. 10.']);
        }
        $attempt = DB::transaction(function () use ($order, $provider) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            if (! in_array($locked->status, ['pending_confirmation', 'confirmed']) || in_array($locked->payment_status, ['paid', 'refunded', 'review'])) {
                throw ValidationException::withMessages(['payment_method' => 'This order cannot start another payment.']);
            }
            $active = PaymentAttempt::where('order_id', $locked->id)->whereNotIn('status', ['failed'])->latest('id')->first();
            if ($active) {
                return $active;
            }
            $attempt = PaymentAttempt::create(['reference' => (string) Str::uuid(), 'order_id' => $locked->id, 'provider' => $provider,
                'amount' => $locked->total, 'currency' => $locked->currency, 'sandbox' => (bool) config('payments.'.$provider.'.sandbox')]);
            $locked->update(['payment_method' => $provider, 'payment_status' => 'pending']);

            return $attempt;
        });
        if (! $attempt->wasRecentlyCreated) {
            if ($attempt->status === 'initiated' && $attempt->payment_url && $attempt->expires_at?->isFuture()) {
                return $attempt;
            }
            if (in_array($attempt->status, ['initiated', 'pending']) && $attempt->provider_id) {
                $this->verify($attempt);
                if ($attempt->fresh()->status === 'failed') {
                    return $this->start($order->fresh(), $provider);
                }
            }
            throw ValidationException::withMessages(['payment_method' => 'A payment is already being processed. Check your order status before trying again.']);
        }
        try {
            $session = $gateway->initiate($attempt);
            $attempt->update([...$session, 'status' => 'initiated']);
        } catch (\Throwable $e) {
            // A timeout may hide a successful initiation. Do not create a second session automatically.
            $definitive = $e instanceof RequestException && $e->response->clientError();
            $attempt->update(['status' => $definitive ? 'failed' : 'review']);
            $order->update(['payment_status' => $definitive ? 'unpaid' : 'review']);
            Log::warning('Payment initiation needs attention.', ['attempt_id' => $attempt->id, 'provider' => $provider]);
            throw ValidationException::withMessages(['payment_method' => $definitive ? 'The payment provider could not start checkout. Please retry.' : 'Payment could not be confirmed. Our team must check it before another payment is attempted.']);
        }

        return $attempt;
    }

    public function verify(PaymentAttempt $attempt): string
    {
        if (! $attempt->provider_id) {
            throw ValidationException::withMessages(['payment' => 'The payment session is not ready for verification.']);
        }
        $result = $this->gateways->get($attempt->provider)->lookup($attempt);

        return DB::transaction(function () use ($attempt, $result) {
            $locked = PaymentAttempt::lockForUpdate()->findOrFail($attempt->id);
            $order = $locked->order_id ? Order::lockForUpdate()->findOrFail($locked->order_id) : null;
            $valid = ($result['id'] ?? null) === $locked->provider_id && (string) ($result['amount'] ?? '') === (string) $locked->amount && ($result['currency'] ?? null) === $locked->currency;
            if (! $valid || (($result['status'] ?? null) === 'paid' && empty($result['transaction_id']))) {
                if ($locked->status === 'paid') {
                    Log::warning('A verified payment returned inconsistent lookup data.', ['attempt_id' => $locked->id]);

                    return 'review';
                }
                $locked->update(['status' => 'review']);
                if ($order && $order->payment_status !== 'paid') {
                    $order->update(['payment_status' => 'review']);
                }

                return 'review';
            }
            if ($locked->status === 'refunded') {
                $locked->update(['verified_at' => now()]);

                return 'refunded';
            }
            if (($result['status'] ?? null) === 'refunded' && (! $locked->transaction_id || ($result['transaction_id'] ?? null) !== $locked->transaction_id)) {
                Log::warning('Refund lookup does not match the verified transaction.', ['attempt_id' => $locked->id]);

                return 'review';
            }
            if ($locked->status === 'paid' && ($result['status'] ?? null) !== 'refunded') {
                $locked->update(['verified_at' => now()]);

                return 'paid';
            }
            $status = $result['status'];
            if (! in_array($status, ['paid', 'failed', 'pending', 'refunded', 'review'], true)) {
                $status = 'pending';
            }
            if ($status === 'paid') {
                if ($order && (! in_array($order->status, ['pending_confirmation', 'confirmed']) || $order->payment_status === 'paid')) {
                    $locked->update(['status' => 'review']);
                    Log::warning('Payment needs reconciliation for an unavailable or paid order.', ['attempt_id' => $locked->id]);

                    return 'review';
                }
                $locked->update(['status' => 'paid', 'transaction_id' => $result['transaction_id'], 'verified_at' => now()]);
                if ($order) {
                    $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'status' => 'confirmed']);
                    OrderEvent::create(['order_id' => $order->id, 'event' => 'online_payment_verified', 'details' => ['provider' => $locked->provider, 'transaction_id' => $locked->transaction_id, 'amount' => $locked->amount, 'currency' => $locked->currency]]);
                    $order->unsetRelation('items');
                    $this->emails->record($order, 'paid');
                }
            } else {
                $locked->update(['status' => $status, 'verified_at' => now()]);
                if ($order && ($order->payment_status !== 'paid' || $status === 'refunded')) {
                    $order->update(['payment_status' => match ($status) {
                        'failed' => 'unpaid','refunded' => 'refunded','review' => 'review',default => 'pending'
                    }]);
                    if ($status === 'refunded') {
                        $this->emails->record($order, 'refunded');
                    }
                }
            }

            return $status;
        });
    }
}
