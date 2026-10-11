<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use App\Services\ProductComparison;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    public function index(Request $request, Cart $cart, ProductComparison $comparison)
    {
        return Inertia::render('shop/Compare', [
            'products' => $comparison->products($request, $cart->wholesale($request)),
            'wholesale' => $cart->wholesale($request),
        ]);
    }

    public function toggle(Request $request, Product $product, ProductComparison $comparison)
    {
        $added = $comparison->toggle($request, $product);

        return back()->with('success', $added ? __('Shoe added to comparison.') : __('Shoe removed from comparison.'));
    }

    public function clear(Request $request, ProductComparison $comparison)
    {
        $comparison->clear($request);

        return back()->with('success', __('Comparison list cleared.'));
    }
}
