<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Notifications\StoreUpdate;
use Illuminate\Http\Request;

class ProductQuestionController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->active, 404);
        $data = $request->validate(['question' => ['required', 'string', 'min:8', 'max:1200']]);
        $product->questions()->create(['user_id' => $request->user()->id, 'question' => trim($data['question'])]);

        return back()->with('success', __('Your question was sent to our team.'));
    }

    public function answer(Request $request, ProductQuestion $question)
    {
        $data = $request->validate(['answer' => ['required', 'string', 'min:2', 'max:2000']]);
        $question->update(['answer' => trim($data['answer']), 'answered_by' => $request->user()->id, 'answered_at' => now()]);
        if ($question->user) {
            $question->user->notify(new StoreUpdate(
                __('Your product question has an answer'),
                __('Open the shoe page to read the answer from our team.'),
                '/products/'.$question->product()->value('slug').'#questions',
                'product_answer',
            ));
        }

        return back()->with('success', __('Product answer published.'));
    }
}
