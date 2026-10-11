<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductComparison
{
    private const LIMIT = 3;

    public function __construct(private ProductRepository $products) {}

    public function ids(Request $request): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', (array) $request->session()->get('compare_products', [])), fn ($id) => $id > 0))), 0, self::LIMIT);
        if (! $ids) {
            return [];
        }
        $active = Product::where('active', true)->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $ids = array_values(array_intersect($ids, $active));
        $request->session()->put('compare_products', $ids);

        return $ids;
    }

    public function toggle(Request $request, Product $product): bool
    {
        abort_unless($product->active, 404);
        $ids = $this->ids($request);
        if (in_array($product->id, $ids, true)) {
            $request->session()->put('compare_products', array_values(array_diff($ids, [$product->id])));

            return false;
        }
        if (count($ids) >= self::LIMIT) {
            throw ValidationException::withMessages(['compare' => __('Compare up to :count shoes at a time. Remove one to add another.', ['count' => self::LIMIT])]);
        }
        array_unshift($ids, $product->id);
        $request->session()->put('compare_products', $ids);

        return true;
    }

    public function products(Request $request, bool $wholesale): array
    {
        $ids = $this->ids($request);
        if (! $ids) {
            return [];
        }
        $found = Product::with('variants')->withCount('reviews')->withAvg('reviews', 'rating')
            ->where('active', true)->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $found->get($id))->filter()
            ->map(fn (Product $product) => $this->products->display($product, $wholesale))->values()->all();
    }

    public function clear(Request $request): void
    {
        $request->session()->forget('compare_products');
    }
}
