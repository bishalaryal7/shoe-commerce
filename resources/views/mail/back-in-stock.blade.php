<x-mail::message>
# {{ __('Good news, your size is available') }}

{{ __(':product (:color), size :size is back in stock.', ['product' => $product, 'color' => __($color), 'size' => $size]) }}

<x-mail::button :url="$url">{{ __('Shop this shoe') }}</x-mail::button>

{{ __('Availability can change quickly, so visit the product page to order.') }}

{{ config('app.name') }}
</x-mail::message>
