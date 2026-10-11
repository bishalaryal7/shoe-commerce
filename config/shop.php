<?php

return [
    'currency' => env('SHOP_CURRENCY', 'NPR'),
    'shipping_amount' => (int) env('SHOP_SHIPPING_AMOUNT', 0),
    'tax_basis_points' => (int) env('SHOP_TAX_BASIS_POINTS', 0),
    'test_email' => env('SHOP_TEST_EMAIL'),
    'wholesale_minimum' => 12,
    'cash_on_delivery' => (bool) env('SHOP_CASH_ON_DELIVERY', true),
];
