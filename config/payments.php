<?php

return [
    'khalti' => [
        'enabled' => (bool) env('KHALTI_ENABLED', false), 'secret_key' => env('KHALTI_SECRET_KEY'),
        'sandbox' => (bool) env('KHALTI_SANDBOX', true),
        'sandbox_url' => 'https://dev.khalti.com/api/v2', 'production_url' => 'https://khalti.com/api/v2',
    ],
    'paypay' => [
        'enabled' => (bool) env('PAYPAY_ENABLED', false), 'api_key' => env('PAYPAY_API_KEY'), 'api_secret' => env('PAYPAY_API_SECRET'),
        'merchant_id' => env('PAYPAY_MERCHANT_ID'), 'sandbox' => (bool) env('PAYPAY_SANDBOX', true),
        'sandbox_url' => 'https://stg-api.sandbox.paypay.ne.jp', 'production_url' => 'https://api.paypay.ne.jp',
    ],
];
