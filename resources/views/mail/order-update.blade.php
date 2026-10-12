<x-mail::message>
# {{ __(match($event) { 'paid' => 'Payment received. Thank you!', 'refunded' => 'Your payment was refunded.', 'confirmed' => 'Your order is confirmed.', 'shipped' => 'Your order has shipped.', 'fulfilled' => 'Your order is delivered.', 'cancelled' => 'Your order has been cancelled.', 'payment_expired' => 'Your checkout expired.', default => 'We received your order.' }) }}
{{ __('Hello :name,', ['name' => $details['name']]) }}

{{ __('Order reference:') }} **{{ $details['reference'] }}**

<x-mail::table>
| {{ __('Shoe') }} | {{ __('EU size') }} | {{ __('Pairs') }} | {{ __('Amount') }} |
|:-----|:--------|------:|-------:|
@foreach($details['items'] as $item)
| {{ __($item['name']) }} | {{ $formatNumber($item['size']) }} | {{ $formatNumber($item['quantity']) }} | {{ $formatMoney($item['unit_price'] * $item['quantity']) }} |
@endforeach
</x-mail::table>

{{ __('Product subtotal:') }} {{ $formatMoney($details['subtotal']) }}
@if(($details['discount_amount'] ?? 0) > 0)
{{ __('Discount') }} ({{ $details['promotion_code'] }}): −{{ $formatMoney($details['discount_amount']) }}
@endif

{{ __('Shipping') }}: {{ $formatMoney($details['shipping_amount']) }}

{{ __('Tax') }}: {{ $formatMoney($details['tax_amount']) }}

**{{ __('Total') }}: {{ $formatMoney($details['total']) }}**

{{ __('Payment:') }} {{ __($details['payment_status']) }}.
@if($event === 'payment_expired')
{{ __('Payment was not completed before checkout expired. Your reserved sizes are available again. You can place a new order whenever you are ready.') }}
@endif
@if(($details['payment_method'] ?? '') === 'cod' && $details['payment_status'] === 'unpaid' && $details['status'] !== 'cancelled')
{{ __('Cash on delivery: pay :amount when your shoes arrive.', ['amount' => $formatMoney($details['total'])]) }}
@elseif($details['payment_status'] !== 'paid')
{{ __('No completed online payment is recorded for this order. Please check your order page before paying again.') }}
@endif
@if($event === 'shipped' && !empty($details['tracking_number']))
{{ __('Carrier') }}: {{ $details['tracking_carrier'] }}

{{ __('Tracking number') }}: **{{ $details['tracking_number'] }}**
@endif
@if($event === 'cancelled' && $details['payment_status'] === 'paid')
{{ __('A payment refund is handled separately by our team. Cancellation does not automatically refund a payment.') }}
@endif
<x-mail::button :url="$trackingUrl">{{ __('View your order') }}</x-mail::button>
{{ __('This private order link is valid for 30 days. Do not share it.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
