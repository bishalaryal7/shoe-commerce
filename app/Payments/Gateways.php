<?php

namespace App\Payments;

use InvalidArgumentException;

class Gateways
{
    public function get(string $provider): Gateway
    {
        return match ($provider) {
            'khalti' => app(KhaltiGateway::class),'paypay' => app(PayPayGateway::class),default => throw new InvalidArgumentException('Unsupported payment provider.')
        };
    }

    public function options(string $currency): array
    {
        $options = [];
        foreach (['khalti', 'paypay'] as $provider) {
            $gateway = $this->get($provider);
            if ($gateway->available() && $gateway->currency() === $currency) {
                $options[] = ['id' => $provider, 'name' => $provider === 'khalti' ? 'Khalti' : 'PayPay', 'sandbox' => (bool) config('payments.'.$provider.'.sandbox')];
            }
        }

        return $options;
    }
}
