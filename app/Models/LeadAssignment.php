<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignment extends Model {
    protected $fillable = ['phone', 'staff_member_id', 'method', 'assigned_at'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function staff(): BelongsTo {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }
}
