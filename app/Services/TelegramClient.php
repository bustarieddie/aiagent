<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Minimal Telegram Bot API client.
 *
 * Endpoint: POST https://api.telegram.org/bot<TOKEN>/sendMessage
 *   Body: { chat_id, text, parse_mode }
 */
class TelegramClient {
    protected string $token;

    /** Human-readable reason the last sendMessage() failed (null on success). */
    public ?string $lastError = null;

    public function __construct() {
        $this->token = (string) config('services.telegram.bot_token', '');
    }

    public function isConfigured(): bool {
        return $this->token !== '';
    }

    /** Send an HTML message to a chat. Returns true on success; sets lastError on failure. */
    public function sendMessage(string $chatId, string $html): bool {
        $this->lastError = null;
        if (!$this->isConfigured()) {
            $this->lastError = 'TELEGRAM_BOT_TOKEN kosong (config tak terbaca?).';
            return false;
        }
        if ($chatId === '') {
            $this->lastError = 'TELEGRAM_MONITOR_CHAT_ID kosong.';
            return false;
        }
        try {
            $resp = Http::acceptJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $html,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            if ($resp->successful()) {
                return true;
            }
            // Telegram returns a helpful description, e.g. "Bad Request: chat not found".
            $this->lastError = 'HTTP ' . $resp->status() . ' — ' . ($resp->json('description') ?? $resp->body());
            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /** Escape text for Telegram HTML parse mode. */
    public static function escape(string $s): string {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $s);
    }
}
