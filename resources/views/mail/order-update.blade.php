<x-mail::message>
# {{ match($event) { 'paid' => 'Payment received. Thank you!', 'refunded' => 'Your payment was refunded.', 'confirmed' => 'Your order is confirmed.', 'fulfilled' => 'Your order is fulfilled.', 'cancelled' => 'Your order has been cancelled.', default => 'We received your order.' } }}
Hello {{ $details['name'] }},

Order reference: **{{ $details['reference'] }}**

<x-mail::table>
| Shoe | EU size | Pairs | Amount |
|:-----|:--------|------:|-------:|
@foreach($details['items'] as $item)
| {{ $item['name'] }} | {{ $item['size'] }} | {{ $item['quantity'] }} | {{ $formatMoney($item['unit_price'] * $item['quantity']) }} |
@endforeach
</x-mail::table>

Product subtotal: {{ $formatMoney($details['subtotal']) }}

Shipping: {{ $formatMoney($details['shipping_amount']) }}

Tax: {{ $formatMoney($details['tax_amount']) }}

**Total: {{ $formatMoney($details['total']) }}**

Payment: {{ str_replace('_', ' ', $details['payment_status']) }}.
@if($details['payment_status'] !== 'paid')
No completed online payment is recorded for this order. Please check your order page before paying again.
@endif
@if($event === 'cancelled' && $details['payment_status'] === 'paid')
A payment refund is handled separately by our team. Cancellation does not automatically refund a payment.
@endif
<x-mail::button :url="$trackingUrl">View your order</x-mail::button>
This private order link is valid for 30 days. Do not share it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
