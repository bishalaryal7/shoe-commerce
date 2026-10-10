<?php

namespace App\Http\Controllers;

use App\Models\WholesaleAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WholesaleController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('shop/Wholesale', ['account' => $request->user() ? WholesaleAccount::where('user_id', $request->user()->id)->first() : null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['company' => 'required|string|max:150', 'phone' => 'required|string|max:40', 'message' => 'nullable|string|max:2000']);
        if (WholesaleAccount::where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['company' => 'You already have a wholesale application.']);
        }
        WholesaleAccount::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);

        return back()->with('success', 'Application received. Our team will review your business.');
    }
}
