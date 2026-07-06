<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'visible_on_site' => 'boolean',
        'visible_to_ai' => 'boolean',
    ];
    protected static function booted() {
        static::creating(function ($m) {
            $m->id ??= (string) \Illuminate\Support\Str::uuid();
            $m->price_tiers ??= '[]';
        });
    }

    public function getPriceTiersArrayAttribute(): array {
        return json_decode($this->price_tiers ?? '[]', true) ?: [];
    }
}
