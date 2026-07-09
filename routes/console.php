<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mirror WhatsApp conversations to the staff Telegram group (near real-time).
// Requires the server scheduler cron: `* * * * * php artisan schedule:run`.
Schedule::command('telegram:monitor')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// --- Klinik FM Report module: PDPA retention purge (daily 03:00) ---
Schedule::command('pdpa:purge')->dailyAt('03:00');
