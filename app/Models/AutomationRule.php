<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model {
    protected $fillable = [
        'slug', 'name', 'icon', 'description',
        'schedule_label', 'schedule_cron',
        'trigger_type', 'trigger_config',
        'action_type', 'action_config',
        'is_active', 'is_system',
        'fire_count', 'runs_last_7d', 'sent_last_7d',
        'last_fired_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config'  => 'array',
        'is_active'      => 'boolean',
        'is_system'      => 'boolean',
        'last_fired_at'  => 'datetime',
    ];
}
