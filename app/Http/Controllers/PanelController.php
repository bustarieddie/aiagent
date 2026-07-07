<?php

namespace App\Http\Controllers;

use App\Models\Panel;
use Illuminate\Http\Request;

class PanelController extends Controller {
    public function index() {
        return view('admin.panels');
    }

    public function list(Request $request) {
        $q = trim((string) $request->query('q', ''));

        $query = Panel::query();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%");
            });
        }

        $panels = $query->orderBy('name')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'is_active' => $p->is_active,
            ]);

        return response()->json([
            'panels' => $panels,
            'total' => Panel::count(),
            'active' => Panel::where('is_active', true)->count(),
            'inactive' => Panel::where('is_active', false)->count(),
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:100|unique:panels,code',
            'is_active' => 'boolean',
        ]);
        $panel = Panel::create($data + ['is_active' => $request->boolean('is_active', true)]);
        Panel::syncKnowledge();
        return response()->json($panel);
    }

    public function update(Request $request, Panel $panel) {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'code' => 'sometimes|required|string|max:100|unique:panels,code,' . $panel->id,
            'is_active' => 'boolean',
        ]);
        $panel->update($data);
        Panel::syncKnowledge();
        return response()->json($panel);
    }

    public function toggle(Panel $panel) {
        $panel->update(['is_active' => !$panel->is_active]);
        Panel::syncKnowledge();
        return response()->json($panel);
    }

    public function destroy(Panel $panel) {
        $panel->delete();
        Panel::syncKnowledge();
        return response()->json(['ok' => true]);
    }
}
