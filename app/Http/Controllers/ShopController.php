<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Services\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request, Cart $cart)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|in:Everyday,Running,Outdoor']);
        $wholesale = $cart->wholesale($request);
        $products = Product::with('variants')->where('active', true)
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->orderBy('id')->paginate(12)->withQueryString();
        $products->through(function ($product) use ($wholesale) {
            $data = $product->toArray();
            $data['price'] = $wholesale ? $product->wholesale_price : $product->retail_price;

            return $data;
        });

        return Inertia::render('shop/Index', ['products' => $products, 'filters' => $filters, 'wholesale' => $wholesale]);
    }

    public function cart(Request $request, Cart $cart)
    {
        return Inertia::render('shop/Cart', ['lines' => $cart->lines($request), 'wholesale' => $cart->wholesale($request)]);
    }

    public function add(Request $request)
    {
        $data = $request->validate(['variant_id' => 'required|integer|exists:variants,id', 'quantity' => 'required|integer|min:1|max:999']);
        $variant = Variant::with('product')->findOrFail($data['variant_id']);
        abort_unless($variant->product->active, 404);
        $cart = $request->session()->get('cart', []);
        $quantity = ($cart[$variant->id] ?? 0) + $data['quantity'];
        if ($quantity > min($variant->stock, 999)) {
            return back()->withErrors(['quantity' => 'There is not enough stock for that size.']);
        }
        $cart[$variant->id] = $quantity;
        $request->session()->forget('checkout_token');
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Added to your bag.');
    }

    public function remove(Request $request, Variant $variant)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$variant->id]);
        $request->session()->forget('checkout_token');
        $request->session()->put('cart', $cart);

        return back();
    }
}
