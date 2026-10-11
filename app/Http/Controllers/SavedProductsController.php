<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use App\Services\ShoppingDiscovery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SavedProductsController extends Controller
{
    public function index(Request $request, Cart $cart, ShoppingDiscovery $discovery)
    {
        $wholesale = $cart->wholesale($request);

        return Inertia::render('shop/Saved', [
            'products' => $discovery->savedProducts($request, $wholesale),
            'wholesale' => $wholesale,
        ]);
    }

    public function toggle(Request $request, Product $product, ShoppingDiscovery $discovery)
    {
        $saved = $discovery->toggle($request, $product);

        return back()->with('success', $saved ? __('Shoe saved for later.') : __('Shoe removed from saved list.'));
    }

    public function clearHistory(Request $request, ShoppingDiscovery $discovery)
    {
        $discovery->clearHistory($request);

        return back()->with('success', __('Recently viewed shoes cleared.'));
    }
}
