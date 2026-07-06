<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model {
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'draft_reviewed_at' => 'datetime',
        'draft_edited_at' => 'datetime',
    ];
    protected static function booted() {
        static::creating(fn ($m) => $m->id ??= (string) \Illuminate\Support\Str::uuid());
    }
}
