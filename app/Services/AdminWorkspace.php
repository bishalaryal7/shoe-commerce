<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\Promotion;
use App\Models\SupportConversation;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Payments\KhaltiGateway;
use App\Payments\PayPayGateway;
use App\Repositories\UserRepository;
use App\Services\Auth\SignInOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminWorkspace
{
    public function __construct(
        private UserRepository $users,
        private SignInOptions $signInOptions,
        private KhaltiGateway $khalti,
        private PayPayGateway $paypay,
    ) {}

    public function data(array $filters): array
    {
        return [
            'products' => Product::with('variants')
                ->orderBy('id')
                ->get()
                ->makeVisible('wholesale_price'),
            'orders' => $this->orders($filters),
            'team' => $this->users->staff(),
            'applications' => WholesaleAccount::with('user:id,name,email')
                ->orderBy('status')
                ->get(),
            'payments' => PaymentAttempt::latest('id')
                ->paginate(20, ['*'], 'payments_page')
                ->withQueryString(),
            'integrations' => $this->integrations(),
            'stats' => $this->stats(),
            'lowStockThreshold' => max(0, config('shop.low_stock_threshold')),
            'promotions' => Promotion::latest('id')->get(),
            'questions' => ProductQuestion::with(['product:id,name,slug', 'user:id,name'])->latest('id')->paginate(20, ['*'], 'questions_page')->withQueryString(),
            'reviews' => ProductReview::with(['product:id,name,slug', 'user:id,name,email'])->latest('id')->paginate(20, ['*'], 'reviews_page')->withQueryString(),
            'conversations' => SupportConversation::with(['user:id,name,email', 'messages' => fn ($messages) => $messages->orderByDesc('id')->limit(8)])
                ->latest('last_message_at')->paginate(20, ['*'], 'messages_page')->withQueryString(),
        ];
    }

    private function orders(array $filters): LengthAwarePaginator
    {
        return Order::with(['items', 'events.actor:id,name'])
            ->when($filters['search'] ?? null, function (Builder $orders, string $search) {
                $term = '%'.$search.'%';
                $orders->where(function (Builder $query) use ($term) {
                    $query->where('reference', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $orders, string $status) => $orders->where('status', $status))
            ->when($filters['payment'] ?? null, fn (Builder $orders, string $payment) => $orders->where('payment_status', $payment))
            ->latest('id')
            ->paginate(20, ['*'], 'orders_page')
            ->withQueryString();
    }

    private function integrations(): array
    {
        return [
            'signIn' => $this->signInOptions->all(),
            'cashOnDelivery' => config('shop.cash_on_delivery') && config('shop.currency') === 'NPR',
            'khalti' => $this->khalti->available(),
            'paypay' => $this->paypay->available(),
            'mail' => config('mail.default'),
            'pendingEmails' => OrderEmail::whereNull('sent_at')->count(),
        ];
    }

    private function stats(): array
    {
        $lowStockThreshold = max(0, config('shop.low_stock_threshold'));

        return [
            'orders' => Order::count(),
            'pending' => Order::where('status', 'pending_confirmation')->count(),
            'stock' => Variant::sum('stock'),
            'low_stock' => Variant::where('stock', '<=', $lowStockThreshold)
                ->whereHas('product', fn (Builder $products) => $products->where('active', true))
                ->count(),
            'applications' => WholesaleAccount::where('status', 'pending')->count(),
        ];
    }
}
