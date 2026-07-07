<?php

namespace App\Http\Controllers;

use App\Models\ConversationFlag;
use App\Services\BotApi;
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

    /** JSON API: send a manual staff reply (works regardless of AI / takeover state). */
    public function send(Request $request, BotApi $bot) {
        $data = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $resp = $bot->post('/inbox/send', $data + ['source' => 'staff']);
        } catch (\Throwable $e) {
            // Bot unreachable / timeout — return clean JSON so the UI can surface it
            // instead of a 500 HTML page that the frontend can't parse.
            return response()->json([
                'ok' => false,
                'error' => 'Bot tidak dapat dihubungi. Mesej tidak dihantar.',
                'detail' => $e->getMessage(),
            ], 502);
        }

        if (!$resp->ok()) {
            return response()->json([
                'ok' => false,
                'error' => 'Bot menolak mesej (HTTP ' . $resp->status() . ').',
                'detail' => $resp->body(),
            ], $resp->status());
        }

        return response($resp->body(), $resp->status())
            ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
    }

    /** DELETE — nuke entire conversation (bot wipes DB + files, portal clears flag). */
    public function destroy(string $phone, BotApi $bot) {
        $resp = $bot->delete('/admin/api/conversations/' . urlencode($phone));
        ConversationFlag::where('phone', $phone)->delete();
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }
}
