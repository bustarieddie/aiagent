<?php
namespace App\Livewire;

use App\Services\ReportPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadLabReport extends Component
{
    use WithFileUploads;

    public $pdf;
    public bool $consent = false; // PDPA s.6 — affirmative, unticked by default

    protected function rules(): array
    {
        return [
            'pdf'     => ['required', 'file', 'mimetypes:application/pdf', 'max:10240'],
            'consent' => ['accepted'],
        ];
    }

    public function save(ReportPipeline $pipeline)
    {
        $this->validate();

        $path = $this->pdf->storeAs(
            'lab-pdfs', Str::uuid().'.pdf', ['disk' => config('fm.pdf_disk')]
        );

        // Phase 1: parse into a DRAFT — nothing is scored/issued yet.
        $draft = $pipeline->ingestDraft(
            storage_path('app/'.$path),
            clinicId: Auth::user()->clinic_id ?? null,
            uploaderId: Auth::id(),
        );

        // Route the clinician to verify extracted values before finalising.
        return redirect()->route('lab-reports.review', $draft);
    }

    public function render()
    {
        return view('livewire.upload-lab-report');
    }
}
