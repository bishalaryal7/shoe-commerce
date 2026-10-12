<?php

namespace App\Http\Controllers;

use App\Models\MerchantOrder;
use App\Models\MerchantStore;
use App\Models\MerchantVariant;
use App\Notifications\StoreUpdate;
use App\Repositories\MerchantStoreRepository;
use App\Rules\ContactPhone;
use App\Services\MerchantCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MerchantShopController extends Controller
{
    public function __construct(private MerchantStoreRepository $stores) {}

    public function myOrders(Request $request)
    {
        $orders = MerchantOrder::with(['store:id,slug,name', 'items'])
            ->where('user_id', $request->user()->id)
            ->latest()->paginate(15);

        return Inertia::render('merchant/Orders', ['orders' => $orders]);
    }

    public function directory(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $stores = $this->stores->directory($filters);

        return Inertia::render('merchant/Directory', ['stores' => $stores, 'filters' => $filters]);
    }

    private function available(MerchantStore $store): void
    {
        $this->stores->assertPublic($store);
    }

    public function index(Request $request, MerchantStore $store)
    {
        $this->available($store);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:Shoes,Clothing,Accessories'],
        ]);
        $products = $this->stores->products($store, $filters);

        return Inertia::render('merchant/Shop', ['store' => $store, 'products' => $products, 'filters' => $filters,
            'cartCount' => array_sum($request->session()->get('merchant_cart.'.$store->id, []))]);
    }

    public function product(MerchantStore $store, string $product)
    {
        $this->available($store);
        $item = $store->products()->with('variants')->where('slug', $product)->where('active', true)->firstOrFail();

        return Inertia::render('merchant/Product', ['store' => $store, 'product' => $item]);
    }

    public function cart(Request $request, MerchantStore $store, MerchantCommerce $commerce)
    {
        $this->available($store);

        return Inertia::render('merchant/Cart', ['store' => $store, 'lines' => $commerce->cart($request, $store)]);
    }

    public function add(Request $request, MerchantStore $store, MerchantCommerce $commerce)
    {
        $this->available($store);
        $data = $request->validate(['variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        $variant = MerchantVariant::with('product')->findOrFail($data['variant_id']);
        $key = 'merchant_cart.'.$store->id;
        $current = $request->session()->get($key, []);
        $commerce->put($request, $store, $variant, (int) ($current[$variant->id] ?? 0) + $data['quantity']);

        return redirect('/stores/'.$store->slug.'/cart')->with('success', __('Added to your bag.'));
    }

    public function updateCart(Request $request, MerchantStore $store, int $variant, MerchantCommerce $commerce)
    {
        $this->available($store);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $commerce->put($request, $store, MerchantVariant::with('product')->findOrFail($variant), $data['quantity']);

        return back();
    }

    public function checkout(Request $request, MerchantStore $store, MerchantCommerce $commerce)
    {
        $this->available($store);
        $lines = $commerce->cart($request, $store);
        if (! $lines) {
            return redirect('/stores/'.$store->slug.'/cart');
        }

        $key = $commerce->checkoutKey($store);
        if (! $request->session()->has($key)) {
            $request->session()->put($key, (string) Str::uuid());
        }

        return Inertia::render('merchant/Checkout', ['store' => $store, 'lines' => $lines, 'checkoutToken' => $request->session()->get($key)]);
    }

    public function place(Request $request, MerchantStore $store, MerchantCommerce $commerce)
    {
        $this->available($store);
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => ContactPhone::normalize($request->input('phone'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['required', 'string', 'max:40', new ContactPhone],
            'address' => ['required', 'string', 'min:10', 'max:1000'],
            'checkout_token' => ['required', 'uuid'],
        ]);
        $token = $data['checkout_token'];
        unset($data['checkout_token']);
        $order = $commerce->checkout($request, $store, $data, $token);
        if ($order->wasRecentlyCreated) {
            $store->account->user->notify(new StoreUpdate(
                __('New order for :store', ['store' => $store->name]),
                __('Open your store workspace to review the order.'),
                '/merchant',
                'merchant_order',
            ));
        }

        return redirect('/stores/'.$store->slug.'/orders/'.$order->reference)->with('success', __('Your order request was sent to the seller.'));
    }

    public function order(Request $request, MerchantStore $store, string $reference)
    {
        $order = $store->orders()->with('items')->where('reference', $reference)->firstOrFail();
        $owner = $store->account->user_id === $request->user()->id;
        abort_unless($owner || $order->user_id === $request->user()->id, 404);

        return Inertia::render('merchant/Order', ['store' => $store, 'order' => $order]);
    }

    public function messages(Request $request, MerchantStore $store)
    {
        $this->available($store);
        $threads = $store->conversations()->where('user_id', $request->user()->id)->with('messages')->latest('last_message_at')->get();

        return Inertia::render('merchant/Messages', ['store' => $store, 'conversations' => $threads]);
    }

    public function message(Request $request, MerchantStore $store)
    {
        $this->available($store);
        $data = $request->validate(['subject' => ['required', 'string', 'min:4', 'max:150'], 'body' => ['required', 'string', 'min:10', 'max:4000']]);
        DB::transaction(function () use ($request, $store, $data) {
            $thread = $store->conversations()->create(['user_id' => $request->user()->id, 'subject' => trim($data['subject']), 'last_message_at' => now()]);
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
        });
        $store->account->user->notify(new StoreUpdate(__('A customer messaged your store'), __('Open your store workspace to reply.'), '/merchant', 'merchant_message'));

        return back()->with('success', __('Your message was sent to the seller.'));
    }

    public function reply(Request $request, MerchantStore $store, int $conversation)
    {
        $this->available($store);
        $thread = $store->conversations()->where('user_id', $request->user()->id)->findOrFail($conversation);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:4000']]);
        $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
        $thread->update(['last_message_at' => now()]);
        $store->account->user->notify(new StoreUpdate(__('A customer replied to your store'), __('Open your store workspace to reply.'), '/merchant', 'merchant_message'));

        return back()->with('success', __('Reply sent.'));
    }
}
