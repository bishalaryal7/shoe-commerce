<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductReviewRepository
{
    public function eligibleOrderId(User $user, Product $product): ?int
    {
        $id = DB::table('orders')->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('variants', 'variants.id', '=', 'order_items.variant_id')
            ->where('orders.user_id', $user->id)->where('orders.status', 'fulfilled')
            ->where('orders.payment_status', 'paid')->where('variants.product_id', $product->id)
            ->orderByDesc('orders.id')->value('orders.id');

        return $id === null ? null : (int) $id;
    }

    public function own(User $user, Product $product): ?array
    {
        $review = DB::table('product_reviews')->where('user_id', $user->id)->where('product_id', $product->id)
            ->first(['rating', 'fit', 'title', 'body']);

        return $review ? (array) $review : null;
    }

    public function summary(Product $product): array
    {
        $summary = DB::table('product_reviews')->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();
        $fit = DB::table('product_reviews')->where('product_id', $product->id)
            ->whereNotNull('fit')->selectRaw('fit, COUNT(*) as total')->groupBy('fit')->pluck('total', 'fit');

        return [
            'count' => (int) $summary->total,
            'average' => $summary->average === null ? null : round((float) $summary->average, 1),
            'fit' => ['small' => (int) ($fit['small'] ?? 0), 'true' => (int) ($fit['true'] ?? 0), 'large' => (int) ($fit['large'] ?? 0)],
        ];
    }

    public function listing(Product $product)
    {
        return DB::table('product_reviews')->where('product_id', $product->id)->orderByDesc('id')
            ->paginate(5, ['id', 'rating', 'fit', 'title', 'body', 'created_at'], 'reviews_page')->withQueryString();
    }

    public function save(User $user, Product $product, int $orderId, array $data): void
    {
        DB::table('product_reviews')->upsert([[
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $orderId,
            'rating' => $data['rating'], 'fit' => $data['fit'] ?? null,
            'title' => $data['title'], 'body' => $data['body'],
            'created_at' => now(), 'updated_at' => now(),
        ]], ['user_id', 'product_id'], ['order_id', 'rating', 'fit', 'title', 'body', 'updated_at']);
    }
}
