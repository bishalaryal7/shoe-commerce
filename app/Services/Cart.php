<?php

namespace App\Services;

use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Cart
{
    public function __construct(private VariantRepository $variants) {}

    public function wholesale(Request $request): bool
    {
        return $request->user() && WholesaleAccount::where('user_id', $request->user()->id)->where('status', 'approved')->exists();
    }

    public function lines(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $wholesale = $this->wholesale($request);

        return $this->variants->forCart(array_keys($cart))
            ->filter(fn ($v) => $v->product->active)->map(fn ($v) => [
                'id' => $v->id, 'name' => app()->getLocale() === 'ne' ? ($v->product->name_ne ?: __($v->product->name)) : $v->product->name, 'image' => $v->product->image,
                'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color, 'stock' => $v->stock,
                'quantity' => (int) $cart[$v->id],
                'unit_price' => $wholesale ? $v->product->wholesale_price : $v->product->retail_price,
            ])->values()->all();
    }

    public function add(Request $request, int $variantId, int $quantity): void
    {
        $variant = $this->variants->find($variantId);
        abort_unless($variant->product->active, 404);
        $cart = $request->session()->get('cart', []);
        $quantity += ($cart[$variant->id] ?? 0);
        if ($quantity > min($variant->stock, 999)) {
            throw ValidationException::withMessages(['quantity' => __('There is not enough stock for that size.')]);
        }
        $cart[$variant->id] = $quantity;
        $this->save($request, $cart);
    }

    public function update(Request $request, Variant $variant, int $quantity): void
    {
        $cart = $request->session()->get('cart', []);
        abort_unless(isset($cart[$variant->id]) && $variant->product->active, 404);
        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => __('That quantity is no longer available. Please choose fewer pairs.')]);
        }
        $cart[$variant->id] = $quantity;
        $this->save($request, $cart);
    }

    public function remove(Request $request, Variant $variant): void
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$variant->id]);
        $this->save($request, $cart);
    }

    private function save(Request $request, array $cart): void
    {
        $request->session()->forget('checkout_token');
        $request->session()->put('cart', $cart);
    }
}
