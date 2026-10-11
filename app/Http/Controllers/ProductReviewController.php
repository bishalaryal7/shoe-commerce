<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ReviewRequest;
use App\Models\Product;
use App\Services\ProductReviews;

class ProductReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, ProductReviews $reviews)
    {
        $reviews->save($request->user(), $product, $request->validated());

        return back()->with('success', __('Thank you for sharing your review.'));
    }
}
