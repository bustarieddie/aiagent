<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Local, staff-set overrides for lead fields the Python bot doesn't reliably
 * persist (it re-derives crm_stage from conversation analysis, overwriting
 * manual changes). The portal treats these as the source of truth for display.
 */
class LeadOverride extends Model {
    protected $primaryKey = 'phone';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['phone', 'crm_stage'];
}
