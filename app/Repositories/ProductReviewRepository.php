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
            ->first(['id', 'rating', 'fit', 'title', 'body']);

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

    public function listing(Product $product, ?User $user)
    {
        $page = DB::table('product_reviews')->where('product_id', $product->id)->orderByDesc('id')
            ->paginate(5, ['id', 'rating', 'fit', 'title', 'body', 'created_at'], 'reviews_page')->withQueryString();
        $ids = $page->getCollection()->pluck('id');
        $counts = DB::table('product_review_helpful_votes')->whereIn('review_id', $ids)
            ->selectRaw('review_id, COUNT(*) as total')->groupBy('review_id')->pluck('total', 'review_id');
        $voted = $user ? DB::table('product_review_helpful_votes')->where('user_id', $user->id)
            ->whereIn('review_id', $ids)->pluck('review_id') : collect();

        $page->getCollection()->transform(function ($review) use ($counts, $voted) {
            $review->helpful_count = (int) ($counts[$review->id] ?? 0);
            $review->voted_helpful = $voted->contains($review->id);

            return $review;
        });

        return $page;
    }

    public function toggleHelpful(User $user, Product $product, int $reviewId): bool
    {
        return DB::transaction(function () use ($user, $product, $reviewId) {
            $review = DB::table('product_reviews')->where('id', $reviewId)
                ->where('product_id', $product->id)->lockForUpdate()->first();
            abort_if($review === null, 404);
            abort_if((int) $review->user_id === $user->id, 403);

            $vote = DB::table('product_review_helpful_votes')->where('review_id', $reviewId)
                ->where('user_id', $user->id)->first();
            if ($vote) {
                DB::table('product_review_helpful_votes')->where('id', $vote->id)->delete();

                return false;
            }

            DB::table('product_review_helpful_votes')->insert([
                'review_id' => $reviewId, 'user_id' => $user->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return true;
        });
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
