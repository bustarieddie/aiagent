<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A staff reply sent from the portal, stored locally so it survives page
 * reloads even when it was delivered via the WaSenderAPI fallback (and thus
 * never recorded in the Python bot's own conversation store).
 */
class StaffMessage extends Model {
    protected $fillable = ['phone', 'body', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
