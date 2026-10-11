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

    public function __construct(public array $details, public string $event)
    {
        $this->locale(in_array($details['locale'] ?? 'en', ['en', 'ne'], true) ? ($details['locale'] ?? 'en') : 'en');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('app.name').' — '.__(match ($this->event) {
            'paid' => 'Payment received', 'refunded' => 'Payment refunded','confirmed' => 'Order confirmed','shipped' => 'Your order has shipped','fulfilled' => 'Your order is delivered','cancelled' => 'Order cancelled',default => 'Order received'
        }));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-update', with: [
            'trackingUrl' => URL::temporarySignedRoute('orders.track', now()->addDays(30), ['reference' => $this->details['reference']]),
            'formatNumber' => fn ($number) => $this->formatNumber((string) $number),
            'formatMoney' => fn ($amount) => __($this->details['currency']).' '.$this->formatNumber(number_format($amount / ($this->details['currency'] === 'JPY' ? 1 : 100), $this->details['currency'] === 'JPY' ? 0 : 2)),
        ]);
    }

    private function formatNumber(string $number): string
    {
        return app()->getLocale() === 'ne' ? strtr($number, ['0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४', '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९']) : $number;
    }
}
