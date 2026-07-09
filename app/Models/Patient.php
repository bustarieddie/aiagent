<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    // Mass-assignment guard (webapps-security #4) — explicit whitelist.
    protected $fillable = ['clinic_id', 'name', 'ic_number', 'sex', 'age'];

    // PDPA s.40 — sensitive fields encrypted at rest.
    protected $casts = [
        'name'      => 'encrypted',
        'ic_number' => 'encrypted',
    ];

    public function labReports(): HasMany { return $this->hasMany(LabReport::class); }
    public function consents(): MorphMany { return $this->morphMany(Consent::class, 'consentable'); }

    public function hasActiveConsent(string $purpose): bool
    {
        return $this->consents()
            ->where('purpose', $purpose)->where('granted', true)
            ->whereNull('withdrawn_at')->exists();
    }
}
