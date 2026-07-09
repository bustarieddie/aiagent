<?php
namespace App\Livewire;

use App\Models\LabReport;
use App\Support\FunctionalRanges;
use App\Services\ReportPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Clinician review gate. Shows every value the parser extracted, editable,
 * so mis-reads can be corrected BEFORE the interpretation is generated.
 * Finalising requires an explicit "verified against source PDF" attestation.
 */
class ReviewLabReport extends Component
{
    public LabReport $report;
    /** @var array<string,mixed> marker_key => value */
    public array $values = [];
    public bool $verified = false;

    public function mount(LabReport $report): void
    {
        Gate::authorize('view', $report);         // IDOR defence
        abort_if($report->isReviewed(), 409, 'Report already finalised.');
        $this->report = $report;
        $this->values = $report->draft_values ?? [];
    }

    protected function rules(): array
    {
        return [
            'values.*' => ['nullable', 'numeric'],
            'verified' => ['accepted'], // must attest verification to finalise
        ];
    }

    public function finalise(ReportPipeline $pipeline)
    {
        Gate::authorize('view', $this->report);
        $this->validate();

        $clean = array_filter(
            $this->values,
            fn ($v) => $v !== null && $v !== '' && is_numeric($v)
        );

        $pipeline->finalize($this->report, $clean, Auth::id());

        return redirect()->route('lab-reports.show', $this->report);
    }

    public function render()
    {
        return view('livewire.review-lab-report', [
            'catalogue' => FunctionalRanges::MARKERS,
        ]);
    }
}
