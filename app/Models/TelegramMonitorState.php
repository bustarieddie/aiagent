<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMonitorState extends Model {
    protected $primaryKey = 'phone';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['phone', 'last_key'];
}
