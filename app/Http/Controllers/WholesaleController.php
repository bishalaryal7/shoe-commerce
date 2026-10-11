<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\WholesaleRequest;
use App\Models\WholesaleAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WholesaleController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('shop/Wholesale', ['account' => $request->user() ? WholesaleAccount::where('user_id', $request->user()->id)->first() : null]);
    }

    public function store(WholesaleRequest $request)
    {
        $data = $request->validated();
        if (WholesaleAccount::where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['company' => __('You already have a wholesale application.')]);
        }
        WholesaleAccount::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);

        return back()->with('success', __('Application received. Our team will review your business.'));
    }
}
