<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Index', [
            'products' => Product::with('variants')->orderBy('id')->get()->makeVisible('wholesale_price'),
            'orders' => Order::with('items')->latest()->limit(50)->get(),
            'applications' => WholesaleAccount::with('user:id,name,email')->orderBy('status')->get(),
            'stats' => ['orders' => Order::count(), 'pending' => Order::where('status', 'pending_confirmation')->count(),
                'stock' => Variant::sum('stock'), 'applications' => WholesaleAccount::where('status', 'pending')->count()],
        ]);
    }

    public function stock(Request $request, Variant $variant)
    {
        $data = $request->validate(['stock' => 'required|integer|min:0|max:1000000']);
        $variant->update($data);

        return back()->with('success', 'Available stock updated.');
    }

    public function product(Request $request, Product $product)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'retail_price' => 'required|integer|min:1|max:100000000',
            'wholesale_price' => 'required|integer|min:1|max:100000000', 'active' => 'required|boolean']);
        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function approve(Request $request, WholesaleAccount $account)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected']);
        $account->update($data);

        return back()->with('success', 'Wholesale application updated.');
    }

    public function order(Request $request, Order $order)
    {
        $data = $request->validate(['status' => 'required|in:confirmed,fulfilled,cancelled']);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['pending_confirmation' => ['confirmed', 'cancelled'], 'confirmed' => ['fulfilled', 'cancelled'], 'fulfilled' => [], 'cancelled' => []];
            abort_unless(in_array($data['status'], $allowed[$locked->status] ?? []), 422, 'This order cannot move to that status.');
            if ($data['status'] === 'cancelled') {
                foreach ($locked->items as $item) {
                    Variant::whereKey($item->variant_id)->increment('stock', $item->quantity);
                }
            }
            $locked->update($data);
        });

        return back()->with('success', 'Order updated.');
    }
}
