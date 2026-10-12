<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreOrderReturnRequest;
use App\Http\Requests\Staff\ReviewOrderReturnRequest;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Services\OrderReturns;

class OrderReturnController extends Controller
{
    public function store(StoreOrderReturnRequest $request, string $reference, OrderReturns $returns)
    {
        $order = Order::where('reference', $reference)->where('user_id', $request->user()->id)->firstOrFail();
        $returns->request($order, $request->user(), $request->validated());

        return back()->with('success', __('Your return or exchange request was sent to our team.'));
    }

    public function review(ReviewOrderReturnRequest $request, OrderReturnRequest $returnRequest, OrderReturns $returns)
    {
        $returns->review($returnRequest, $request->user(), $request->validated());

        return back()->with('success', __('Return request updated.'));
    }
}
