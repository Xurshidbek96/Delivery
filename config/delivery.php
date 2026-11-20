<?php

/*
|--------------------------------------------------------------------------
| Delivery — biznes sozlamalari
|--------------------------------------------------------------------------
| Bu yerdagi qiymatlar — standart (default). Admin panel "Sozlamalar"
| sahifasida o'zgartirilgan qiymatlar `settings` jadvalida saqlanadi va
| SettingsRepository orqali ustun turadi. Kodda env() emas, config() ishlating.
*/

return [

    'otp' => [
        // Test rejimi: qat'iy kod. Bo'sh bo'lsa — tasodifiy 4 xonali kod SMS orqali yuboriladi.
        'fixed_code' => env('OTP_FIXED_CODE') ?: null,
        'length' => 4,
        'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 300),
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    'push' => [
        'driver' => env('PUSH_DRIVER', 'log'),
    ],

    // Admin paneldan o'zgartiriladigan sozlamalar uchun standart qiymatlar
    'defaults' => [
        'delivery_fee' => 15000,
        'service_fee' => 0,
        'courier_max_active_orders' => 3,
        'accepting_orders' => true,
        'support_phone' => '+998 90 000 00 00',
        'max_items_per_order' => 50,
    ],

    'receipts_disk' => 'public',
];
