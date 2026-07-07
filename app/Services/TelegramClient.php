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

    public function __construct() {
        $this->token = (string) config('services.telegram.bot_token', '');
    }

    public function isConfigured(): bool {
        return $this->token !== '';
    }

    /** Send an HTML message to a chat. Returns true on success. */
    public function sendMessage(string $chatId, string $html): bool {
        if (!$this->isConfigured() || $chatId === '') {
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
            return $resp->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Escape text for Telegram HTML parse mode. */
    public static function escape(string $s): string {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $s);
    }
}
