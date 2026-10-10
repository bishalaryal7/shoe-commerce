<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'shop:mail-test';

    protected $description = 'Send one test message to SHOP_TEST_EMAIL using the configured mail transport';

    public function handle(): int
    {
        $recipient = config('shop.test_email');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Set SHOP_TEST_EMAIL in .env first.');

            return self::FAILURE;
        }
        try {
            Mail::raw('Your '.config('app.name').' email connection is working. This is a development test, not an order.', fn ($mail) => $mail->to($recipient)->subject(config('app.name').' email test'));
            $this->info('Message accepted by '.config('mail.default').'. For Mailpit, view the local inbox; this does not deliver to Gmail.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Mail transport could not accept the message. Check the SMTP server and local Docker service.');

            return self::FAILURE;
        }
    }
}
