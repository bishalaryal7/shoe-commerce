<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\OrderEvent;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Payments\KhaltiGateway;
use App\Payments\PayPayGateway;
use App\Services\CancelOrder;
use App\Services\OrderEmails;
use App\Services\OrderPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|in:pending_confirmation,confirmed,fulfilled,cancelled',
            'payment' => 'nullable|in:unpaid,pending,paid,review,refunded',
        ]);
        $orders = Order::with(['items', 'events.actor:id,name'])->latest('id');
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $orders->where(function ($query) use ($search) {
                $query->where('reference', 'like', $search)->orWhere('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('phone', 'like', $search);
            });
        }
        if (! empty($filters['status'])) {
            $orders->where('status', $filters['status']);
        }
        if (! empty($filters['payment'])) {
            $orders->where('payment_status', $filters['payment']);
        }

        return Inertia::render('admin/Index', ['filters' => $filters,
            'products' => Product::with('variants')->orderBy('id')->get()->makeVisible('wholesale_price'),
            'orders' => $orders->paginate(20, ['*'], 'orders_page')->withQueryString(),
            'applications' => WholesaleAccount::with('user:id,name,email')->orderBy('status')->get(),
            'payments' => PaymentAttempt::latest('id')->paginate(20, ['*'], 'payments_page')->withQueryString(),
            'integrations' => ['khalti' => app(KhaltiGateway::class)->available(), 'paypay' => app(PayPayGateway::class)->available(), 'mail' => config('mail.default'), 'pendingEmails' => OrderEmail::whereNull('sent_at')->count()],
            'stats' => ['orders' => Order::count(), 'pending' => Order::where('status', 'pending_confirmation')->count(),
                'stock' => Variant::sum('stock'), 'applications' => WholesaleAccount::where('status', 'pending')->count()],
        ]);
    }

    public function stock(Request $request, Variant $variant)
    {
        $data = $request->validate(['stock' => 'required|integer|min:0|max:1000000']);
        DB::transaction(function () use ($request, $variant, $data) {
            $locked = Variant::lockForUpdate()->findOrFail($variant->id);
            $difference = $data['stock'] - $locked->stock;
            $locked->update($data);
            if ($difference !== 0) {
                InventoryMovement::create(['variant_id' => $locked->id, 'actor_id' => $request->user()->id, 'quantity' => $difference, 'reason' => 'staff_adjustment']);
            }
        });

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

    public function order(Request $request, Order $order, OrderEmails $emails)
    {
        $data = $request->validate(['status' => 'required|in:confirmed,fulfilled,cancelled']);
        if ($data['status'] === 'cancelled') {
            app(CancelOrder::class)->cancel($order, $request->user()->id, true);
        } else {
            DB::transaction(function () use ($order, $data, $emails, $request) {
                $locked = Order::lockForUpdate()->findOrFail($order->id);
                $allowed = ['pending_confirmation' => ['confirmed'], 'confirmed' => ['fulfilled']];
                abort_unless(in_array($data['status'], $allowed[$locked->status] ?? []), 422, 'This order cannot move to that status.');
                abort_if($data['status'] === 'fulfilled' && $locked->payment_status !== 'paid', 422, 'Record or verify payment before fulfilling this order.');
                $locked->update($data);
                OrderEvent::create(['order_id' => $locked->id, 'actor_id' => $request->user()->id, 'event' => $data['status']]);
                $emails->record($locked, $data['status']);
            });
        }

        return back()->with('success', 'Order updated.');
    }

    public function offlinePayment(Request $request, Order $order, OrderEmails $emails)
    {
        $data = $request->validate(['reference' => 'required|string|max:150']);
        DB::transaction(function () use ($request, $order, $data, $emails) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless($locked->payment_method === 'request' && $locked->payment_status === 'unpaid' && in_array($locked->status, ['pending_confirmation', 'confirmed']), 422, 'This order cannot record an offline payment.');
            $locked->update(['payment_method' => 'offline', 'payment_status' => 'paid', 'paid_at' => now(), 'status' => 'confirmed', 'offline_payment_reference' => $data['reference']]);
            OrderEvent::create(['order_id' => $locked->id, 'actor_id' => $request->user()->id, 'event' => 'offline_payment_received', 'details' => ['reference' => $data['reference'], 'amount' => $locked->total, 'currency' => $locked->currency]]);
            $emails->record($locked, 'paid');
        });

        return back()->with('success', 'Offline payment recorded. The order is ready for fulfillment.');
    }

    public function verifyPayment(PaymentAttempt $attempt, OrderPayments $payments)
    {
        try {
            $status = $payments->verify($attempt);
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => 'Provider verification is unavailable; payment status was not changed.']);
        }

        return back()->with('success', 'Provider status verified: '.$status.'.');
    }
}
