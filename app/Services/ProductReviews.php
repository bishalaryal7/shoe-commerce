<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductReviewRepository;
use Illuminate\Validation\ValidationException;

class ProductReviews
{
    public function __construct(private ProductReviewRepository $reviews) {}

    public function page(Product $product, ?User $user): array
    {
        return [
            'reviewSummary' => $this->reviews->summary($product),
            'reviews' => $this->reviews->listing($product, $user),
            'ownReview' => $user ? $this->reviews->own($user, $product) : null,
            'canReview' => $user && $this->reviews->eligibleOrderId($user, $product) !== null,
        ];
    }

    public function save(User $user, Product $product, array $data): void
    {
        abort_unless($product->active, 404);
        $orderId = $this->reviews->eligibleOrderId($user, $product);
        if ($orderId === null) {
            throw ValidationException::withMessages(['review' => __('You can review a shoe after your paid order is fulfilled.')]);
        }
        $this->reviews->save($user, $product, $orderId, $data);
    }

    public function toggleHelpful(User $user, Product $product, int $reviewId): bool
    {
        abort_unless($product->active, 404);

        return $this->reviews->toggleHelpful($user, $product, $reviewId);
    }
}
