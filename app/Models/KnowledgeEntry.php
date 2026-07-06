<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeEntry extends Model {
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected static function booted() {
        static::creating(fn ($m) => $m->id ??= (string) \Illuminate\Support\Str::uuid());
    }
}
