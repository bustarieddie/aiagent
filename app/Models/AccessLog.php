<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccessLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'ip'];

    // Helper: record an access event (PDPA s.9). Never pass sensitive payloads.
    public static function record(string $action, Model $subject): void
    {
        static::create([
            'actor_id'     => Auth::id(),
            'action'       => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id'   => $subject->getKey(),
            'ip'           => request()->ip(),
        ]);
    }
}
