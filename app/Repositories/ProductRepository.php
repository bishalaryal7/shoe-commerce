<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    private const DEMO_PRODUCT_NAMES = [
        'City Low',
        'Pace Runner',
        'Trail Form',
        'Court Classic',
        'Daily Knit',
        'Motion Pro',
    ];

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): void
    {
        $product->update($data);
    }

    public function display(Product $product, bool $wholesale): array
    {
        $data = $product->toArray();
        unset($data['reviews_count'], $data['reviews_avg_rating']);

        if (app()->getLocale() === 'ne') {
            $data['name'] = $product->name_ne ?: __($product->name);
            $data['description'] = $product->description_ne ?: __($product->description);
        }

        return [
            ...$data,
            'price' => $wholesale ? $product->wholesale_price : $product->retail_price,
            'review_count' => (int) ($product->reviews_count ?? 0),
            'review_average' => $product->reviews_avg_rating === null
                ? null
                : round((float) $product->reviews_avg_rating, 1),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function related(Product $product, bool $wholesale): array
    {
        return Product::query()
            ->with('variants')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('active', true)
            ->where('category', $product->category)
            ->whereKeyNot($product->id)
            ->limit(3)
            ->get()
            ->map(fn (Product $related) => $this->display($related, $wholesale))
            ->all();
    }

    public function browse(array $filters, bool $wholesale): LengthAwarePaginator
    {
        $query = Product::query()
            ->with('variants')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('active', true)
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $this->search($query, $term))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['size'] ?? null, fn (Builder $query, string $size) => $query->whereHas(
                'variants',
                fn (Builder $variants) => $variants->where('size', $size)->where('stock', '>', 0),
            ))
            ->when($filters['rating'] ?? null, fn (Builder $query, string $rating) => $query->whereRaw(
                '(SELECT AVG(product_reviews.rating) FROM product_reviews WHERE product_reviews.product_id = products.id) >= ?',
                [(int) $rating],
            ));

        $this->sort($query, $filters['sort'] ?? 'featured', $wholesale);

        return $query->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $this->display($product, $wholesale));
    }

    private function search(Builder $query, string $term): void
    {
        $like = '%'.$term.'%';

        $translatedNames = $this->matchingTranslatedDemoNames($term);

        $query->where(function (Builder $products) use ($like, $translatedNames) {
            $products->where('name', 'like', $like)
                ->orWhere('name_ne', 'like', $like);

            if ($translatedNames) {
                $products->orWhereIn('name', $translatedNames);
            }
        });
    }

    private function matchingTranslatedDemoNames(string $term): array
    {
        if (app()->getLocale() !== 'ne') {
            return [];
        }

        $search = mb_strtolower($term);

        return array_values(array_filter(
            self::DEMO_PRODUCT_NAMES,
            fn (string $name) => str_contains(mb_strtolower(__($name)), $search),
        ));
    }

    private function sort(Builder $query, string $sort, bool $wholesale): void
    {
        switch ($sort) {
            case 'price_low':
                $query->orderBy($wholesale ? 'wholesale_price' : 'retail_price');
                break;
            case 'price_high':
                $query->orderByDesc($wholesale ? 'wholesale_price' : 'retail_price');
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'rating':
                $query->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('reviews_count');
                break;
        }

        $query->orderBy('id');
    }
}
