<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function listing(Product $product, ?User $user, string $sort, string $search = '')
    {
        $query = DB::table('product_reviews')->select([
            'product_reviews.id', 'product_reviews.rating', 'product_reviews.fit',
            'product_reviews.title', 'product_reviews.body', 'product_reviews.created_at',
            'product_reviews.seller_reply', 'product_reviews.seller_replied_at', 'product_reviews.photo_path',
        ])->where('product_id', $product->id)
            ->addSelect(['helpful_count' => DB::table('product_review_helpful_votes')->selectRaw('COUNT(*)')
                ->whereColumn('review_id', 'product_reviews.id')]);
        if ($search !== '') {
            $query->where(function ($reviews) use ($search) {
                $reviews->where('product_reviews.title', 'like', '%'.$search.'%')
                    ->orWhere('product_reviews.body', 'like', '%'.$search.'%');
            });
        }
        match ($sort) {
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('id'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
        $page = $query->paginate(5, ['*'], 'reviews_page')->withQueryString();
        $ids = $page->getCollection()->pluck('id');
        $voted = $user ? DB::table('product_review_helpful_votes')->where('user_id', $user->id)
            ->whereIn('review_id', $ids)->pluck('review_id') : collect();
        $comments = DB::table('product_review_comments')
            ->whereIn('product_review_id', $ids)
            ->orderBy('id')
            ->get(['id', 'product_review_id', 'body', 'is_staff', 'created_at'])
            ->groupBy('product_review_id');

        $page->getCollection()->transform(function ($review) use ($voted, $comments) {
            $review->helpful_count = (int) $review->helpful_count;
            $review->photo_url = $review->photo_path ? Storage::disk('public')->url($review->photo_path) : null;
            unset($review->photo_path);
            $review->voted_helpful = $voted->contains($review->id);
            $review->comments = $comments->get($review->id, collect())->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'is_staff' => (bool) $comment->is_staff,
                'created_at' => $comment->created_at,
            ])->values();

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

    public function save(User $user, Product $product, int $orderId, array $data, ?string $photoPath = null): void
    {
        $values = [
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $orderId,
            'rating' => $data['rating'], 'fit' => $data['fit'] ?? null,
            'title' => $data['title'], 'body' => $data['body'],
            'created_at' => now(), 'updated_at' => now(),
        ];
        $updates = ['order_id', 'rating', 'fit', 'title', 'body', 'updated_at'];
        if ($photoPath !== null) {
            $values['photo_path'] = $photoPath;
            $updates[] = 'photo_path';
        }
        DB::table('product_reviews')->upsert([$values], ['user_id', 'product_id'], $updates);
    }
}
