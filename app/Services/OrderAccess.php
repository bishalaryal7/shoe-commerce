<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class OrderAccess
{
    public function authorize(Request $request, Order $order): void
    {
        $access = $request->input('access_url');
        $signed = false;
        if (is_string($access) && strlen($access) <= 4096) {
            $parts = parse_url($access);
            $expected = parse_url(route('orders.track', $order->reference));
            if (($parts['host'] ?? null) === ($expected['host'] ?? null) && ($parts['scheme'] ?? null) === ($expected['scheme'] ?? null) && ($parts['port'] ?? null) === ($expected['port'] ?? null) && ($parts['path'] ?? null) === ($expected['path'] ?? null)) {
                $signed = URL::hasValidSignature(Request::create($access));
            }
        }
        abort_unless($signed || ($request->user() && $order->user_id === $request->user()->id) || ($order->user_id === null && $request->session()->get('last_order_reference') === $order->reference), 403);
    }
}
