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
use Illuminate\Support\Facades\Storage;
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

        $product->load('variants', 'photos')
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        return Inertia::render('shop/Product', [
            'product' => [...$serialize($product), 'photos' => $product->photos->map(fn ($photo) => ['id' => $photo->id, 'url' => Storage::disk('public')->url($photo->path)])->values()],
            'wholesale' => $wholesale,
            'related' => $products->related($product, $wholesale),
            'savedIds' => $discovery->savedIds($request),
            'recentlyViewed' => $discovery->recentlyViewed($request, $wholesale, $product->id),
            ...$reviews->page($product, $request->user(), $reviewSort, $reviewQuery),
            'reviewSort' => $reviewSort,
            'reviewQuery' => $reviewQuery,
            'questions' => $product->questions()->whereNotNull('answered_at')->latest('answered_at')->limit(20)->get(['id', 'question', 'answer', 'answered_at']),
        ]);
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

    public function addImage(Request $request, Product $product, ProductCatalog $catalog)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $catalog->addPhoto($product, $request->file('image'));

        return back()->with('success', __('Product photo added.'));
    }

    public function removeImage(Product $product, int $photo, ProductCatalog $catalog)
    {
        $catalog->removePhoto($product, $photo);

        return back()->with('success', __('Product photo removed.'));
    }

    public function primaryImage(Product $product, int $photo, ProductCatalog $catalog)
    {
        $catalog->makePrimary($product, $photo);

        return back()->with('success', __('Primary product photo updated.'));
    }

    public function moveImage(Request $request, Product $product, int $photo, ProductCatalog $catalog)
    {
        $data = $request->validate(['direction' => 'required|in:up,down']);
        $catalog->movePhoto($product, $photo, $data['direction']);

        return back()->with('success', __('Product photos reordered.'));
    }
}
