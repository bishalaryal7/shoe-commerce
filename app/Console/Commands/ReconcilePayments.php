<?php

namespace App\Console\Commands;

use App\Models\PaymentAttempt;
use App\Services\OrderPayments;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'shop:reconcile-payments {--limit=100}';

    protected $description = 'Verify unsettled provider payments without trusting browser callbacks';

    public function handle(OrderPayments $payments): int
    {
        $attempts = PaymentAttempt::whereIn('status', ['initiated', 'pending', 'review'])->whereNotNull('provider_id')->oldest('updated_at')->limit(min(max((int) $this->option('limit'), 1), 1000))->get();
        $failed = false;
        foreach ($attempts as $attempt) {
            try {
                $this->line($attempt->reference.': '.$payments->verify($attempt));
            } catch (\Throwable $e) {
                $failed = true;
                $this->warn($attempt->reference.': provider unavailable; status unchanged.');
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
