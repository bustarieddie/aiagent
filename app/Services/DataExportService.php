<?php
namespace App\Services;

use App\Models\Patient;

/**
 * PDPA s.30 (access) + 2024 portability: export a patient's data in a
 * structured, machine-readable form. Decrypted casts are resolved by Eloquent;
 * caller must be authorised and the access is logged.
 */
class DataExportService
{
    public function forPatient(Patient $patient): array
    {
        $patient->load('labReports.results', 'labReports.interpretation', 'consents');

        return [
            'patient' => $patient->only(['id', 'name', 'sex', 'age', 'created_at']),
            'consents' => $patient->consents->map->only(
                ['purpose', 'basis', 'granted', 'notice_version', 'granted_at', 'withdrawn_at']
            ),
            'lab_reports' => $patient->labReports->map(fn ($r) => [
                'lab_no'    => $r->lab_no,
                'status'    => $r->status,
                'reviewed_at' => $r->reviewed_at,
                'results'   => $r->results->map->only(['marker_key', 'value', 'unit', 'status']),
                'ratios'    => $r->interpretation?->ratios,
                'critical'  => $r->interpretation?->critical,
            ]),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
