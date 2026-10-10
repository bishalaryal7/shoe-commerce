<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Variant;
use App\Services\Cart;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart)
    {
        if (! $cart->lines($request)) {
            return redirect()->route('cart');
        }
        if (! $request->session()->has('checkout_token')) {
            $request->session()->put('checkout_token', (string) Str::uuid());
        }

        return Inertia::render('shop/Checkout', ['lines' => $cart->lines($request), 'wholesale' => $cart->wholesale($request), 'token' => $request->session()->get('checkout_token')]);
    }

    public function store(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'email' => 'required|email|max:254', 'phone' => 'required|string|max:40',
            'address' => 'required|string|max:1000', 'token' => 'required|uuid',
        ]);
        abort_unless($data['token'] === $request->session()->get('checkout_token'), 419);
        try {
            $order = DB::transaction(function () use ($request, $cart, $data) {
                if ($existing = Order::where('checkout_token', $data['token'])->first()) {
                    return $existing;
                }
                $lines = $cart->lines($request);
                if (! $lines || count($lines) !== count($request->session()->get('cart', []))) {
                    throw ValidationException::withMessages(['cart' => 'Your bag has changed. Please review it before ordering.']);
                }
                $wholesale = $cart->wholesale($request);
                if ($wholesale && array_sum(array_column($lines, 'quantity')) < config('shop.wholesale_minimum')) {
                    throw ValidationException::withMessages(['cart' => 'Wholesale orders require at least 12 pairs.']);
                }
                $order = Order::create([
                    'reference' => (string) Str::uuid(), 'checkout_token' => $data['token'], 'user_id' => $request->user()?->id,
                    'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'address' => $data['address'],
                    'channel' => $wholesale ? 'wholesale' : 'retail', 'currency' => config('shop.currency'),
                    'subtotal' => array_sum(array_map(fn ($line) => $line['unit_price'] * $line['quantity'], $lines)),
                ]);
                foreach ($lines as $line) {
                    $reserved = Variant::whereKey($line['id'])->where('stock', '>=', $line['quantity'])->decrement('stock', $line['quantity']);
                    if (! $reserved) {
                        throw ValidationException::withMessages(['cart' => 'A selected size is no longer available in that quantity. Please review your bag.']);
                    }
                    $order->items()->create([
                        'variant_id' => $line['id'], 'name' => $line['name'], 'sku' => $line['sku'], 'size' => $line['size'],
                        'color' => $line['color'], 'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'],
                    ]);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $order = Order::where('checkout_token', $data['token'])->first();
            if (! $order) {
                throw $exception;
            }
        }
        $request->session()->forget('cart');
        $request->session()->put('last_order_reference', $order->reference);

        return redirect()->route('order.received', $order->reference);
    }

    public function received(Request $request, string $reference)
    {
        abort_unless($request->session()->get('last_order_reference') === $reference, 403);
        $order = Order::where('reference', $reference)->firstOrFail();

        return Inertia::render('shop/Received', ['reference' => $order->reference, 'subtotal' => $order->subtotal]);
    }
}
