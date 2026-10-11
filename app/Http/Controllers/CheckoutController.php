<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\Order;
use App\Payments\Gateways;
use App\Services\Cart;
use App\Services\OrderPayments;
use App\Services\PlaceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart, Gateways $gateways)
    {
        $lines = $cart->lines($request);
        if (! $lines) {
            return redirect()->route('cart');
        }
        if (! $request->session()->has('checkout_token')) {
            $request->session()->put('checkout_token', (string) Str::uuid());
        }

        return Inertia::render('shop/Checkout', [
            'lines' => $lines,
            'wholesale' => $cart->wholesale($request),
            'token' => $request->session()->get('checkout_token'),
            'cashOnDelivery' => config('shop.cash_on_delivery') && config('shop.currency') === 'NPR',
            'paymentOptions' => $gateways->options(config('shop.currency')),
            'shippingAmount' => max(0, config('shop.shipping_amount')),
            'taxBasisPoints' => max(0, config('shop.tax_basis_points')),
        ]);
    }

    public function store(CheckoutRequest $request, Cart $cart, Gateways $gateways, OrderPayments $payments, PlaceOrder $placement)
    {
        $data = $request->validated();
        $provider = $data['payment_method'] ?? 'request';
        if ($provider === 'cod' && (! config('shop.cash_on_delivery') || config('shop.currency') !== 'NPR')) {
            throw ValidationException::withMessages(['payment_method' => __('Cash on delivery is not available for this order.')]);
        }
        if (! in_array($provider, ['request', 'cod']) && ! in_array($provider, array_column($gateways->options(config('shop.currency')), 'id'), true)) {
            throw ValidationException::withMessages(['payment_method' => __('This payment method is not available.')]);
        }
        abort_unless($data['token'] === $request->session()->get('checkout_token'), 419);
        $order = $placement->place($data, $cart->lines($request), $cart->wholesale($request), $request->user()?->id, count($request->session()->get('cart', [])));
        $request->session()->forget('cart');
        $request->session()->put('last_order_reference', $order->reference);

        if (! in_array($provider, ['request', 'cod'])) {
            try {
                $attempt = $payments->start($order, $provider);

                return Inertia::location($attempt->payment_url);
            } catch (ValidationException $e) {
                return redirect()->route('order.received', $order->reference)->withErrors($e->errors());
            }
        }

        return redirect()->route('order.received', $order->reference);
    }

    public function received(Request $request, string $reference, Gateways $gateways, OrderController $orders)
    {
        abort_unless($request->session()->get('last_order_reference') === $reference, 403);
        $order = Order::where('reference', $reference)->firstOrFail();
        abort_unless($order->user_id === null || $request->user()?->id === $order->user_id, 403);

        return $orders->page($order, $gateways);
    }
}
