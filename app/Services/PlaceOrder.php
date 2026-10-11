<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private OrderRepository $orders, private OrderEmails $emails) {}

    public function place(array $data, array $lines, bool $wholesale, ?int $userId, int $cartSize): Order
    {
        try {
            return DB::transaction(function () use ($data, $lines, $wholesale, $userId, $cartSize) {
                if ($existing = $this->orders->byCheckoutToken($data['token'])) {
                    $this->assertOwner($existing, $userId);

                    return $existing;
                }
                if (! $lines || count($lines) !== $cartSize) {
                    throw ValidationException::withMessages(['cart' => __('Your bag has changed. Please review it before ordering.')]);
                }
                if ($wholesale && array_sum(array_column($lines, 'quantity')) < config('shop.wholesale_minimum')) {
                    throw ValidationException::withMessages(['cart' => __('Wholesale orders require at least 12 pairs.')]);
                }
                $subtotal = array_sum(array_map(fn ($line) => $line['unit_price'] * $line['quantity'], $lines));
                $shipping = max(0, config('shop.shipping_amount'));
                $tax = intdiv($subtotal * max(0, config('shop.tax_basis_points')) + 5000, 10000);
                $order = $this->orders->create(['reference' => (string) Str::uuid(), 'checkout_token' => $data['token'], 'user_id' => $userId, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'address' => $data['address'], 'channel' => $wholesale ? 'wholesale' : 'retail', 'currency' => config('shop.currency'), 'subtotal' => $subtotal, 'shipping_amount' => $shipping, 'tax_amount' => $tax, 'total' => $subtotal + $shipping + $tax, 'payment_method' => $data['payment_method'] ?? 'request', 'payment_status' => 'unpaid']);
                $this->orders->reserveItems($order, $lines, $userId);
                $this->emails->record($order, 'created');

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $order = $this->orders->byCheckoutToken($data['token']) ?? throw $exception;
            $this->assertOwner($order, $userId);

            return $order;
        }
    }

    private function assertOwner(Order $order, ?int $userId): void
    {
        if ($order->user_id !== null && $order->user_id !== $userId) {
            throw ValidationException::withMessages(['token' => __('This checkout belongs to another account. Return to your bag and start a new checkout.')]);
        }
    }
}
