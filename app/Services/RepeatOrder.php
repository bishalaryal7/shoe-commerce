<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RepeatOrder
{
    public function __construct(private Cart $cart) {}

    public function addToBag(Request $request, User $user, Order $order): array
    {
        abort_unless($order->user_id === $user->id, 404);
        abort_unless($order->status === 'fulfilled' && $order->payment_status === 'paid', 422);
        $result = $this->cart->addAvailableOrderItems($request, $order->loadMissing('items'));
        if ($result['added'] === 0) {
            throw ValidationException::withMessages(['cart' => __('None of the shoes from this order are available in your sizes right now.')]);
        }

        return $result;
    }
}
