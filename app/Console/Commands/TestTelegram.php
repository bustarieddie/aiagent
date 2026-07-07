<?php

namespace App\Console\Commands;

use App\Services\TelegramClient;
use Illuminate\Console\Command;

/**
 * Diagnostic: send a test message to the monitor Telegram chat and report the
 * exact outcome (config presence + Telegram API error if any).
 */
class TestTelegram extends Command {
    protected $signature = 'telegram:test';
    protected $description = 'Send a test message to the Telegram monitor chat and report the result.';

    public function handle(TelegramClient $tg): int {
        $token = (string) config('services.telegram.bot_token', '');
        $chat = (string) config('services.telegram.monitor_chat_id', '');

        $this->line('TELEGRAM_BOT_TOKEN     : ' . ($token !== '' ? 'set (' . strlen($token) . ' chars, ends …' . substr($token, -4) . ')' : 'EMPTY ❌'));
        $this->line('TELEGRAM_MONITOR_CHAT_ID: ' . ($chat !== '' ? "[{$chat}]" : 'EMPTY ❌'));

        if ($chat !== '' && $chat[0] !== '-') {
            $this->warn('⚠️  Chat ID group biasanya negatif (mula dengan -100...). ID positif = chat personal.');
        }

        $this->line('Sending test message…');
        $ok = $tg->sendMessage($chat, '✅ <b>Test</b> dari Klinik Bustari monitor. Kalau nampak ni, setup Telegram OK!');

        if ($ok) {
            $this->info('✅ Berjaya dihantar — semak group Telegram anda sekarang.');
            return self::SUCCESS;
        }

        $this->error('❌ Gagal: ' . $tg->lastError);
        return self::FAILURE;
    }
}
