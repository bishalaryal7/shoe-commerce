<?php

namespace App\Jobs;

use App\Mail\OrderUpdate;
use App\Models\OrderEmail;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendOrderEmail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return (string) $this->emailId;
    }

    public function __construct(public int $emailId) {}

    public function backoff(): array
    {
        return [15, 60, 180, 600];
    }

    public function handle(): void
    {
        Cache::lock('order-email-'.$this->emailId, 90)->block(5, function () {
            $email = OrderEmail::find($this->emailId);
            if (! $email || $email->sent_at) {
                return;
            }
            Mail::to($email->payload['email'])->send(new OrderUpdate($email->payload, $email->event));
            $email->update(['sent_at' => now()]);
        });
    }
}
