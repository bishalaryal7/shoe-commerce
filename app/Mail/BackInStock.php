<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStock extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $product, public string $size, public string $color, public string $url, string $locale = 'en')
    {
        $this->locale(in_array($locale, ['en', 'ne'], true) ? $locale : 'en');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('A size you wanted is back at EVE Shoes'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.back-in-stock');
    }
}
