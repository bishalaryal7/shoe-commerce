<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\SavedProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShoppingDiscovery
{
    private const SAVED_LIMIT = 50;

    private const RECENT_LIMIT = 8;

    public function __construct(private SavedProductRepository $saved, private ProductRepository $products) {}

    public function savedIds(Request $request): array
    {
        if (! $request->user()) {
            $ids = array_values(array_unique(array_map('intval', (array) $request->session()->get('saved_products', []))));
            if (! $ids) {
                return [];
            }
            $active = Product::where('active', true)->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids = array_values(array_intersect($ids, $active));
            $request->session()->put('saved_products', $ids);

            return $ids;
        }
        $this->mergeGuestList($request);

        return $this->saved->ids($request->user()->id);
    }

    public function toggle(Request $request, Product $product): bool
    {
        abort_unless($product->active, 404);
        $ids = $this->savedIds($request);
        $saved = in_array($product->id, $ids, true);
        if ($request->user()) {
            if ($saved) {
                $this->saved->remove($request->user()->id, $product->id);
            } else {
                $this->checkLimit(count($ids));
                $this->saved->add($request->user()->id, $product->id);
            }
        } else {
            if (! $saved) {
                $this->checkLimit(count($ids));
                array_unshift($ids, $product->id);
            } else {
                $ids = array_values(array_diff($ids, [$product->id]));
            }
            $request->session()->put('saved_products', $ids);
        }

        return ! $saved;
    }

    public function savedProducts(Request $request, bool $wholesale): array
    {
        return $this->displayOrdered($this->savedIds($request), $wholesale);
    }

    public function recordView(Request $request, Product $product): void
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', (array) $request->session()->get('recent_products', []))), fn ($id) => $id > 0 && $id !== $product->id));
        array_unshift($ids, $product->id);
        $request->session()->put('recent_products', array_slice($ids, 0, self::RECENT_LIMIT));
    }

    public function recentlyViewed(Request $request, bool $wholesale, ?int $exclude = null): array
    {
        $ids = array_values(array_filter(array_slice((array) $request->session()->get('recent_products', []), 0, self::RECENT_LIMIT), fn ($id) => (int) $id !== $exclude));

        return $this->displayOrdered($ids, $wholesale);
    }

    public function clearHistory(Request $request): void
    {
        $request->session()->forget('recent_products');
    }

    private function displayOrdered(array $ids, bool $wholesale): array
    {
        if (! $ids) {
            return [];
        }
        $products = Product::with('variants')->withCount('reviews')->withAvg('reviews', 'rating')->where('active', true)->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $products->get((int) $id))->filter()->map(fn ($product) => $this->products->display($product, $wholesale))->values()->all();
    }

    private function mergeGuestList(Request $request): void
    {
        $guest = array_slice(array_unique(array_map('intval', (array) $request->session()->get('saved_products', []))), 0, self::SAVED_LIMIT);
        if (! $guest) {
            return;
        }
        DB::transaction(function () use ($request, $guest) {
            $valid = Product::where('active', true)->whereIn('id', $guest)->pluck('id')->all();
            $existing = $this->saved->ids($request->user()->id);
            foreach (array_values(array_intersect($guest, $valid)) as $id) {
                if (count($existing) >= self::SAVED_LIMIT) {
                    break;
                }
                if (! in_array((int) $id, $existing, true)) {
                    $this->saved->add($request->user()->id, (int) $id);
                    $existing[] = (int) $id;
                }
            }
        });
        $request->session()->forget('saved_products');
    }

    private function checkLimit(int $count): void
    {
        if ($count >= self::SAVED_LIMIT) {
            throw ValidationException::withMessages(['saved' => __('Your saved list is full. Remove a shoe before adding another.')]);
        }
    }
}
