<?php

return [

    // Third-party services already registered by Laravel default...
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'resend' => ['key' => env('RESEND_API_KEY')],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Klinik Bustari admin gate + bot API
    'admin_password' => env('ADMIN_PASSWORD'),

    'bot' => [
        'url' => env('PYTHON_BOT_URL', 'http://localhost:5000'),
        'admin_key' => env('PYTHON_ADMIN_KEY', ''),
    ],

    'booking_url' => env('APPOINTMENT_BOOKING_URL', 'https://klinikbustari.com/appointment.html'),

    // Turso migration source (used once by artisan turso:pull)
    'turso' => [
        'url' => env('TURSO_URL', ''),
        'auth_token' => env('TURSO_AUTH_TOKEN', ''),
    ],
];
