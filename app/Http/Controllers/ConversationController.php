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
     * JSON API: send a manual staff reply. Works regardless of AI / takeover state.
     *
     * Delivery is resilient: we try the Python bot first (so it records the message
     * in its own DB and the thread shows it), but if the bot is unreachable or
     * rejects the request we fall back to sending the WhatsApp message directly via
     * WaSenderAPI — the same channel used for login OTPs — so the patient still
     * gets the reply even when the bot is down.
     */
    public function send(Request $request, BotApi $bot, WaSenderClient $wa) {
        $data = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        // 1) Primary: route through the bot so it persists the message + sends.
        try {
            $resp = $bot->post('/inbox/send', $data + ['source' => 'staff']);
            if ($resp->ok()) {
                $this->recordStaffMessage($data['phone'], $data['message']);
                return response($resp->body(), 200)
                    ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
            }
            $botError = 'Bot menolak mesej (HTTP ' . $resp->status() . ').';
        } catch (\Throwable $e) {
            $botError = 'Bot tidak dapat dihubungi (' . $e->getMessage() . ').';
        }

        // 2) Fallback: send the WhatsApp message directly via WaSenderAPI.
        if ($wa->sendText($data['phone'], $data['message'])) {
            $this->recordStaffMessage($data['phone'], $data['message']);
            return response()->json([
                'ok' => true,
                'via' => 'wasender',
                'note' => 'Dihantar terus via WaSenderAPI (bot tidak tersedia). Mesej mungkin tidak tersimpan dalam thread bot.',
                'message' => [
                    'direction' => 'out',
                    'source' => 'staff',
                    'body' => $data['message'],
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }

        // 3) Both paths failed.
        return response()->json([
            'ok' => false,
            'error' => 'Mesej gagal dihantar. Bot & WaSenderAPI kedua-duanya tidak tersedia.',
            'detail' => $botError,
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
