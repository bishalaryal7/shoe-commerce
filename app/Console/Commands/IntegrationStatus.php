<?php

namespace App\Console\Commands;

use App\Payments\Gateways;
use App\Services\Auth\SignInOptions;
use Illuminate\Console\Command;

class IntegrationStatus extends Command
{
    protected $signature = 'shop:integrations';

    protected $description = 'Show payment and sign-in configuration status without exposing secrets';

    public function handle(SignInOptions $signIn, Gateways $gateways): int
    {
        $rows = [];
        foreach ($signIn->all() as $provider => $available) {
            $rows[] = [ucfirst($provider), $available ? 'Configured' : 'Needs configuration'];
        }
        foreach (['khalti', 'paypay'] as $provider) {
            $rows[] = [ucfirst($provider), $gateways->get($provider)->available() ? (config("payments.$provider.sandbox") ? 'Configured (sandbox)' : 'Configured (live)') : 'Needs configuration'];
        }
        $rows[] = ['Cash on delivery', config('shop.cash_on_delivery') && config('shop.currency') === 'NPR' ? 'Enabled for NPR' : 'Disabled'];
        $this->table(['Integration', 'Configuration'], $rows);
        $this->line('Callback base: '.config('app.url'));
        $this->line('Configured does not mean provider-verified. No network requests were sent.');
        $this->line('Setup: docs/SIGN_IN_AND_PAYMENTS.md');

        return self::SUCCESS;
    }
}
