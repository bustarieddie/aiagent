<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model {
    protected $fillable = [
        'name', 'trigger_type', 'trigger_config',
        'action_type', 'action_config', 'is_active',
        'fire_count', 'last_fired_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config'  => 'array',
        'is_active'      => 'boolean',
        'last_fired_at'  => 'datetime',
    ];
}
