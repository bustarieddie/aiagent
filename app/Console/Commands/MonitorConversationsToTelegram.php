<?php

namespace App\Console\Commands;

use App\Models\AutomationRule;
use App\Models\TelegramMonitorState;
use App\Services\BotApi;
use App\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Mirror new WhatsApp conversation messages to a staff Telegram group.
 *
 * Runs on a schedule (every minute). Polls the bot's conversation API, and for
 * each conversation forwards messages that arrived since the last run. The first
 * time a conversation is seen it is "baselined" (recorded but not forwarded) so
 * enabling the monitor doesn't dump the entire history of 200 chats at once.
 */
class MonitorConversationsToTelegram extends Command {
    protected $signature = 'telegram:monitor {--limit=200 : Max conversations to scan} {--max-sends=25 : Max Telegram messages per run}';
    protected $description = 'Forward new WhatsApp conversation messages to the staff Telegram group.';

    private const MAX_LINES_PER_CONVO = 15;
    private const MAX_BODY_LEN = 300;

    public function handle(BotApi $bot, TelegramClient $tg): int {
        $chatId = (string) config('services.telegram.monitor_chat_id', '');

        if (!$tg->isConfigured() || $chatId === '') {
            $this->warn('Telegram not configured (TELEGRAM_BOT_TOKEN / TELEGRAM_MONITOR_CHAT_ID). Skipping.');
            return self::SUCCESS;
        }

        // Respect the ON/OFF toggle from the Automation panel, if the rule exists.
        $rule = Schema::hasTable('automation_rules')
            ? AutomationRule::where('slug', 'telegram_monitor')->first()
            : null;
        if ($rule && !$rule->is_active) {
            $this->line('telegram_monitor automation is OFF. Skipping.');
            return self::SUCCESS;
        }
        $prefs = $rule ? $rule->settingsWithDefaults() : [];

        // 1) Fetch the conversation list from the bot.
        try {
            $resp = $bot->get('/admin/api/conversations', ['limit' => (int) $this->option('limit')]);
        } catch (\Throwable $e) {
            $this->error('Bot unreachable: ' . $e->getMessage());
            return self::FAILURE;
        }
        if (!$resp->ok()) {
            $this->error('Bot returned HTTP ' . $resp->status());
            return self::FAILURE;
        }
        $payload = $resp->json();
        $convos = $payload['conversations'] ?? (is_array($payload) ? $payload : []);

        $sends = 0;
        $maxSends = (int) $this->option('max-sends');

        foreach ($convos as $c) {
            if ($sends >= $maxSends) {
                $this->warn("Hit max-sends ({$maxSends}); remaining conversations will be picked up next run.");
                break;
            }
            $phone = $c['phone'] ?? null;
            if (!$phone) {
                continue;
            }

            // 2) Fetch this conversation's recent messages.
            try {
                $mResp = $bot->get('/admin/api/conversations', ['phone' => $phone, 'limit' => 50]);
            } catch (\Throwable) {
                continue;
            }
            if (!$mResp->ok()) {
                continue;
            }
            $messages = $mResp->json()['messages'] ?? [];
            if (empty($messages)) {
                continue;
            }

            $keys = array_map(fn ($m) => $this->keyOf($m), $messages);
            $state = TelegramMonitorState::find($phone);

            // 3) First sight → baseline silently (no history dump).
            if (!$state) {
                TelegramMonitorState::create(['phone' => $phone, 'last_key' => end($keys)]);
                continue;
            }

            // 4) Find messages newer than the cursor.
            $idx = array_search($state->last_key, $keys, true);
            if ($idx !== false) {
                $newMsgs = array_slice($messages, $idx + 1);
            } else {
                // Cursor rolled out of the window — fall back to timestamp comparison.
                $cursorTs = explode('|', (string) $state->last_key)[0];
                $newMsgs = array_values(array_filter($messages, fn ($m) => $this->tsOf($m) > $cursorTs));
            }

            if (empty($newMsgs)) {
                continue;
            }

            // 5) Apply the include/exclude settings from the Automation panel.
            $newMsgs = array_values(array_filter($newMsgs, fn ($m) => $this->shouldInclude($m, $prefs)));
            if (empty($newMsgs)) {
                $state->update(['last_key' => end($keys)]);   // advance so we don't re-scan
                continue;
            }

            // 6) Forward as one grouped Telegram message per conversation.
            $text = $this->render($c, $phone, $newMsgs);
            if ($tg->sendMessage($chatId, $text)) {
                $state->update(['last_key' => end($keys)]);
                $sends++;
            } else {
                $this->error("Telegram send failed for {$phone}: " . $tg->lastError);
            }
        }

        $this->info("Forwarded {$sends} conversation update(s) to Telegram.");
        return self::SUCCESS;
    }

    private function render(array $convo, string $phone, array $msgs): string {
        $name = trim((string) ($convo['name'] ?? '')) ?: $phone;
        $header = '🟢 <b>' . TelegramClient::escape($name) . '</b> (<code>' . TelegramClient::escape($phone) . '</code>)';

        $lines = [];
        $shown = array_slice($msgs, -self::MAX_LINES_PER_CONVO);
        foreach ($shown as $m) {
            $dir = $m['direction'] ?? 'in';
            $src = $m['source'] ?? '';
            $who = $dir === 'in' ? '⬇️ patient' : ($src === 'staff' ? '⬆️ staf' : '⬆️ bot');
            $body = trim((string) ($m['body'] ?? ''));
            if ($body === '' && !empty($m['media_url'])) {
                $body = '[media]';
            }
            if (mb_strlen($body) > self::MAX_BODY_LEN) {
                $body = mb_substr($body, 0, self::MAX_BODY_LEN) . '…';
            }
            $lines[] = $who . ': ' . TelegramClient::escape($body);
        }
        if (count($msgs) > self::MAX_LINES_PER_CONVO) {
            array_unshift($lines, '… (' . (count($msgs) - self::MAX_LINES_PER_CONVO) . ' mesej terdahulu)');
        }

        return $header . "\n" . implode("\n", $lines);
    }

    private function shouldInclude(array $m, array $prefs): bool {
        $dir = $m['direction'] ?? 'in';
        $src = $m['source'] ?? '';
        if ($dir === 'in') {
            return $prefs['include_incoming'] ?? true;
        }
        if ($src === 'staff') {
            return $prefs['include_staff'] ?? true;
        }
        return $prefs['include_bot'] ?? true;
    }

    private function tsOf(array $m): string {
        foreach (['timestamp', 'ts', 'created_at', 'received_at', 'sent_at'] as $k) {
            if (!empty($m[$k])) {
                return (string) $m[$k];
            }
        }
        return '';
    }

    private function keyOf(array $m): string {
        return $this->tsOf($m) . '|' . substr(md5((string) ($m['body'] ?? '')), 0, 8);
    }
}
