<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\AddCartRequest;
use App\Http\Requests\Shop\CartQuantityRequest;
use App\Models\Variant;
use App\Repositories\ProductRepository;
use App\Services\Cart;
use App\Services\ShoppingDiscovery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request, Cart $cart, ProductRepository $products, ShoppingDiscovery $discovery)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|in:Everyday,Running,Outdoor',
            'size' => 'nullable|in:36,37,38,39,40,41,42,43,44,45',
            'sort' => 'nullable|in:featured,price_low,price_high,name',
        ]);
        $wholesale = $cart->wholesale($request);
        $catalog = $products->browse($filters, $wholesale);

        return Inertia::render('shop/Index', ['products' => $catalog, 'filters' => $filters, 'wholesale' => $wholesale,
            'savedIds' => $discovery->savedIds($request), 'recentlyViewed' => $discovery->recentlyViewed($request, $wholesale)]);
    }

    public function cart(Request $request, Cart $cart)
    {
        return Inertia::render('shop/Cart', ['lines' => $cart->lines($request), 'wholesale' => $cart->wholesale($request)]);
    }

    public function add(AddCartRequest $request, Cart $cart)
    {
        $cart->add($request, $request->validated('variant_id'), $request->validated('quantity'));

        return back()->with('success', __('Added to your bag.'));
    }

    public function update(CartQuantityRequest $request, Variant $variant, Cart $cart)
    {
        $cart->update($request, $variant, $request->validated('quantity'));

        return back();
    }

    public function remove(Request $request, Variant $variant, Cart $cart)
    {
        $cart->remove($request, $variant);

        return back();
    }
}
