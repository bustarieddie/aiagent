<?php

namespace App\Http\Controllers;

use App\Models\ConversationFlag;
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

        // Single-conversation view (bot returns {messages: [...]}) — pass through.
        if (is_array($payload) && isset($payload['messages'])) {
            return response()->json($payload);
        }

        $convos = $payload['conversations'] ?? (is_array($payload) ? $payload : []);
        $phones = collect($convos)->pluck('phone')->filter()->unique()->all();
        $flags = ConversationFlag::whereIn('phone', $phones)->get()->keyBy('phone');

        $enriched = collect($convos)->map(function ($row) use ($flags) {
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
                return response($resp->body(), 200)
                    ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
            }
            $botError = 'Bot menolak mesej (HTTP ' . $resp->status() . ').';
        } catch (\Throwable $e) {
            $botError = 'Bot tidak dapat dihubungi (' . $e->getMessage() . ').';
        }

        // 2) Fallback: send the WhatsApp message directly via WaSenderAPI.
        if ($wa->sendText($data['phone'], $data['message'])) {
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
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }
}
