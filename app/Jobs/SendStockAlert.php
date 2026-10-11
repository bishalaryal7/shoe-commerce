<?php

namespace App\Jobs;

use App\Services\StockAlerts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStockAlert implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public int $uniqueFor = 3600;

    public function __construct(public int $alertId) {}

    public function uniqueId(): string
    {
        return 'stock-alert-'.$this->alertId;
    }

    public function handle(StockAlerts $alerts): void
    {
        $alerts->send($this->alertId);
    }
}
