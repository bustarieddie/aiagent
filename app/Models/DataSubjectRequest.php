<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    protected $fillable = ['patient_id', 'type', 'status', 'detail', 'due_at', 'fulfilled_at'];
    protected $casts = ['due_at' => 'datetime', 'fulfilled_at' => 'datetime'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
}
