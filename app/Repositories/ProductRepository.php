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
        if (app()->getLocale() === 'ne') {
            $data['name'] = $product->name_ne ?: $product->name;
            $data['description'] = $product->description_ne ?: $product->description;
        }

        return [...$data, 'price' => $wholesale ? $product->wholesale_price : $product->retail_price];
    }

    public function browse(array $filters, bool $wholesale)
    {
        $query = Product::with('variants')->where('active', true)->when($filters['q'] ?? null, function ($query, $term) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('name_ne', 'like', '%'.$term.'%'));
        })->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))->when($filters['size'] ?? null, fn ($q, $size) => $q->whereHas('variants', fn ($v) => $v->where('size', $size)->where('stock', '>', 0)));
        $price = $wholesale ? 'wholesale_price' : 'retail_price';
        match ($filters['sort'] ?? 'featured') {
            'price_low' => $query->orderBy($price),'price_high' => $query->orderByDesc($price),'name' => $query->orderBy('name'),default => $query->orderBy('id')
        };

        return $query->orderBy('id')->paginate(12)->withQueryString()->through(fn ($product) => $this->display($product,$wholesale));
    }
}
