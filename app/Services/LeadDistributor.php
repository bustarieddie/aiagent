<?php

namespace App\Services;

use App\Models\LeadAssignment;
use App\Models\StaffMember;
use Illuminate\Support\Facades\DB;

/**
 * Round-robin lead distribution.
 *
 * distribute(): assigns unassigned phones. Cycles through active staff in id
 * order, one phone each per turn. Existing assignments are left alone unless
 * $override is true.
 */
class LeadDistributor {
    /**
     * @param  array<string> $phones
     * @param  bool          $override  reassign even if phone is already assigned
     * @return array{assigned:int, skipped:int, per_staff:array<int,int>}
     */
    public function distribute(array $phones, bool $override = false): array {
        $staff = StaffMember::where('is_active', true)->orderBy('id')->get();
        if ($staff->isEmpty()) {
            return ['assigned' => 0, 'skipped' => count($phones), 'per_staff' => []];
        }

        // Existing assignments — skip these unless override
        $existing = $override
            ? collect()
            : LeadAssignment::whereIn('phone', $phones)
                ->pluck('staff_member_id', 'phone');

        $perStaff = $staff->pluck('id')->mapWithKeys(fn ($id) => [$id => 0])->all();
        $assigned = 0;
        $skipped = 0;
        $index = 0;

        // Start round-robin from the staff with the lightest load (fair when
        // distribute is called repeatedly with different batches).
        $startIndex = 0;
        $minCount = PHP_INT_MAX;
        foreach ($staff as $i => $s) {
            if ($s->assigned_count < $minCount) {
                $minCount = $s->assigned_count;
                $startIndex = $i;
            }
        }

        DB::transaction(function () use (
            $phones, $staff, $existing, $override,
            &$assigned, &$skipped, &$perStaff, &$index, $startIndex
        ) {
            foreach ($phones as $phone) {
                if (!$override && $existing->has($phone)) {
                    $skipped++;
                    continue;
                }
                $target = $staff[($startIndex + $index) % $staff->count()];
                LeadAssignment::updateOrCreate(
                    ['phone' => $phone],
                    [
                        'staff_member_id' => $target->id,
                        'method' => 'auto',
                        'assigned_at' => now(),
                    ],
                );
                $perStaff[$target->id]++;
                $assigned++;
                $index++;
            }

            // Refresh cached counts on staff rows
            foreach ($staff as $s) {
                $s->update([
                    'assigned_count' => LeadAssignment::where('staff_member_id', $s->id)->count(),
                ]);
            }
        });

        return ['assigned' => $assigned, 'skipped' => $skipped, 'per_staff' => $perStaff];
    }
}
