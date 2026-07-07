<?php

namespace App\Http\Controllers;

use App\Models\LeadAssignment;
use App\Models\StaffMember;
use App\Services\BotApi;
use App\Services\LeadClassifier;
use App\Services\LeadDistributor;
use Illuminate\Http\Request;

class LeadController extends Controller {
    public function index() {
        return view('admin.leads');
    }

    public function list(Request $request, BotApi $bot) {
        $resp = $bot->get('/admin/api/leads', $request->query());
        if (!$resp->ok()) {
            return response($resp->body(), $resp->status())
                ->header('Content-Type', 'application/json');
        }
        $payload = $resp->json();
        $leads = $payload['leads'] ?? [];
        $phones = collect($leads)->pluck('phone')->filter()->all();

        // Assignment overlay
        $assignments = LeadAssignment::with('staff:id,name')
            ->whereIn('phone', $phones)
            ->get()->keyBy('phone');

        // Optional filter: assigned_to (staff id, "0" = unassigned)
        $filterAssigned = $request->query('assigned_to');
        if ($filterAssigned !== null && $filterAssigned !== '') {
            $wantUnassigned = (string) $filterAssigned === '0';
            $leads = array_values(array_filter($leads, function ($l) use ($assignments, $filterAssigned, $wantUnassigned) {
                $a = $assignments[$l['phone']] ?? null;
                if ($wantUnassigned) return !$a || !$a->staff_member_id;
                return $a && (string) $a->staff_member_id === (string) $filterAssigned;
            }));
        }

        $enriched = collect($leads)->map(function ($l) use ($assignments) {
            $a = $assignments[$l['phone']] ?? null;
            $l['assigned_to'] = $a && $a->staff ? [
                'id' => $a->staff->id,
                'name' => $a->staff->name,
                'method' => $a->method,
            ] : null;
            return $l;
        })->values();

        $payload['leads'] = $enriched;
        return response()->json($payload);
    }

    public function update(Request $request, string $phone, BotApi $bot) {
        $resp = $bot->patch('/admin/api/leads/' . urlencode($phone), $request->all());
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }

    /** Return phones needing classification (no service_interested yet, unless force). */
    public function classifiable(Request $request, BotApi $bot) {
        $force = $request->query('force') === '1';
        $resp = $bot->get('/admin/api/leads', ['limit' => 5000]);
        if (!$resp->ok()) return response()->json(['phones' => [], 'total' => 0]);
        $leads = $resp->json()['leads'] ?? [];
        $phones = collect($leads)
            ->filter(fn ($l) => $force || empty($l['service_interested'] ?? null))
            ->pluck('phone')
            ->filter()
            ->values()->all();
        return response()->json(['phones' => $phones, 'total' => count($phones)]);
    }

    /** Classify a single phone via Claude, then PATCH bot API. */
    public function classifyOne(string $phone, LeadClassifier $classifier, BotApi $bot) {
        if (empty(config('services.anthropic.api_key'))) {
            return response()->json([
                'ok' => false, 'phone' => $phone,
                'error' => 'ANTHROPIC_API_KEY not set in Laravel .env',
            ], 400);
        }

        $result = $classifier->classify($phone);
        if (!$result) {
            return response()->json(['ok' => false, 'phone' => $phone, 'reason' => 'no messages / API error']);
        }
        if (empty($result['service'])) {
            return response()->json([
                'ok' => false, 'phone' => $phone,
                'confidence' => $result['confidence'] ?? 'low',
                'reason' => $result['reason'] ?? 'AI unsure',
            ]);
        }

        $bot->patch('/admin/api/leads/' . urlencode($phone), [
            'service_interested' => $result['service'],
        ]);

        return response()->json([
            'ok' => true, 'phone' => $phone,
            'service' => $result['service'],
            'confidence' => $result['confidence'] ?? 'medium',
            'reason' => $result['reason'] ?? '',
        ]);
    }

    /** Export leads as CSV, optionally filtered by date range (created_at). */
    public function export(Request $request, BotApi $bot) {
        $from = $request->query('from');   // YYYY-MM-DD
        $to   = $request->query('to');     // YYYY-MM-DD

        $query = $request->only(['tier', 'stage', 'service', 'q']);
        $query['limit'] = 5000;
        $resp = $bot->get('/admin/api/leads', $query);
        if (!$resp->ok()) {
            return response('Bot API error: ' . $resp->status(), 502);
        }
        $leads = $resp->json()['leads'] ?? [];

        if ($from || $to) {
            $fromTs = $from ? strtotime($from . ' 00:00:00') : 0;
            $toTs   = $to   ? strtotime($to   . ' 23:59:59') : PHP_INT_MAX;
            $leads = array_values(array_filter($leads, function ($l) use ($fromTs, $toTs) {
                $ts = strtotime($l['created_at'] ?? $l['first_seen'] ?? $l['last_interaction'] ?? '');
                return $ts && $ts >= $fromTs && $ts <= $toTs;
            }));
        }

        $filename = 'leads-' . ($from ?: 'all') . '-' . ($to ?: date('Y-m-d')) . '.csv';

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            // BOM for Excel to detect UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Nama', 'Phone', 'Tier', 'Stage', 'Servis',
                'Score', 'Last Message', 'Created At', 'Last Interaction',
            ]);
            foreach ($leads as $l) {
                fputcsv($out, [
                    $l['name'] ?? '',
                    $l['phone'] ?? '',
                    $l['lead_tier'] ?? '',
                    $l['crm_stage'] ?? '',
                    $l['service_interested'] ?? '',
                    $l['lead_score'] ?? '',
                    $l['last_message'] ?? '',
                    $l['created_at'] ?? $l['first_seen'] ?? '',
                    $l['last_interaction'] ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Bulk round-robin distribute across active staff. */
    public function distribute(Request $request, BotApi $bot, LeadDistributor $distributor) {
        $data = $request->validate([
            'scope' => 'in:unassigned,all',           // which phones to consider
            'filter' => 'array',                       // tier/stage/service filter to narrow
            'override' => 'boolean',                   // overwrite existing assignments
        ]);
        $scope = $data['scope'] ?? 'unassigned';
        $filter = $data['filter'] ?? [];
        $override = (bool) ($data['override'] ?? false);

        $resp = $bot->get('/admin/api/leads', $filter + ['limit' => 5000]);
        if (!$resp->ok()) return response()->json(['error' => 'bot API failed'], 502);
        $phones = collect($resp->json()['leads'] ?? [])
            ->pluck('phone')->filter()->unique()->values()->all();

        if ($scope === 'unassigned') {
            $already = LeadAssignment::whereIn('phone', $phones)
                ->whereNotNull('staff_member_id')->pluck('phone')->all();
            $phones = array_values(array_diff($phones, $already));
        }

        $result = $distributor->distribute($phones, $override);
        return response()->json($result + ['total' => count($phones)]);
    }

    /** Manual assign / unassign a single lead. */
    public function assign(Request $request, string $phone) {
        $data = $request->validate([
            'staff_member_id' => 'nullable|integer|exists:staff_members,id',
        ]);
        $sid = $data['staff_member_id'] ?? null;
        LeadAssignment::updateOrCreate(
            ['phone' => $phone],
            ['staff_member_id' => $sid, 'method' => 'manual', 'assigned_at' => now()],
        );
        // Refresh cached counts
        foreach (StaffMember::all() as $s) {
            $s->update(['assigned_count' => LeadAssignment::where('staff_member_id', $s->id)->count()]);
        }
        return response()->json(['ok' => true, 'phone' => $phone, 'staff_member_id' => $sid]);
    }
}
