<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Payments\Gateways;
use App\Services\CancelOrder;
use App\Services\OrderAccess;
use App\Services\RepeatOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items')->where('user_id', $request->user()->id)->latest()->paginate(10);

        return Inertia::render('shop/Orders', ['orders' => $orders]);
    }

    public function show(Request $request, string $reference, Gateways $gateways)
    {
        $order = Order::with(['items', 'events'])->where('reference', $reference)->firstOrFail();
        abort_unless($request->hasValidSignature() || ($request->user() && $order->user_id === $request->user()->id), 403);

        return $this->page($order, $gateways, null);
    }

    public function cancel(Request $request, string $reference, CancelOrder $cancellation, OrderAccess $access)
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        $access->authorize($request, $order);
        $cancellation->cancel($order, $request->user()?->id);

        return back()->with('success', __('Order cancelled. Your reserved stock has been released.'));
    }

    public function reorder(Request $request, string $reference, RepeatOrder $repeatOrder)
    {
        $order = Order::where('reference', $reference)->where('user_id', $request->user()->id)->firstOrFail();
        $result = $repeatOrder->addToBag($request, $request->user(), $order);
        $message = $result['added'] === 1
            ? __('One pair was added to your bag.')
            : __(':count pairs were added to your bag.', ['count' => $result['added']]);
        if ($result['skipped'] > 0) {
            $message .= ' '.__(':count pairs from your previous order were already in your bag or unavailable.', ['count' => $result['skipped']]);
        }

        return redirect('/cart')->with('success', $message);
    }

    public function page(Order $order, Gateways $gateways, ?string $accessUrl = null)
    {
        $order->loadMissing('items');
        $resume = null;
        if (in_array($order->status, ['pending_confirmation', 'confirmed']) && $order->payment_status === 'pending') {
            $attempt = PaymentAttempt::where('order_id', $order->id)->whereIn('status', ['initiated', 'pending'])->latest('id')->first();
            if ($attempt && $attempt->payment_url && $attempt->expires_at?->isFuture()) {
                $resume = ['url' => $attempt->payment_url, 'provider' => $attempt->provider, 'sandbox' => $attempt->sandbox];
            }
        }

        $order->loadMissing('events');

        return Inertia::render('shop/Order', ['order' => $order->only(['reference', 'name', 'email', 'address', 'phone', 'currency', 'subtotal', 'shipping_amount', 'tax_amount', 'total', 'status', 'payment_status', 'payment_method', 'paid_at', 'created_at', 'items', 'events', 'tracking_carrier', 'tracking_number', 'shipped_at', 'delivered_at', 'promotion_code', 'discount_amount']),
            'resumePayment' => $resume,
            'paymentOptions' => in_array($order->status, ['pending_confirmation', 'confirmed']) && $order->payment_status === 'unpaid' ? $gateways->options($order->currency) : [],
            'accessUrl' => $accessUrl ?? URL::temporarySignedRoute('orders.track', now()->addDays(30), ['reference' => $order->reference]),
        ]);
    }
}
