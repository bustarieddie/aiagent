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

    // Comma-separated whitelist of phone numbers allowed to receive OTP + login.
    // Any Malaysian format works — 011-2233 4455, 60112233445, +60112233445.
    // Normalized to E.164 (+60...) before comparison.
    'allowed_phones' => env('ADMIN_ALLOWED_PHONES', ''),

    // WaSenderAPI — used to deliver login OTPs via WhatsApp.
    'wasender' => [
        'api_key' => env('WASENDER_API_KEY', ''),
    ],

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

    // Anthropic (used by LeadClassifier for AI auto-tagging).
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
        'model'   => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],
];
