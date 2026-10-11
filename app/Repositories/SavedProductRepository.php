<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class SavedProductRepository
{
    public function ids(int $userId): array
    {
        return DB::table('saved_products')->join('products', 'products.id', '=', 'saved_products.product_id')
            ->where('saved_products.user_id', $userId)->where('products.active', true)
            ->orderByDesc('saved_products.id')->pluck('saved_products.product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function add(int $userId, int $productId): void
    {
        DB::table('saved_products')->insertOrIgnore(['user_id' => $userId, 'product_id' => $productId, 'created_at' => now()]);
    }

    public function remove(int $userId, int $productId): void
    {
        DB::table('saved_products')->where('user_id', $userId)->where('product_id', $productId)->delete();
    }
}
