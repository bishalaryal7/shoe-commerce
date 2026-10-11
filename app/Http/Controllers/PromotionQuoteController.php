<?php

namespace App\Http\Controllers;

use App\Services\Cart;
use App\Services\PromotionPricing;
use Illuminate\Http\Request;

class PromotionQuoteController extends Controller
{
    public function __invoke(Request $request, Cart $cart, PromotionPricing $pricing)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $lines = $cart->lines($request);
        abort_if($lines === [], 422);
        $subtotal = array_sum(array_map(fn (array $line) => $line['unit_price'] * $line['quantity'], $lines));
        $quote = $pricing->quote($data['code'], $subtotal, $cart->wholesale($request) ? 'wholesale' : 'retail');
        $taxBasisPoints = max(0, config('shop.tax_basis_points'));
        $taxableSubtotal = $subtotal - $quote['discount'];
        $tax = intdiv($taxableSubtotal * $taxBasisPoints + 5000, 10000);

        return response()->json([
            'code' => $quote['promotion']->code,
            'discount' => $quote['discount'],
            'tax' => $tax,
            'total' => $taxableSubtotal + max(0, config('shop.shipping_amount')) + $tax,
        ]);
    }
}
