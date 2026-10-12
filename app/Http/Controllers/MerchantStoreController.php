<?php

namespace App\Http\Controllers;

use App\Models\MerchantStore;
use App\Models\MerchantVariant;
use App\Models\WholesaleAccount;
use App\Notifications\StoreUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MerchantStoreController extends Controller
{
    private function priceInPaisa(string|int|float $price): int
    {
        [$rupees, $paisa] = array_pad(explode('.', (string) $price, 2), 2, '0');

        return ((int) $rupees * 100) + (int) str_pad($paisa, 2, '0');
    }

    private function approvedAccount(Request $request): WholesaleAccount
    {
        return WholesaleAccount::where('user_id', $request->user()->id)->where('status', 'approved')->firstOrFail();
    }

    private function ownedStore(Request $request): MerchantStore
    {
        return MerchantStore::where('wholesale_account_id', $this->approvedAccount($request)->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $account = $this->approvedAccount($request);
        $store = MerchantStore::where('wholesale_account_id', $account->id)->first();

        return Inertia::render('merchant/Manage', [
            'store' => $store,
            'products' => $store?->products()->with('variants')->latest()->get() ?? [],
            'orders' => $store?->orders()->with('items')->latest()->paginate(30, ['*'], 'orders_page')->withQueryString() ?? ['data' => [], 'links' => []],
            'conversations' => $store?->conversations()->with(['user:id,name,email', 'messages'])->latest('last_message_at')->paginate(20, ['*'], 'messages_page')->withQueryString() ?? ['data' => [], 'links' => []],
        ]);
    }

    public function create(Request $request)
    {
        $account = $this->approvedAccount($request);
        abort_if(MerchantStore::where('wholesale_account_id', $account->id)->exists(), 409);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $slug = Str::slug($data['name']);
        if ($slug === '') {
            $slug = 'shop';
        }
        $account->store()->create([...$data, 'slug' => $slug.'-'.Str::lower(Str::random(6))]);

        return redirect('/merchant')->with('success', __('Your storefront is ready.'));
    }

    public function update(Request $request)
    {
        $store = $this->ownedStore($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'active' => ['required', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        unset($data['logo'], $data['banner']);
        $oldPaths = [];
        $newPaths = [];
        foreach (['logo', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('merchant-stores/'.$store->id, 'public');
                if (! $path) {
                    Storage::disk('public')->delete($newPaths);
                    throw ValidationException::withMessages([$field => __('Photo upload could not be saved.')]);
                }
                $oldPaths[] = $store->{$field.'_path'};
                $newPaths[] = $path;
                $data[$field.'_path'] = $path;
            }
        }
        try {
            $store->update($data);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }
        Storage::disk('public')->delete(array_filter($oldPaths));

        return back()->with('success', __('Storefront updated.'));
    }

    public function product(Request $request)
    {
        $store = $this->ownedStore($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category' => ['required', Rule::in(['Shoes', 'Clothing', 'Accessories'])],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'price' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/', 'numeric', 'gt:0', 'max:1000000'],
            'size' => ['required', 'string', 'max:40'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $path = $request->file('image')->store('merchant-products/'.$store->id, 'public');
        if (! $path) {
            throw ValidationException::withMessages(['image' => __('Photo upload could not be saved.')]);
        }
        try {
            DB::transaction(function () use ($store, $data, $path) {
                $product = $store->products()->create([
                    'slug' => (Str::slug($data['name']) ?: 'product').'-'.Str::lower(Str::random(6)),
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'price' => $this->priceInPaisa($data['price']),
                    'image_path' => $path,
                ]);
                $product->variants()->create(['size' => trim($data['size']), 'stock' => $data['stock']]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return back()->with('success', __('Product added to your store.'));
    }

    public function updateProduct(Request $request, int $product)
    {
        $store = $this->ownedStore($request);
        $item = $store->products()->findOrFail($product);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category' => ['required', Rule::in(['Shoes', 'Clothing', 'Accessories'])],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'price' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/', 'numeric', 'gt:0', 'max:1000000'],
            'active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        unset($data['image']);
        $data['price'] = $this->priceInPaisa($data['price']);
        $newPath = null;
        $oldPath = null;
        if ($request->hasFile('image')) {
            $oldPath = $item->image_path;
            $newPath = $request->file('image')->store('merchant-products/'.$store->id, 'public');
            if (! $newPath) {
                throw ValidationException::withMessages(['image' => __('Photo upload could not be saved.')]);
            }
            $data['image_path'] = $newPath;
        }
        try {
            $item->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', __('Product updated.'));
    }

    public function variant(Request $request, int $product)
    {
        $item = $this->ownedStore($request)->products()->findOrFail($product);
        $data = $request->validate(['size' => ['required', 'string', 'max:40'], 'stock' => ['required', 'integer', 'min:0', 'max:100000']]);
        $item->variants()->updateOrCreate(['size' => trim($data['size'])], ['stock' => $data['stock']]);

        return back()->with('success', __('Size and stock updated.'));
    }

    public function order(Request $request, int $order)
    {
        $store = $this->ownedStore($request);
        $data = $request->validate(['status' => ['required', Rule::in(['accepted', 'shipped', 'fulfilled', 'cancelled'])]]);
        $item = DB::transaction(function () use ($store, $order, $data) {
            $item = $store->orders()->with('items')->whereKey($order)->lockForUpdate()->firstOrFail();
            $allowed = ['pending' => ['accepted', 'cancelled'], 'accepted' => ['shipped', 'cancelled'], 'shipped' => ['fulfilled']];
            abort_unless(in_array($data['status'], $allowed[$item->status] ?? [], true), 422);
            if ($data['status'] === 'cancelled') {
                foreach ($item->items as $line) {
                    MerchantVariant::whereKey($line->merchant_variant_id)->increment('stock', $line->quantity);
                }
            }
            $item->update(['status' => $data['status']]);

            return $item;
        });
        $item->user()->first()?->notify(new StoreUpdate(
            __('Your order status changed'),
            __('Your order from :store is now :status.', ['store' => $store->name, 'status' => __($item->status)]),
            '/stores/'.$store->slug.'/orders/'.$item->reference,
            'merchant_order',
        ));

        return back()->with('success', __('Order status updated.'));
    }

    public function reply(Request $request, int $conversation)
    {
        $thread = $this->ownedStore($request)->conversations()->findOrFail($conversation);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:4000']]);
        $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
        $thread->update(['last_message_at' => now()]);
        $thread->user?->notify(new StoreUpdate(__('The seller replied to your message'), __('Read the reply in your store conversations.'), '/stores/'.$this->ownedStore($request)->slug.'/messages', 'merchant_message'));

        return back()->with('success', __('Reply sent.'));
    }
}
