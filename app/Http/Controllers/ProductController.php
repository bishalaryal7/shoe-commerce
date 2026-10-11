<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Cart;
use App\Services\ProductCatalog;
use App\Services\ProductReviews;
use App\Services\ShoppingDiscovery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, Cart $cart, ProductRepository $products, ShoppingDiscovery $discovery, ProductReviews $reviews)
    {
        abort_unless($product->active, 404);
        $reviewSort = in_array($request->query('reviews_sort'), ['helpful', 'rating'], true)
            ? $request->query('reviews_sort')
            : 'recent';
        $reviewFilters = $request->validate(['review_q' => 'nullable|string|max:100']);
        $reviewQuery = trim((string) ($reviewFilters['review_q'] ?? ''));
        $wholesale = $cart->wholesale($request);
        $serialize = fn ($item) => $products->display($item, $wholesale);
        $discovery->recordView($request, $product);

        return Inertia::render('shop/Product', ['product' => $serialize($product->load('variants')->loadCount('reviews')->loadAvg('reviews', 'rating')), 'wholesale' => $wholesale,
            'related' => Product::with('variants')->withCount('reviews')->withAvg('reviews', 'rating')->where('active', true)->where('category', $product->category)->whereKeyNot($product->id)->limit(3)->get()->map($serialize),
            'savedIds' => $discovery->savedIds($request), 'recentlyViewed' => $discovery->recentlyViewed($request, $wholesale, $product->id),
            ...$reviews->page($product, $request->user(), $reviewSort, $reviewQuery), 'reviewSort' => $reviewSort, 'reviewQuery' => $reviewQuery]);
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
