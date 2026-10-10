<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrderUpdate extends Mailable
{
    use Queueable,SerializesModels;

    public function __construct(public array $details, public string $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name').' — '.match ($this->event) {
            'paid' => 'Payment received', 'refunded' => 'Payment refunded','confirmed' => 'Order confirmed','fulfilled' => 'Your order is fulfilled','cancelled' => 'Order cancelled',default => 'Order received'
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-update', with: [
            'trackingUrl' => URL::temporarySignedRoute('orders.track', now()->addDays(30), ['reference' => $this->details['reference']]),
            'formatMoney' => fn ($amount) => $this->details['currency'].' '.number_format($amount / ($this->details['currency'] === 'JPY' ? 1 : 100), $this->details['currency'] === 'JPY' ? 0 : 2),
        ]);
    }
}
