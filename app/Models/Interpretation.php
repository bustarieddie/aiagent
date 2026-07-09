<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interpretation extends Model
{
    protected $fillable = [
        'lab_report_id', 'engine_version', 'ratios', 'critical', 'narrative',
        'patient_pdf_path', 'practitioner_pdf_path',
    ];
    protected $casts = ['ratios' => 'array', 'critical' => 'array', 'narrative' => 'array'];

    public function labReport(): BelongsTo { return $this->belongsTo(LabReport::class); }
}
