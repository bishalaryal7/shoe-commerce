<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ProductRequest;
use App\Http\Requests\Staff\OfflinePaymentRequest;
use App\Http\Requests\Staff\OrderStatusRequest;
use App\Http\Requests\Staff\StockRequest;
use App\Http\Requests\Staff\WholesaleReviewRequest;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Payments\KhaltiGateway;
use App\Payments\PayPayGateway;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\OrderPayments;
use App\Services\StaffOperations;
use Illuminate\Http\Request;
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
            'team' => app(UserRepository::class)->staff(),
            'applications' => WholesaleAccount::with('user:id,name,email')->orderBy('status')->get(),
            'payments' => PaymentAttempt::latest('id')->paginate(20, ['*'], 'payments_page')->withQueryString(),
            'integrations' => ['khalti' => app(KhaltiGateway::class)->available(), 'paypay' => app(PayPayGateway::class)->available(), 'mail' => config('mail.default'), 'pendingEmails' => OrderEmail::whereNull('sent_at')->count()],
            'stats' => ['orders' => Order::count(), 'pending' => Order::where('status', 'pending_confirmation')->count(),
                'stock' => Variant::sum('stock'), 'applications' => WholesaleAccount::where('status', 'pending')->count()],
        ]);
    }

    public function stock(StockRequest $request, Variant $variant, StaffOperations $staff)
    {
        $staff->stock($variant->id, $request->validated('stock'), $request->user()->id);

        return back()->with('success', 'Available stock updated.');
    }

    public function product(ProductRequest $request, Product $product, ProductRepository $products)
    {
        $products->update($product, $request->validated());

        return back()->with('success', 'Product updated.');
    }

    public function approve(WholesaleReviewRequest $request, WholesaleAccount $account)
    {
        $data = $request->validated();
        $account->update($data);

        return back()->with('success', 'Wholesale application updated.');
    }

    public function order(OrderStatusRequest $request, Order $order, StaffOperations $staff)
    {
        $staff->transition($order, $request->validated('status'), $request->user()->id);

        return back()->with('success', 'Order updated.');
    }

    public function offlinePayment(OfflinePaymentRequest $request, Order $order, StaffOperations $staff)
    {
        $staff->offlinePayment($order, $request->validated('reference'), $request->user()->id);

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
