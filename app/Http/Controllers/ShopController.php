<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\AddCartRequest;
use App\Http\Requests\Shop\CartQuantityRequest;
use App\Models\Product;
use App\Models\Variant;
use App\Repositories\ProductRepository;
use App\Services\Cart;
use App\Services\ShoppingDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request, Cart $cart, ProductRepository $products, ShoppingDiscovery $discovery)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|in:Everyday,Running,Outdoor',
            'size' => 'nullable|in:36,37,38,39,40,41,42,43,44,45',
            'rating' => 'nullable|in:3,4',
            'sort' => 'nullable|in:featured,price_low,price_high,name,rating',
        ]);
        $wholesale = $cart->wholesale($request);
        $catalog = $products->browse($filters, $wholesale);

        return Inertia::render('shop/Index', ['products' => $catalog, 'filters' => $filters, 'wholesale' => $wholesale,
            'savedIds' => $discovery->savedIds($request), 'recentlyViewed' => $discovery->recentlyViewed($request, $wholesale),
            'popular' => Product::query()->select('products.*')->selectSub(DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->join('variants', 'variants.id', '=', 'order_items.variant_id')->whereColumn('variants.product_id', 'products.id')->where('orders.payment_status', 'paid')->whereIn('orders.status', ['confirmed', 'shipped', 'fulfilled'])->selectRaw('COALESCE(SUM(order_items.quantity), 0)'), 'units_sold')->with('variants')->where('active', true)->orderByDesc('units_sold')->orderBy('products.id')->limit(4)->get()->filter(fn ($product) => $product->units_sold > 0)->map(fn ($product) => $products->display($product, $wholesale))->values()->all()]);
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
