<?php

namespace App\Http\Controllers;

use App\Models\ConversationFlag;
use App\Models\LeadOverride;
use App\Models\StaffMessage;
use App\Services\BotApi;
use App\Services\WaSenderClient;
use Illuminate\Http\Request;

class ConversationController extends Controller {
    public function index() {
        return view('admin.conversations');
    }

    /** JSON API: list conversations (merged with ConversationFlag overlay). */
    public function list(Request $request, BotApi $bot) {
        $resp = $bot->get('/admin/api/conversations', $request->query());
        if (!$resp->ok()) {
            return response($resp->body(), $resp->status())
                ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
        }
        $payload = $resp->json();

        // Single-conversation view (bot returns {messages: [...]}) — merge in any
        // locally-stored staff replies so they survive reloads even when the bot
        // never recorded them (WaSenderAPI fallback).
        if (is_array($payload) && isset($payload['messages'])) {
            $phone = $request->query('phone');
            if ($phone) {
                $payload['messages'] = $this->mergeStaffMessages($phone, $payload['messages']);
            }
            return response()->json($payload);
        }

        $convos = $payload['conversations'] ?? (is_array($payload) ? $payload : []);
        $phones = collect($convos)->pluck('phone')->filter()->unique()->all();
        $flags = ConversationFlag::whereIn('phone', $phones)->get()->keyBy('phone');
        // Same crm_stage override as the Leads page, so both views stay in sync.
        $overrides = LeadOverride::whereIn('phone', $phones)->get()->keyBy('phone');

        $enriched = collect($convos)->map(function ($row) use ($flags, $overrides) {
            $flag = $flags[$row['phone']] ?? null;
            $row['flag'] = $flag ? [
                'aiEnabled' => (bool) $flag->ai_enabled,
                'humanTakeover' => (bool) $flag->human_takeover,
                'status' => $flag->status,
                'pinned' => (bool) $flag->pinned,
                'staffTags' => $flag->staff_tags,
            ] : [
                'aiEnabled' => true, 'humanTakeover' => false,
                'status' => 'open', 'pinned' => false, 'staffTags' => null,
            ];
            $o = $overrides[$row['phone']] ?? null;
            if ($o && $o->crm_stage) {
                $row['crm_stage'] = $o->crm_stage;
            }
            return $row;
        })->values();

        if (isset($payload['conversations'])) {
            $payload['conversations'] = $enriched;
            return response()->json($payload);
        }
        return response()->json($enriched);
    }

    /**
     * JSON API: send a manual staff reply. Always routes through the Python bot
     * so the patient sees the message from the same Meta number the AI uses —
     * never from the WaSenderAPI number (that channel is OTP-only).
     *
     * If the bot rejects the first attempt (common when a conversation hasn't
     * been flagged as human-takeover yet), we auto-toggle takeover on the bot
     * side and retry once. Only after both attempts fail do we surface the
     * error to the UI so the staff can decide what to do (usually just retry
     * or check the bot logs).
     */
    public function send(Request $request, BotApi $bot) {
        $data = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);
        $phone = $data['phone'];

        $sendViaBot = function () use ($bot, $data) {
            return $bot->post('/inbox/send', $data + ['source' => 'staff']);
        };

        // First attempt.
        try {
            $resp = $sendViaBot();
            if ($resp->ok()) {
                $this->recordStaffMessage($phone, $data['message']);
                return response($resp->body(), 200)
                    ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
            }
            $firstStatus = $resp->status();
            $firstBody = $resp->body();
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'Bot tidak dapat dihubungi. Periksa bot service.',
                'detail' => $e->getMessage(),
            ], 502);
        }

        // Retry once after auto-enabling human takeover — bot often refuses
        // staff replies on conversations still under AI control.
        try {
            $bot->post('/inbox/takeover/' . urlencode($phone), ['takeover' => true]);
            $resp = $sendViaBot();
            if ($resp->ok()) {
                ConversationFlag::updateOrCreate(
                    ['phone' => $phone],
                    ['human_takeover' => true, 'ai_enabled' => false],
                );
                $this->recordStaffMessage($phone, $data['message']);
                return response($resp->body(), 200)
                    ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
            }
        } catch (\Throwable) {
            // fall through to error response
        }

        return response()->json([
            'ok' => false,
            'error' => "Bot menolak mesej (HTTP {$firstStatus}). Cuba enable Human Takeover manually + retry, atau check bot log.",
            'detail' => is_string($firstBody) ? substr($firstBody, 0, 300) : null,
        ], 502);
    }

    /** DELETE — nuke entire conversation (bot wipes DB + files, portal clears flag). */
    public function destroy(string $phone, BotApi $bot) {
        $resp = $bot->delete('/admin/api/conversations/' . urlencode($phone));
        ConversationFlag::where('phone', $phone)->delete();
        StaffMessage::where('phone', $phone)->delete();
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }

    /** Persist a staff reply locally (best-effort). */
    private function recordStaffMessage(string $phone, string $body): void {
        try {
            StaffMessage::create(['phone' => $phone, 'body' => $body, 'sent_at' => now()]);
        } catch (\Throwable) {
            // best-effort — never block the send on a local-store failure
        }
    }

    /**
     * Merge locally-stored staff replies into the bot's message list, in
     * chronological order, skipping any the bot already has (dedup by body +
     * time window) so nothing shows twice.
     */
    private function mergeStaffMessages(string $phone, array $botMsgs): array {
        $local = StaffMessage::where('phone', $phone)->orderBy('sent_at')->get();
        if ($local->isEmpty()) {
            return $botMsgs;
        }

        // Tag bot messages with a sortable epoch, carrying the last known time
        // forward for any message we can't parse so original order is preserved.
        $combined = [];
        $carry = 0;
        foreach ($botMsgs as $i => $bm) {
            $e = $this->msgEpoch($bm);
            if ($e === null) {
                $e = $carry;
            } else {
                $carry = $e;
            }
            $combined[] = ['m' => $bm, 'e' => $e, 'i' => $i];
        }

        $base = count($botMsgs);
        foreach ($local as $k => $sm) {
            $body = trim((string) $sm->body);
            $se = $sm->sent_at ? $sm->sent_at->getTimestamp() : 0;

            // Skip if the bot thread already contains this staff message.
            $dup = false;
            foreach ($botMsgs as $bm) {
                if (($bm['direction'] ?? '') === 'out' && trim((string) ($bm['body'] ?? '')) === $body) {
                    $be = $this->msgEpoch($bm);
                    if ($be !== null && abs($be - $se) <= 600) {
                        $dup = true;
                        break;
                    }
                }
            }
            if ($dup) {
                continue;
            }

            $combined[] = [
                'm' => [
                    'direction' => 'out',
                    'source' => 'staff',
                    'body' => $sm->body,
                    'timestamp' => optional($sm->sent_at)->format('Y-m-d H:i:s'),
                ],
                'e' => $se,
                'i' => $base + $k,
            ];
        }

        usort($combined, fn ($a, $b) => [$a['e'], $a['i']] <=> [$b['e'], $b['i']]);
        return array_map(fn ($x) => $x['m'], $combined);
    }

    private function msgEpoch(array $m): ?int {
        foreach (['timestamp', 'ts', 'created_at', 'received_at', 'sent_at'] as $k) {
            if (!empty($m[$k])) {
                $t = strtotime((string) $m[$k]);
                if ($t !== false) {
                    return $t;
                }
            }
        }
        return null;
    }
}
