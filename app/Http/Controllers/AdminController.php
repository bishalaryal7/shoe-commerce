<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ProductRequest;
use App\Http\Requests\Staff\InventoryImportRequest;
use App\Http\Requests\Staff\OfflinePaymentRequest;
use App\Http\Requests\Staff\OrderStatusRequest;
use App\Http\Requests\Staff\StockRequest;
use App\Http\Requests\Staff\WholesaleReviewRequest;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Repositories\ProductRepository;
use App\Services\AdminWorkspace;
use App\Services\InventoryExport;
use App\Services\InventoryImport;
use App\Services\OrderPayments;
use App\Services\StaffOperations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request, AdminWorkspace $workspace)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|in:pending_confirmation,confirmed,fulfilled,cancelled',
            'payment' => 'nullable|in:unpaid,pending,paid,review,refunded',
        ]);

        return Inertia::render('admin/Index', ['filters' => $filters, ...$workspace->data($filters)]);
    }

    public function stock(StockRequest $request, Variant $variant, StaffOperations $staff)
    {
        $staff->stock($variant->id, $request->validated('stock'), $request->user()->id);

        return back()->with('success', __('Available stock updated.'));
    }

    public function exportInventory(InventoryExport $inventory)
    {
        return $inventory->download();
    }

    public function importInventory(InventoryImportRequest $request, InventoryImport $inventory)
    {
        $count = $inventory->import($request->file('inventory_file'), $request->user()->id);

        return back()->with('success', __('Inventory checked for :count sizes. Invalid files never change stock.', ['count' => $count]));
    }

    public function product(ProductRequest $request, Product $product, ProductRepository $products)
    {
        $products->update($product, $request->validated());

        return back()->with('success', __('Product updated.'));
    }

    public function approve(WholesaleReviewRequest $request, WholesaleAccount $account)
    {
        $data = $request->validated();
        $account->update($data);

        return back()->with('success', __('Wholesale application updated.'));
    }

    public function order(OrderStatusRequest $request, Order $order, StaffOperations $staff)
    {
        $staff->transition($order, $request->validated('status'), $request->user()->id);

        return back()->with('success', __('Order updated.'));
    }

    public function offlinePayment(OfflinePaymentRequest $request, Order $order, StaffOperations $staff)
    {
        $staff->offlinePayment($order, $request->validated('reference'), $request->user()->id);

        return back()->with('success', __('Offline payment recorded. The order is ready for fulfillment.'));
    }

    public function verifyPayment(PaymentAttempt $attempt, OrderPayments $payments)
    {
        try {
            $status = $payments->verify($attempt);
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => __('Provider verification is unavailable; payment status was not changed.')]);
        }

        return back()->with('success', __('Provider status verified: :status.', ['status' => __($status)]));
    }
}
