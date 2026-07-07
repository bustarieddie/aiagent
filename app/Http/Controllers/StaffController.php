<?php

namespace App\Http\Controllers;

use App\Models\LeadAssignment;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class StaffController extends Controller {
    public function index() {
        return view('admin.staff');
    }

    public function list() {
        $staff = StaffMember::orderByDesc('is_active')->orderBy('id')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
                'email' => $s->email,
                'is_active' => $s->is_active,
                'weight' => $s->weight,
                'assigned_count' => $s->assigned_count,
            ]);
        return response()->json(['staff' => $staff]);
    }

    public function store(Request $request) {
        $data = $this->validated($request);
        return response()->json(StaffMember::create($data));
    }

    public function update(Request $request, StaffMember $staff) {
        $staff->update($this->validated($request));
        return response()->json($staff);
    }

    public function toggle(StaffMember $staff) {
        $staff->update(['is_active' => !$staff->is_active]);
        return response()->json($staff);
    }

    public function destroy(StaffMember $staff) {
        LeadAssignment::where('staff_member_id', $staff->id)->update(['staff_member_id' => null]);
        $staff->delete();
        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array {
        return $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'is_active' => 'boolean',
            'weight' => 'integer|min:1|max:10',
        ]);
    }
}
