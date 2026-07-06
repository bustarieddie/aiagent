<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\MessageTemplate;
use App\Services\BotApi;
use Illuminate\Http\Request;

class BroadcastController extends Controller {
    public function index() {
        return view('admin.broadcast');
    }

    /** Preview audience — returns count + phones matching filter (consent-aware). */
    public function audience(Request $request, BotApi $bot) {
        $filter = $request->only(['tier', 'stage', 'service']);
        $filter['limit'] = 5000;
        $resp = $bot->get('/admin/api/leads', $filter);
        if (!$resp->ok()) return response()->json(['phones' => [], 'total' => 0]);

        $leads = $resp->json()['leads'] ?? [];

        // Consent filter — only include patients with consent_marketing=yes.
        // If bot doesn't yet expose the field we default to yes (visible) — flag will
        // be honored once patient records carry consent_marketing.
        $phones = collect($leads)
            ->filter(function ($l) {
                $consent = $l['consent_marketing'] ?? null;
                if ($consent === null) return true;                       // unknown → include
                return in_array(strtolower((string) $consent), ['yes', 'true', '1'], true);
            })
            ->filter(function ($l) {
                return empty($l['opted_out']) && empty($l['blocked']);
            })
            ->pluck('phone')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Frequency cap: no phone should receive >2 broadcasts in the last 7 days.
        $recent = BroadcastRecipient::whereIn('phone', $phones)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(7))
            ->select('phone')
            ->get()
            ->groupBy('phone')
            ->map(fn ($rows) => count($rows));

        $eligible = collect($phones)->reject(fn ($p) => ($recent[$p] ?? 0) >= 2)->values()->all();

        return response()->json([
            'phones' => $eligible,
            'total' => count($eligible),
            'skipped_by_freq_cap' => count($phones) - count($eligible),
        ]);
    }

    /** Create broadcast + queue recipients. Frontend then iterates send-one. */
    public function store(Request $request, BotApi $bot) {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'mode' => 'required|in:freeform,meta_template',
            'audience_filter' => 'array',
            'message_body' => 'required_if:mode,freeform|string',
            'meta_template_name' => 'required_if:mode,meta_template|nullable|string',
            'delay_ms' => 'integer|min:500|max:60000',
            'phones' => 'array',
            'phones.*' => 'string',
        ]);

        $phones = collect($data['phones'] ?? [])
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Frequency-cap enforcement (defence-in-depth — frontend already filters).
        if (!empty($phones)) {
            $recent = BroadcastRecipient::whereIn('phone', $phones)
                ->where('status', 'sent')
                ->where('sent_at', '>=', now()->subDays(7))
                ->select('phone')->get()->groupBy('phone')
                ->map(fn ($rows) => count($rows));
            $phones = array_values(array_filter($phones, fn ($p) => ($recent[$p] ?? 0) < 2));
        }

        $b = Broadcast::create([
            'name' => $data['name'],
            'mode' => $data['mode'],
            'audience_filter' => $data['audience_filter'] ?? [],
            'message_body' => $data['message_body'] ?? '',
            'meta_template_name' => $data['meta_template_name'] ?? null,
            'delay_ms' => $data['delay_ms'] ?? 1500,
            'status' => 'running',
            'total_count' => count($phones),
            'started_at' => now(),
        ]);

        foreach (array_chunk($phones, 500) as $chunk) {
            BroadcastRecipient::insert(
                array_map(fn ($p) => [
                    'broadcast_id' => $b->id,
                    'phone' => $p,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $chunk)
            );
        }

        return response()->json([
            'broadcast' => $b,
            'phones' => $phones,
        ]);
    }

    /** Send message to a single recipient of an in-flight broadcast. */
    public function sendOne(Request $request, Broadcast $broadcast, BotApi $bot) {
        $phone = (string) $request->input('phone');
        if (!$phone) return response()->json(['ok' => false, 'error' => 'phone required'], 422);

        $recipient = BroadcastRecipient::where('broadcast_id', $broadcast->id)
            ->where('phone', $phone)->first();
        if (!$recipient) return response()->json(['ok' => false, 'error' => 'not enrolled'], 404);
        if ($recipient->status !== 'pending') {
            return response()->json(['ok' => true, 'already' => $recipient->status]);
        }

        // Personalize {nama} — try leads list with q=phone
        $firstName = 'encik/puan';
        try {
            $lr = $bot->get('/admin/api/leads', ['q' => $phone, 'limit' => 1]);
            if ($lr->ok()) {
                $leads = $lr->json()['leads'] ?? [];
                $name = trim((string) ($leads[0]['name'] ?? ''));
                if ($name) $firstName = preg_split('/\s+/', $name)[0];
            }
        } catch (\Throwable) {}

        $body = str_replace(
            ['{nama}', '{name}', '[Nama]', '[Name]', '{Nama}'],
            $firstName,
            $broadcast->message_body,
        );

        $resp = $bot->post('/inbox/send', [
            'phone' => $phone, 'message' => $body, 'source' => 'staff',
        ]);

        if ($resp->successful()) {
            $recipient->update(['status' => 'sent', 'sent_at' => now()]);
            $broadcast->increment('sent_count');
            return response()->json(['ok' => true]);
        }

        $recipient->update(['status' => 'failed', 'failure_reason' => 'bot ' . $resp->status()]);
        $broadcast->increment('failed_count');
        return response()->json(['ok' => false, 'error' => 'bot failed']);
    }

    public function finalize(Broadcast $broadcast) {
        if ($broadcast->status === 'running') {
            $broadcast->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);
        }
        return response()->json(['broadcast' => $broadcast]);
    }

    public function cancel(Broadcast $broadcast) {
        $broadcast->update(['status' => 'cancelled', 'completed_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function history() {
        $rows = Broadcast::orderByDesc('id')->limit(50)->get();
        return response()->json(['broadcasts' => $rows]);
    }

    // ─── Templates (lokal) ──────────────────────────────────────────────────

    public function templates() {
        return response()->json([
            'templates' => MessageTemplate::orderBy('name')->get(),
        ]);
    }

    public function storeTemplate(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'nullable|string|max:30',
            'body' => 'required|string',
        ]);
        return response()->json(MessageTemplate::create($data));
    }

    public function updateTemplate(Request $request, MessageTemplate $template) {
        $template->update($request->validate([
            'name' => 'required|string|max:120',
            'category' => 'nullable|string|max:30',
            'body' => 'required|string',
        ]));
        return response()->json($template);
    }

    public function destroyTemplate(MessageTemplate $template) {
        $template->delete();
        return response()->json(['ok' => true]);
    }
}
