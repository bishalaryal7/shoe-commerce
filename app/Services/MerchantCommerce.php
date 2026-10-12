<?php

namespace App\Services;

use App\Models\MerchantOrder;
use App\Models\MerchantStore;
use App\Models\MerchantVariant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MerchantCommerce
{
    private function key(MerchantStore $store): string
    {
        return 'merchant_cart.'.$store->id;
    }

    public function checkoutKey(MerchantStore $store): string
    {
        return 'merchant_checkout.'.$store->id;
    }

    public function cart(Request $request, MerchantStore $store): array
    {
        $quantities = $request->session()->get($this->key($store), []);
        $variants = MerchantVariant::with('product')->whereIn('id', array_keys($quantities))->get();

        return $variants->filter(fn (MerchantVariant $variant) => $variant->product->merchant_store_id === $store->id && $variant->product->active)
            ->map(fn (MerchantVariant $variant) => [
                'variant_id' => $variant->id,
                'product' => $variant->product->name,
                'slug' => $variant->product->slug,
                'image_url' => $variant->product->image_url,
                'size' => $variant->size,
                'stock' => $variant->stock,
                'quantity' => (int) $quantities[$variant->id],
                'unit_price' => $variant->product->price,
            ])->values()->all();
    }

    public function put(Request $request, MerchantStore $store, MerchantVariant $variant, int $quantity): void
    {
        abort_unless($variant->product->merchant_store_id === $store->id && $variant->product->active, 404);
        if ($quantity > 20) {
            throw ValidationException::withMessages(['quantity' => __('Choose no more than 20 items of one size.')]);
        }
        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => __('Only :count items are available.', ['count' => $variant->stock])]);
        }
        $cart = $request->session()->get($this->key($store), []);
        if ($quantity === 0) {
            unset($cart[$variant->id]);
        } else {
            $cart[$variant->id] = $quantity;
        }
        $request->session()->put($this->key($store), $cart);
        $request->session()->forget($this->checkoutKey($store));
    }

    public function checkout(Request $request, MerchantStore $store, array $buyer, string $token): MerchantOrder
    {
        $existing = $store->orders()->where('checkout_token', $token)->where('user_id', $request->user()->id)->first();
        if ($existing) {
            $request->session()->forget($this->key($store));
            $request->session()->forget($this->checkoutKey($store));

            return $existing;
        }
        if ($request->session()->get($this->checkoutKey($store)) !== $token) {
            throw ValidationException::withMessages(['cart' => __('Your checkout session expired. Review your bag and try again.')]);
        }
        $lines = $this->cart($request, $store);
        if (! $lines) {
            throw ValidationException::withMessages(['cart' => __('Your bag is empty.')]);
        }

        try {
            $order = DB::transaction(function () use ($store, $request, $buyer, $lines, $token) {
                $order = $store->orders()->create([
                    'user_id' => $request->user()->id,
                    'reference' => (string) Str::uuid(),
                    'checkout_token' => $token,
                    ...$buyer,
                    'total' => 0,
                    'status' => 'pending',
                ]);
                $total = 0;
                foreach ($lines as $line) {
                    $variant = MerchantVariant::with('product')->whereKey($line['variant_id'])->lockForUpdate()->firstOrFail();
                    if ($variant->product->merchant_store_id !== $store->id || ! $variant->product->active || $variant->stock < $line['quantity']) {
                        throw ValidationException::withMessages(['cart' => __('An item in your bag is no longer available. Review your bag and try again.')]);
                    }
                    $variant->decrement('stock', $line['quantity']);
                    $order->items()->create([
                        'merchant_variant_id' => $variant->id,
                        'name' => $variant->product->name,
                        'size' => $variant->size,
                        'quantity' => $line['quantity'],
                        'unit_price' => $variant->product->price,
                    ]);
                    $total += $variant->product->price * $line['quantity'];
                }
                $order->update(['total' => $total]);

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $order = $store->orders()->where('checkout_token', $token)->where('user_id', $request->user()->id)->first();
            if (! $order) {
                throw $exception;
            }
        }
        $request->session()->forget($this->key($store));
        $request->session()->forget($this->checkoutKey($store));

        return $order;
    }
}
