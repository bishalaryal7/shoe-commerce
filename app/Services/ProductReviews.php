<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewComment;
use App\Models\User;
use App\Notifications\StoreUpdate;
use App\Repositories\ProductReviewRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductReviews
{
    public function __construct(private ProductReviewRepository $reviews) {}

    public function page(Product $product, ?User $user, string $sort = 'recent', string $query = ''): array
    {
        return [
            'reviewSummary' => $this->reviews->summary($product),
            'reviews' => $this->reviews->listing($product, $user, $sort, $query),
            'ownReview' => $user ? $this->reviews->own($user, $product) : null,
            'canReview' => $user && $this->reviews->eligibleOrderId($user, $product) !== null,
        ];
    }

    public function save(User $user, Product $product, array $data, ?UploadedFile $photo = null): void
    {
        abort_unless($product->active, 404);
        $orderId = $this->reviews->eligibleOrderId($user, $product);
        if ($orderId === null) {
            throw ValidationException::withMessages(['review' => __('You can review a shoe after your paid order is fulfilled.')]);
        }
        $existing = ProductReview::where('user_id', $user->id)->where('product_id', $product->id)->first();
        $newPath = $photo?->store('review-photos', 'public');
        try {
            $this->reviews->save($user, $product, $orderId, $data, $newPath);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if ($newPath && $existing?->photo_path) {
            Storage::disk('public')->delete($existing->photo_path);
        }
    }

    public function toggleHelpful(User $user, Product $product, int $reviewId): bool
    {
        abort_unless($product->active, 404);

        return $this->reviews->toggleHelpful($user, $product, $reviewId);
    }

    public function reply(User $staff, ProductReview $review, string $reply): void
    {
        $review->update([
            'seller_reply' => trim($reply),
            'seller_replied_by' => $staff->id,
            'seller_replied_at' => now(),
        ]);

        if ($review->user) {
            $review->user->notify(new StoreUpdate(
                __('EVE Shoes replied to your review'),
                __('Open the product page to read the shop reply.'),
                '/products/'.$review->product()->value('slug').'#reviews',
                'review_reply',
            ));
        }
    }

    public function comment(User $user, Product $product, ProductReview $review, string $body): void
    {
        abort_unless($product->active && $review->product_id === $product->id, 404);

        ProductReviewComment::create([
            'product_review_id' => $review->id,
            'user_id' => $user->id,
            'body' => trim($body),
        ]);

        if ($review->user && $review->user->isNot($user)) {
            $review->user->notify(new StoreUpdate(
                __('Someone commented on your review'),
                __('Open the product page to read the comment.'),
                '/products/'.$product->slug.'#reviews',
                'review_comment',
            ));
        }
    }
}
