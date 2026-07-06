<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRunLog extends Model {
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['run_at' => 'datetime'];
    protected static function booted() {
        static::creating(fn ($m) => $m->id ??= (string) \Illuminate\Support\Str::uuid());
    }
}
