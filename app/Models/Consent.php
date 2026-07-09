<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $fillable = [
        'consentable_type', 'consentable_id', 'purpose', 'basis', 'granted',
        'notice_version', 'ip', 'user_agent', 'granted_at', 'withdrawn_at',
    ];
    protected $casts = [
        'granted' => 'boolean', 'granted_at' => 'datetime', 'withdrawn_at' => 'datetime',
    ];
    public function consentable() { return $this->morphTo(); }
}
