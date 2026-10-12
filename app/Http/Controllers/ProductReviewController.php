<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ProductReviews;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, ProductReviews $reviews)
    {
        $reviews->save($request->user(), $product, $request->validated(), $request->file('photo'));

        return back()->with('success', __('Thank you for sharing your review.'));
    }

    public function helpful(Request $request, Product $product, int $review, ProductReviews $reviews)
    {
        $helpful = $reviews->toggleHelpful($request->user(), $product, $review);

        return back()->with('success', $helpful ? __('Thanks for helping other shoppers.') : __('Your helpful vote was removed.'));
    }

    public function comment(Request $request, Product $product, int $review, ProductReviews $reviews)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:4', 'max:1000']]);
        $record = $product->reviews()->with('user')->findOrFail($review);
        $reviews->comment($request->user(), $product, $record, $data['body']);

        return back()->with('success', __('Your comment was posted.'));
    }

    public function reply(Request $request, ProductReview $review, ProductReviews $reviews)
    {
        $data = $request->validate(['reply' => ['required', 'string', 'min:2', 'max:2000']]);
        $review->load('user', 'product');
        $reviews->reply($request->user(), $review, $data['reply']);

        return back()->with('success', __('Your reply is now visible on the product page.'));
    }
}
