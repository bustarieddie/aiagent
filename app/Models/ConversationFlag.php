<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationFlag extends Model {
    protected $primaryKey = 'phone';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'ai_enabled' => 'boolean',
        'human_takeover' => 'boolean',
        'pinned' => 'boolean',
    ];
}
