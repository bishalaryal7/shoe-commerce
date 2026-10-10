<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\Gateways;
use App\Services\CancelOrder;
use App\Services\OrderAccess;
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
        $order = Order::with('items')->where('reference', $reference)->firstOrFail();
        abort_unless($request->hasValidSignature() || ($request->user() && $order->user_id === $request->user()->id), 403);

        return $this->page($order, $gateways, null);
    }

    public function cancel(Request $request, string $reference, CancelOrder $cancellation)
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        app(OrderAccess::class)->authorize($request, $order);
        $cancellation->cancel($order, $request->user()?->id);

        return back()->with('success', 'Order cancelled. Your reserved stock has been released.');
    }

    public function page(Order $order, Gateways $gateways, ?string $accessUrl = null)
    {
        $order->loadMissing('items');

        return Inertia::render('shop/Order', ['order' => $order->only(['reference', 'name', 'email', 'address', 'phone', 'currency', 'subtotal', 'shipping_amount', 'tax_amount', 'total', 'status', 'payment_status', 'payment_method', 'paid_at', 'created_at', 'items']),
            'paymentOptions' => in_array($order->status, ['pending_confirmation', 'confirmed']) && $order->payment_status === 'unpaid' ? $gateways->options($order->currency) : [],
            'accessUrl' => $accessUrl ?? URL::temporarySignedRoute('orders.track', now()->addDays(30), ['reference' => $order->reference]),
        ]);
    }
}
