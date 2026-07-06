<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model {
    protected $fillable = [
        'name', 'mode', 'audience_filter', 'message_body',
        'meta_template_name', 'delay_ms', 'status',
        'total_count', 'sent_count', 'failed_count', 'skipped_count',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'audience_filter' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function recipients(): HasMany {
        return $this->hasMany(BroadcastRecipient::class);
    }
}
