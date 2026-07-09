<?php
namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\LabReport;
use App\Services\DualReportRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabReportController extends Controller
{
    public function show(Request $request, LabReport $labReport)
    {
        $this->authorize('view', $labReport);                       // IDOR defence
        // Safety: an unreviewed draft has no clinician-verified interpretation.
        if (! $labReport->isReviewed()) {
            return redirect()->route('lab-reports.review', $labReport);
        }
        AccessLog::record('viewed', $labReport);

        $labReport->load('results', 'interpretation', 'patient');
        return view('reports.show', ['report' => $labReport]);
    }

    public function pdf(Request $request, LabReport $labReport, string $edition, DualReportRenderer $renderer)
    {
        abort_unless(in_array($edition, ['patient', 'practitioner'], true), 404);
        $this->authorize('view', $labReport);
        abort_unless($labReport->isReviewed(), 409, 'Report not yet reviewed.'); // no draft PDFs
        AccessLog::record('exported', $labReport);

        $path = $renderer->render($labReport, $edition);
        return Storage::disk(config('fm.pdf_disk'))->download($path);
    }
}
