<?php
namespace App\Services;

use App\Models\AccessLog;
use App\Models\Interpretation;
use App\Models\LabReport;
use App\Models\LabResult;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

/**
 * Two-phase pipeline with a clinician review gate:
 *   ingestDraft() : parse PDF -> patient + draft report (NO scoring yet)
 *   finalize()    : after a clinician verifies/edits values -> score + persist
 *
 * Interpretation never reaches a clinician or patient until finalize() runs,
 * so parser errors can be corrected first. PDPA s.40: explicit health-data
 * consent recorded at draft creation.
 */
class ReportPipeline
{
    public function __construct(
        private GnosisPdfParser $parser,
        private FunctionalMedicineInterpreter $engine,
        private NarrativeBuilder $narrative,
    ) {}

    /** Phase 1 — parse + stage. Returns an unscored draft report for review. */
    public function ingestDraft(string $pdfPath, ?int $clinicId, int $uploaderId): LabReport
    {
        $parsed = $this->parser->parseFile($pdfPath);
        $meta   = $parsed['meta'];
        $sex    = $meta['sex'] ?? 'M';

        return DB::transaction(function () use ($parsed, $meta, $sex, $pdfPath, $clinicId, $uploaderId) {
            $patient = Patient::create([
                'clinic_id' => $clinicId,
                'name'      => $meta['patient_name'] ?? 'UNKNOWN',
                'sex'       => $sex,
                'age'       => $meta['age'] ?? null,
            ]);

            $patient->consents()->create([
                'purpose' => 'health_processing', 'basis' => 'explicit_consent',
                'granted' => true, 'notice_version' => config('pdpa.notice_version'),
                'ip' => request()->ip(), 'user_agent' => request()->userAgent(),
                'granted_at' => now(),
            ]);

            $report = $patient->labReports()->create([
                'uploaded_by'     => $uploaderId,
                'status'          => 'draft',
                'lab_no'          => $meta['lab_no'] ?? null,
                'source_pdf_path' => $pdfPath,
                'raw_text'        => $parsed['raw'],
                'draft_values'    => $parsed['values'], // marker_key => value, pre-review
            ]);

            AccessLog::record('parsed', $report);
            return $report->load('patient');
        });
    }

    /**
     * Phase 2 — clinician has verified/edited $values. Score + persist + mark reviewed.
     * @param array<string,float> $values
     */
    public function finalize(LabReport $report, array $values, int $reviewerId): LabReport
    {
        abort_if($report->isReviewed(), 409, 'Report already finalised.');
        $sex = $report->patient->sex ?? 'M';

        return DB::transaction(function () use ($report, $values, $reviewerId, $sex) {
            $result = $this->engine->interpret($values, $sex);

            $report->results()->delete(); // idempotent if re-run before review
            foreach ($result['markers'] as $m) {
                LabResult::create([
                    'lab_report_id' => $report->id,
                    'marker_key'    => $m['key'],
                    'value'         => $m['value'],
                    'unit'          => $m['unit'],
                    'status'        => $m['status'],
                ]);
            }

            $report->interpretation()->updateOrCreate(
                ['lab_report_id' => $report->id],
                [
                    'engine_version' => FunctionalMedicineInterpreter::ENGINE_VERSION,
                    'ratios'         => $result['ratios'],
                    'critical'       => $result['critical'],
                    'narrative'      => $this->narrative->build($result),
                ]
            );

            $report->update([
                'status'      => 'reviewed',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            AccessLog::record('finalised', $report);
            return $report->load('results', 'interpretation', 'patient');
        });
    }
}
