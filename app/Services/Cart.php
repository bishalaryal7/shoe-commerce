<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Variant;
use App\Models\WholesaleAccount;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Cart
{
    private const MAX_LINE_QUANTITY = 999;

    public function __construct(private VariantRepository $variants) {}

    public function wholesale(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && WholesaleAccount::where('user_id', $user->id)
                ->where('status', 'approved')
                ->exists();
    }

    /** @return list<array{id: int, name: string, image: string, sku: string, size: string, color: string, stock: int, quantity: int, unit_price: int|float}> */
    public function lines(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $wholesale = $this->wholesale($request);

        return $this->variants->forCart(array_keys($cart))
            ->filter(fn ($variant) => $variant->product->active)
            ->map(fn ($variant) => $this->line($variant, $cart, $wholesale))
            ->values()
            ->all();
    }

    public function add(Request $request, int $variantId, int $quantity): void
    {
        $variant = $this->variants->find($variantId);
        abort_unless($variant->product->active, 404);
        $cart = $request->session()->get('cart', []);
        $quantity += ($cart[$variant->id] ?? 0);
        if ($quantity > min($variant->stock, self::MAX_LINE_QUANTITY)) {
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

    /** @return array{added: int, skipped: int} */
    public function addAvailableOrderItems(Request $request, Order $order): array
    {
        $requested = $order->items->groupBy('variant_id')->map(fn ($items) => $items->sum('quantity'))->all();
        if (! $requested) {
            return ['added' => 0, 'skipped' => 0];
        }
        $cart = $request->session()->get('cart', []);
        $variants = $this->variants->forCart(array_keys($requested))->keyBy('id');
        $added = 0;
        $skipped = 0;
        foreach ($requested as $id => $quantity) {
            $variant = $variants->get((int) $id);
            $inBag = (int) ($cart[$id] ?? 0);
            if (! $variant || ! $variant->product->active) {
                $skipped += $quantity;

                continue;
            }
            $room = max(0, min($variant->stock, self::MAX_LINE_QUANTITY) - $inBag);
            $toAdd = min((int) $quantity, $room);
            if ($toAdd > 0) {
                $cart[$id] = $inBag + $toAdd;
                $added += $toAdd;
            }
            $skipped += (int) $quantity - $toAdd;
        }
        if ($added > 0) {
            $this->save($request, $cart);
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    private function save(Request $request, array $cart): void
    {
        $request->session()->forget('checkout_token');
        $request->session()->put('cart', $cart);
    }

    /** @param array<int|string, int> $cart
     * @return array{id: int, name: string, image: string, sku: string, size: string, color: string, stock: int, quantity: int, unit_price: int|float}
     */
    private function line(Variant $variant, array $cart, bool $wholesale): array
    {
        $product = $variant->product;
        $name = app()->getLocale() === 'ne'
            ? ($product->name_ne ?: __($product->name))
            : $product->name;

        return [
            'id' => $variant->id,
            'name' => $name,
            'image' => $product->image,
            'sku' => $variant->sku,
            'size' => $variant->size,
            'color' => $variant->color,
            'stock' => $variant->stock,
            'quantity' => (int) $cart[$variant->id],
            'unit_price' => $wholesale ? $product->wholesale_price : $product->retail_price,
        ];
    }
}
