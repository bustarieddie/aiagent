<?php
namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use App\Services\DataExportService;
use Illuminate\Http\Request;

/**
 * PDPA data-subject rights (s.30–43 + 2024 portability). Every action is
 * authorised (clinic scope), logged, and recorded for the 21-day SLA.
 */
class DataSubjectController extends Controller
{
    // s.30 access + portability: structured JSON download.
    public function export(Request $r, Patient $patient, DataExportService $svc)
    {
        $this->authorizeClinic($r, $patient);
        $this->logRequest($patient, 'portability', 'fulfilled');
        AccessLog::record('exported', $patient);

        $data = $svc->forPatient($patient);
        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            'patient-'.$patient->id.'-data-'.now()->format('Ymd').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    // s.38 withdraw consent — must actually stop processing.
    public function withdraw(Request $r, Patient $patient)
    {
        $this->authorizeClinic($r, $patient);
        $patient->consents()->whereNull('withdrawn_at')
            ->update(['granted' => false, 'withdrawn_at' => now()]);
        $this->logRequest($patient, 'withdraw', 'fulfilled');
        AccessLog::record('consent_withdrawn', $patient);

        return back()->with('status', 'Consent withdrawn; processing stopped.');
    }

    // s.10/erasure: log request + soft-delete (purge command hard-deletes later).
    public function erase(Request $r, Patient $patient)
    {
        $this->authorizeClinic($r, $patient);
        $this->logRequest($patient, 'erasure', 'fulfilled');
        AccessLog::record('erasure_requested', $patient);
        $patient->delete(); // soft delete; retained until pdpa:purge window

        return redirect('/')->with('status', 'Erasure recorded; data scheduled for purge.');
    }

    private function authorizeClinic(Request $r, Patient $patient): void
    {
        abort_unless(
            (int) $patient->clinic_id === (int) ($r->user()->clinic_id ?? -1),
            403
        );
    }

    private function logRequest(Patient $patient, string $type, string $status): void
    {
        DataSubjectRequest::create([
            'patient_id'   => $patient->id,
            'type'         => $type,
            'status'       => $status,
            'due_at'       => now()->addDays(21), // s.31 SLA
            'fulfilled_at' => $status === 'fulfilled' ? now() : null,
        ]);
    }
}
