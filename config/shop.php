<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Profile
    |--------------------------------------------------------------------------
    */

    'store' => [
        'name' => env('APP_NAME', 'MyShop'),
        'email' => env('SHOP_EMAIL', 'halo@myshop.test'),
        'phone' => env('SHOP_PHONE', '0812-0000-0000'),
        'address' => env('SHOP_ADDRESS', 'Jl. Contoh No. 1, Bandung, Jawa Barat'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    |
    | The simulator lets a customer mark their own order paid, so it is off in
    | production by default. With it off, customers transfer to the bank
    | account below and an admin confirms the payment on the order page.
    |
    */

    'payment_simulator' => (bool) env('SHOP_PAYMENT_SIMULATOR', env('APP_ENV', 'production') !== 'production'),

    'bank_account' => env('SHOP_BANK_ACCOUNT'),

    /*
    |--------------------------------------------------------------------------
    | Operational Settings
    |--------------------------------------------------------------------------
    */

    'payment_due_hours' => 24,

    // Email the customer this many hours before an unpaid order expires.
    'payment_reminder_hours' => 2,

    // Delivered orders the customer has not confirmed are completed after this many days.
    'auto_complete_days' => 3,

    'max_quantity_per_item' => 10,

    'low_stock_threshold' => 5,

    'products_per_page' => 12,

    /*
    |--------------------------------------------------------------------------
    | Shipping (demo rates)
    |--------------------------------------------------------------------------
    |
    | Cost = ceil(total weight in kg, min 1 kg) x rate_per_kg. The destination
    | does not affect the rate in phase 1.
    |
    */

    'shipping' => [
        'origin' => 'Bandung, Jawa Barat',
        'courier' => 'Kurir Demo',
        'services' => [
            'reguler' => ['name' => 'Reguler', 'rate_per_kg' => 10000, 'eta' => '2–4 hari'],
            'kilat' => ['name' => 'Kilat', 'rate_per_kg' => 18000, 'eta' => '1–2 hari'],
        ],
    ],

];
