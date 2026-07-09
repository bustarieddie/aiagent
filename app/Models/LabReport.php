<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'uploaded_by', 'lab_no', 'panel', 'status',
        'collected_at', 'reported_at', 'source_pdf_path', 'raw_text',
        'draft_values', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'collected_at' => 'date',
        'reported_at'  => 'date',
        'reviewed_at'  => 'datetime',
        'raw_text'     => 'encrypted',   // PDPA s.40
        'draft_values' => 'encrypted:array', // parsed health values, encrypted at rest
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function results(): HasMany { return $this->hasMany(LabResult::class); }
    public function interpretation(): HasOne { return $this->hasOne(Interpretation::class); }

    public function isReviewed(): bool { return $this->status === 'reviewed'; }
}
