<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ReviewRequest;
use App\Models\Product;
use App\Services\ProductReviews;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, ProductReviews $reviews)
    {
        $reviews->save($request->user(), $product, $request->validated());

        return back()->with('success', __('Thank you for sharing your review.'));
    }

    public function helpful(Request $request, Product $product, int $review, ProductReviews $reviews)
    {
        $helpful = $reviews->toggleHelpful($request->user(), $product, $review);

        return back()->with('success', $helpful ? __('Thanks for helping other shoppers.') : __('Your helpful vote was removed.'));
    }
}
