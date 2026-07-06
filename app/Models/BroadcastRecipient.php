<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRecipient extends Model {
    protected $fillable = [
        'broadcast_id', 'phone', 'status', 'failure_reason', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo {
        return $this->belongsTo(Broadcast::class);
    }
}
