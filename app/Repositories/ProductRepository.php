<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
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

        return [...$data, 'price' => $wholesale ? $product->wholesale_price : $product->retail_price,
            'review_count' => (int) ($product->reviews_count ?? 0),
            'review_average' => $product->reviews_avg_rating === null ? null : round((float) $product->reviews_avg_rating, 1)];
    }

    public function browse(array $filters, bool $wholesale)
    {
        $query = Product::with('variants')->withCount('reviews')->withAvg('reviews', 'rating')->where('active', true)->when($filters['q'] ?? null, function ($query, $term) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('name_ne', 'like', '%'.$term.'%')
                ->orWhereIn('name', array_values(array_filter(
                    ['City Low', 'Pace Runner', 'Trail Form', 'Court Classic', 'Daily Knit', 'Motion Pro'],
                    fn ($name) => str_contains(mb_strtolower(__($name)), mb_strtolower($term))
                ))));
        })->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))->when($filters['size'] ?? null, fn ($q, $size) => $q->whereHas('variants', fn ($v) => $v->where('size', $size)->where('stock', '>', 0)))
            ->when($filters['rating'] ?? null, fn ($q, $rating) => $q->whereRaw('(SELECT AVG(product_reviews.rating) FROM product_reviews WHERE product_reviews.product_id = products.id) >= ?', [(int) $rating]));
        $price = $wholesale ? 'wholesale_price' : 'retail_price';
        match ($filters['sort'] ?? 'featured') {
            'price_low' => $query->orderBy($price),'price_high' => $query->orderByDesc($price),'name' => $query->orderBy('name'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),default => $query->orderBy('id')
        };

        return $query->orderBy('id')->paginate(12)->withQueryString()->through(fn ($product) => $this->display($product, $wholesale));
    }
}
