<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Profile
    |--------------------------------------------------------------------------
    */

    'store' => [
        'name' => env('APP_NAME', 'MyShop'),
        'email' => 'halo@myshop.test',
        'phone' => '0812-0000-0000',
        'address' => 'Jl. Contoh No. 1, Bandung, Jawa Barat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational Settings
    |--------------------------------------------------------------------------
    */

    'payment_due_hours' => 24,

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
