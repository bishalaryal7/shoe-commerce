<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Cart;
use App\Services\ProductCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, Cart $cart, ProductRepository $products)
    {
        abort_unless($product->active, 404);
        $wholesale = $cart->wholesale($request);
        $serialize = fn ($item) => $products->display($item, $wholesale);

        return Inertia::render('shop/Product', ['product' => $serialize($product->load('variants')), 'wholesale' => $wholesale,
            'related' => Product::with('variants')->where('active', true)->where('category', $product->category)->whereKeyNot($product->id)->limit(3)->get()->map($serialize)]);
    }

    public function store(ProductRequest $request, ProductCatalog $catalog)
    {
        $data = $request->validated();
        $catalog->create($data);

        return back()->with('success', __('Product created. Add a real photo before publishing your collection.'));
    }

    public function image(Request $request, Product $product, ProductCatalog $catalog)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $catalog->replacePhoto($product, $request->file('image'));

        return back()->with('success', __('Product photo updated.'));
    }
}
