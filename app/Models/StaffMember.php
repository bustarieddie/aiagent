<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model {
    protected $fillable = ['name', 'phone', 'email', 'is_active', 'weight', 'assigned_count'];

    protected $casts = ['is_active' => 'boolean'];

    public function assignments(): HasMany {
        return $this->hasMany(LeadAssignment::class);
    }
}
