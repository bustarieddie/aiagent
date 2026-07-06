<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model {
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
