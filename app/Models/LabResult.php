<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = ['lab_report_id', 'marker_key', 'value', 'unit', 'status'];
    protected $casts = ['value' => 'float'];

    public function labReport(): BelongsTo { return $this->belongsTo(LabReport::class); }
}
