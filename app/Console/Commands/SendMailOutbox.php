<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderEmail;
use App\Models\OrderEmail;
use Illuminate\Console\Command;

class SendMailOutbox extends Command
{
    protected $signature = 'shop:mail-outbox';

    protected $description = 'Queue pending order emails for retryable delivery';

    public function handle(): int
    {
        OrderEmail::whereNull('sent_at')->oldest()->limit(100)->get()->each(fn ($email) => SendOrderEmail::dispatch($email->id));
        $this->info('Pending order email jobs queued.');

        return self::SUCCESS;
    }
}
