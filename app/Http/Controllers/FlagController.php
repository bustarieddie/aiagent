<?php

namespace App\Http\Controllers;

use App\Models\ConversationFlag;
use App\Services\BotApi;
use Illuminate\Http\Request;

class FlagController extends Controller {
    public function show(string $phone) {
        $flag = ConversationFlag::find($phone) ?? new ConversationFlag();
        return response()->json([
            'phone' => $phone,
            'aiEnabled' => (bool) ($flag->ai_enabled ?? true),
            'humanTakeover' => (bool) ($flag->human_takeover ?? false),
            'status' => $flag->status ?? 'open',
            'pinned' => (bool) ($flag->pinned ?? false),
            'staffTags' => $flag->staff_tags,
            'lastNoteByStaff' => $flag->last_note_by_staff,
        ]);
    }

    public function update(Request $request, string $phone, BotApi $bot) {
        $body = $request->all();
        $patch = collect($body)->only([
            'aiEnabled', 'humanTakeover', 'status', 'pinned',
            'staffTags', 'lastNoteByStaff', 'updatedBy',
        ])->all();

        $translated = [
            'ai_enabled' => $patch['aiEnabled'] ?? null,
            'human_takeover' => $patch['humanTakeover'] ?? null,
            'status' => $patch['status'] ?? null,
            'pinned' => $patch['pinned'] ?? null,
            'staff_tags' => array_key_exists('staffTags', $patch) ? $patch['staffTags'] : null,
            'last_note_by_staff' => $patch['lastNoteByStaff'] ?? null,
            'updated_by' => $patch['updatedBy'] ?? null,
        ];
        // Drop null values that were absent (keep nulls that are explicit)
        foreach ($translated as $k => $v) {
            if ($v === null && !array_key_exists($this->camel($k), $patch)) unset($translated[$k]);
        }

        $flag = ConversationFlag::updateOrCreate(
            ['phone' => $phone],
            array_filter($translated, fn ($v) => $v !== null) + [
                'ai_enabled' => $translated['ai_enabled'] ?? true,
                'human_takeover' => $translated['human_takeover'] ?? false,
                'status' => $translated['status'] ?? 'open',
                'pinned' => $translated['pinned'] ?? false,
            ],
        );

        // Sync takeover to bot's in-memory dict for immediate effect
        if (array_key_exists('humanTakeover', $patch)) {
            try {
                $bot->post('/inbox/takeover/' . urlencode($phone), [
                    'takeover' => (bool) $patch['humanTakeover'],
                ]);
            } catch (\Throwable) {
                // best-effort
            }
        }

        return response()->json($flag);
    }

    private function camel(string $snake): string {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snake))));
    }
}
