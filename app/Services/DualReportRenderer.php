<?php
namespace App\Services;

use App\Models\LabReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders the Patient / Practitioner edition to A4 PDF via dompdf and stores it
 * on a PRIVATE disk (never public). Filenames randomized. Caller must authorize.
 */
class DualReportRenderer
{
    public function render(LabReport $report, string $edition): string
    {
        $report->loadMissing('results', 'interpretation', 'patient');

        $view = $edition === 'practitioner' ? 'reports.practitioner' : 'reports.patient';

        $pdf = Pdf::loadView($view, [
            'report'     => $report,
            'patient'    => $report->patient,
            'results'    => $report->results,
            'interp'     => $report->interpretation,
            'disclaimer' => config('fm.disclaimer'),
        ])->setPaper('a4');

        $path = 'fm-reports/'.Str::uuid().'-'.$edition.'.pdf';
        Storage::disk(config('fm.pdf_disk'))->put($path, $pdf->output());

        // Persist path back to the interpretation record.
        $col = $edition === 'practitioner' ? 'practitioner_pdf_path' : 'patient_pdf_path';
        $report->interpretation?->update([$col => $path]);

        return $path;
    }
}
