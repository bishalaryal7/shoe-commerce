<?php

namespace App\Payments;

use InvalidArgumentException;

class Gateways
{
    public function __construct(
        private KhaltiGateway $khalti,
        private PayPayGateway $paypay,
    ) {}

    public function get(string $provider): Gateway
    {
        return match ($provider) {
            'khalti' => $this->khalti,
            'paypay' => $this->paypay,
            default => throw new InvalidArgumentException('Unsupported payment provider.'),
        };
    }

    public function options(string $currency): array
    {
        $options = [];
        foreach (['khalti' => $this->khalti, 'paypay' => $this->paypay] as $provider => $gateway) {
            if ($gateway->available() && $gateway->currency() === $currency) {
                $options[] = [
                    'id' => $provider,
                    'name' => $provider === 'khalti' ? 'Khalti' : 'PayPay',
                    'sandbox' => (bool) config('payments.'.$provider.'.sandbox'),
                ];
            }
        }

        return $options;
    }
}
