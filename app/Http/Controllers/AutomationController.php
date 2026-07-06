<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use Illuminate\Http\Request;

class AutomationController extends Controller {
    public function index() {
        return view('admin.automation');
    }

    public function list() {
        $rules = AutomationRule::orderByDesc('is_system')->orderBy('id')->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'slug' => $r->slug,
                'name' => $r->name,
                'icon' => $r->icon,
                'description' => $r->description,
                'schedule_label' => $r->schedule_label,
                'schedule_cron' => $r->schedule_cron,
                'is_active' => $r->is_active,
                'is_system' => $r->is_system,
                'fire_count' => $r->fire_count,
                'runs_last_7d' => $r->runs_last_7d,
                'sent_last_7d' => $r->sent_last_7d,
                'last_fired_at' => optional($r->last_fired_at)->diffForHumans(),
            ]);
        return response()->json(['rules' => $rules]);
    }

    public function store(Request $request) {
        $data = $this->validated($request);
        $rule = AutomationRule::create($data);
        return response()->json($rule);
    }

    public function update(Request $request, AutomationRule $rule) {
        $rule->update($this->validated($request));
        return response()->json($rule);
    }

    public function toggle(AutomationRule $rule) {
        $rule->update(['is_active' => !$rule->is_active]);
        return response()->json($rule);
    }

    public function destroy(AutomationRule $rule) {
        if ($rule->is_system) {
            return response()->json(['error' => 'Cannot delete system rule'], 403);
        }
        $rule->delete();
        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array {
        return $request->validate([
            'name' => 'required|string|max:100',
            'trigger_type' => 'required|in:keyword_in,no_reply_hours,new_lead',
            'trigger_config' => 'nullable|array',
            'action_type' => 'required|in:send_message,set_stage,set_tier,takeover,add_tag',
            'action_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
    }
}
