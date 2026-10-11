<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\OrderAccess;
use App\Services\OrderPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function start(Request $request, string $reference, OrderPayments $payments)
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorizeOrder($request, $order);
        $data = $request->validate(['provider' => 'required|in:khalti,paypay']);
        $attempt = $payments->start($order, $data['provider']);

        return Inertia::location($attempt->payment_url);
    }

    public function verify(Request $request, string $reference, OrderPayments $payments)
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorizeOrder($request, $order);
        $attempt = PaymentAttempt::where('order_id', $order->id)->whereNotNull('provider_id')->latest('id')->firstOrFail();
        try {
            $status = $payments->verify($attempt);
        } catch (\Throwable $e) {
            Log::warning('Payment lookup unavailable.', ['attempt_id' => $attempt->id]);

            return back()->withErrors(['payment' => __('The provider is unavailable. Your payment status has not been changed. Please check again shortly.')]);
        }

        return back()->with('success', $status === 'paid' ? __('Payment verified. Thank you!') : __('Payment status checked: :status.', ['status' => __($status)]));
    }

    public function callback(Request $request, PaymentAttempt $attempt, OrderPayments $payments)
    {
        if ($attempt->provider === 'khalti') {
            abort_unless(is_string($request->query('pidx')) && hash_equals((string) $attempt->provider_id, $request->query('pidx')), 422);
        }
        try {
            $status = $payments->verify($attempt);
        } catch (\Throwable $e) {
            Log::warning('Payment callback verification unavailable.', ['attempt_id' => $attempt->id]);
            $status = 'pending';
        }
        if ($attempt->order_id) {
            $order = $attempt->order;
            if ($request->user() && $order->user_id === $request->user()->id) {
                return redirect()->route('orders.track', $order->reference);
            }
            if ($order->user_id === null && $request->session()->get('last_order_reference') === $order->reference) {
                return redirect()->route('order.received', $order->reference);
            }
        }

        return Inertia::render('shop/PaymentResult', ['status' => $status, 'sandbox' => $attempt->sandbox]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        app(OrderAccess::class)->authorize($request, $order);
    }
}
