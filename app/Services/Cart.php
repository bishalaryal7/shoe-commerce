<?php

namespace App\Services;

use App\Models\Variant;
use App\Models\WholesaleAccount;
use Illuminate\Http\Request;

class Cart
{
    public function wholesale(Request $request): bool
    {
        return $request->user() && WholesaleAccount::where('user_id', $request->user()->id)->where('status', 'approved')->exists();
    }

    public function lines(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $wholesale = $this->wholesale($request);

        return Variant::with('product')->whereIn('id', array_keys($cart))->orderBy('id')->get()
            ->filter(fn ($v) => $v->product->active)->map(fn ($v) => [
                'id' => $v->id, 'name' => $v->product->name, 'image' => $v->product->image,
                'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color, 'stock' => $v->stock,
                'quantity' => (int) $cart[$v->id],
                'unit_price' => $wholesale ? $v->product->wholesale_price : $v->product->retail_price,
            ])->values()->all();
    }
}
