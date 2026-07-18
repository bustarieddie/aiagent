<?php

namespace App\Http\Controllers;

use App\Models\LeadOverride;
use App\Services\BotApi;
use Illuminate\Http\Request;

class PatientController extends Controller {
    public function index() {
        return view('admin.patients');
    }

    public function show(string $phone) {
        return view('admin.patient-detail', ['phone' => $phone]);
    }

    // JSON API proxies to bot
    public function list(Request $request, BotApi $bot) {
        $resp = $bot->get('/admin/api/patients', $request->query());
        if (!$resp->ok()) {
            return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
        }
        $payload = $resp->json();
        $patients = $payload['patients'] ?? [];

        // Same crm_stage override as Leads/Conversations, so all views stay in sync.
        $phones = collect($patients)->pluck('phone')->filter()->all();
        $overrides = LeadOverride::whereIn('phone', $phones)->get()->keyBy('phone');
        $payload['patients'] = array_map(function ($p) use ($overrides) {
            $o = $overrides[$p['phone']] ?? null;
            if ($o && $o->crm_stage) {
                $p['crm_stage'] = $o->crm_stage;
            }
            return $p;
        }, $patients);

        return response()->json($payload);
    }

    public function detail(string $phone, BotApi $bot) {
        $resp = $bot->get('/admin/api/patients/' . urlencode($phone));
        if (!$resp->ok()) {
            return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
        }
        $payload = $resp->json();
        $o = LeadOverride::find($phone);
        if ($o && $o->crm_stage) {
            $payload['crm_stage'] = $o->crm_stage;
        }
        return response()->json($payload);
    }

    public function update(Request $request, string $phone, BotApi $bot) {
        $data = $request->all();

        // Persist crm_stage as a local override (source of truth for the portal),
        // keeping Patients in sync with Leads/Conversations.
        if (array_key_exists('crm_stage', $data)) {
            LeadOverride::updateOrCreate(
                ['phone' => $phone],
                ['crm_stage' => $data['crm_stage']],
            );
        }

        try {
            $resp = $bot->patch('/admin/api/patients/' . urlencode($phone), $data);
            if ($resp->ok()) {
                return response($resp->body(), 200)->header('Content-Type', 'application/json');
            }
        } catch (\Throwable) {
            // ignore — local override already saved
        }

        return response()->json(['ok' => true]);
    }
}
