<?php

namespace App\Console\Commands;

use App\Services\ExpireUnpaidOrders as ExpireUnpaidOrdersService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'shop:expire-unpaid-orders {--limit=100}';

    protected $description = 'Release stock only after the provider confirms an expired online checkout';

    public function handle(ExpireUnpaidOrdersService $expiry): int
    {
        $result = $expiry->run((int) $this->option('limit'));

        $this->info("Checked {$result['checked']} expired payment session(s); cancelled {$result['expired']} order(s), kept {$result['still_pending']} provider-pending, {$result['unavailable']} provider check(s) unavailable.");

        return $result['unavailable'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
