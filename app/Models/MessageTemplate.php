<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model {
    protected $fillable = ['name', 'category', 'body', 'use_count'];
}
