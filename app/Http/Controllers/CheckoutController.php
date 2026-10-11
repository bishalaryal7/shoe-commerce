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
        if (! $cart->lines($request)) {
            return redirect()->route('cart');
        }
        if (! $request->session()->has('checkout_token')) {
            $request->session()->put('checkout_token', (string) Str::uuid());
        }

        return Inertia::render('shop/Checkout', ['lines' => $cart->lines($request), 'wholesale' => $cart->wholesale($request), 'token' => $request->session()->get('checkout_token'), 'paymentOptions' => $gateways->options(config('shop.currency')), 'shippingAmount' => max(0, config('shop.shipping_amount')), 'taxBasisPoints' => max(0, config('shop.tax_basis_points'))]);
    }

    public function store(CheckoutRequest $request, Cart $cart, Gateways $gateways, OrderPayments $payments, PlaceOrder $placement)
    {
        $data = $request->validated();
        $provider = $data['payment_method'] ?? 'request';
        if ($provider !== 'request' && ! in_array($provider, array_column($gateways->options(config('shop.currency')), 'id'), true)) {
            throw ValidationException::withMessages(['payment_method' => 'This payment method is not available.']);
        }
        abort_unless($data['token'] === $request->session()->get('checkout_token'), 419);
        $order = $placement->place($data, $cart->lines($request), $cart->wholesale($request), $request->user()?->id, count($request->session()->get('cart', [])));
        $request->session()->forget('cart');
        $request->session()->put('last_order_reference', $order->reference);

        if ($provider !== 'request') {
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
        abort_unless($request->session()->get('last_order_reference') === $reference && ($order->user_id === null || $request->user()?->id === $order->user_id), 403);

        return $orders->page($order, $gateways);
    }
}
