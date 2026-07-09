<?php
namespace App\Policies;

use App\Models\LabReport;
use App\Models\User;

// webapps-security #3: authorize every action server-side. Prevents IDOR —
// a user may only touch reports within their own clinic scope.
class LabReportPolicy
{
    public function view(User $user, LabReport $report): bool
    {
        return $this->sameClinic($user, $report);
    }
    public function delete(User $user, LabReport $report): bool
    {
        return $this->sameClinic($user, $report);
    }
    private function sameClinic(User $user, LabReport $report): bool
    {
        return $report->patient
            && (int) $report->patient->clinic_id === (int) ($user->clinic_id ?? -1);
    }
}
