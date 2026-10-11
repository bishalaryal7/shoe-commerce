<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StockAlerts;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function store(Request $request, Product $product, StockAlerts $alerts)
    {
        $data = $request->validate(['variant_id' => ['required', 'integer', 'exists:variants,id'], 'email' => ['required', 'email:rfc', 'max:254']]);
        $variant = $product->variants()->whereKey($data['variant_id'])->firstOrFail();
        abort_unless($product->active, 404);
        if ($variant->stock > 0) {
            return back()->withErrors(['variant_id' => __('This size is already available. Refresh the page to add it to your bag.')]);
        }
        $alerts->subscribe($variant, $data['email'], $request->user()?->id);

        return back()->with('success', __('We’ll email you when this size is back in stock.'));
    }
}
