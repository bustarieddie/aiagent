<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSession extends Model {
    protected $table = 'whats_app_sessions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = ['last_seen_at' => 'datetime'];
}
