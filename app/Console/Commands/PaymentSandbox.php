<?php

namespace App\Console\Commands;

use App\Models\PaymentAttempt;
use App\Payments\Gateways;
use App\Services\OrderPayments;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PaymentSandbox extends Command
{
    protected $signature = 'shop:payment-sandbox {provider : khalti or paypay} {--lookup= : Reference from a previous sandbox attempt}';

    protected $description = 'Create or verify an isolated sandbox payment without a real customer order';

    public function handle(Gateways $gateways, OrderPayments $payments): int
    {
        $provider = $this->argument('provider');
        if (! in_array($provider, ['khalti', 'paypay'], true) || ! config('payments.'.$provider.'.sandbox')) {
            $this->error('This command only supports enabled sandbox providers.');

            return self::FAILURE;
        }
        try {
            if ($reference = $this->option('lookup')) {
                $attempt = PaymentAttempt::where('reference', $reference)->where('sandbox', true)->whereNull('order_id')->where('provider', $provider)->firstOrFail();
                $this->info('Verified sandbox status: '.$payments->verify($attempt));

                return self::SUCCESS;
            }
            $gateway = $gateways->get($provider);
            if (! $gateway->available()) {
                $this->error('Configure the sandbox merchant keys in .env first.');

                return self::FAILURE;
            }
            $attempt = PaymentAttempt::create(['reference' => (string) Str::uuid(), 'provider' => $provider, 'currency' => $gateway->currency(), 'amount' => $provider === 'khalti' ? 1000 : 100, 'sandbox' => true]);
            try {
                $attempt->update([...$gateway->initiate($attempt), 'status' => 'initiated']);
            } catch (\Throwable $e) {
                $attempt->update(['status' => 'review']);
                throw $e;
            }
            $this->info('Sandbox-only reference: '.$attempt->reference);
            $this->line('Open this sandbox checkout: '.$attempt->payment_url);
            $this->line('Verify with: php artisan shop:payment-sandbox '.$provider.' --lookup='.$attempt->reference);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Sandbox operation failed. Check merchant configuration and provider availability; no real order was created.');

            return self::FAILURE;
        }
    }
}
