<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Panel extends Model {
    protected $fillable = ['name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Rebuild the `insurance_panels` knowledge entry from the active panels so
     * the bot can auto-answer "ada panel X?" without escalating to staff.
     * Called after any seed / toggle / add / edit / delete.
     */
    public static function syncKnowledge(): void {
        if (!\Illuminate\Support\Facades\Schema::hasTable('knowledge_entries')) {
            return;
        }

        $active = static::where('is_active', true)->orderBy('name')->get();

        $list = $active->map(function ($p) {
            // Include the short code when it differs from the name, to help matching.
            return Str::upper($p->code) !== Str::upper($p->name)
                ? "{$p->name} ({$p->code})"
                : $p->name;
        })->implode(', ');

        $value = $active->isEmpty()
            ? 'Buat masa ini tiada panel insurance/korporat yang aktif disenaraikan. Sila semak dengan staf.'
            : "Klinik Bustari menerima panel insurance/korporat berikut ({$active->count()} panel aktif): {$list}. "
                . 'Jika nama syarikat/insurance pesakit ada dalam senarai ni, kita adalah panel mereka. '
                . 'Jika tiada dalam senarai, kita bukan panel — pesakit boleh bayar sendiri (self-pay).';

        KnowledgeEntry::updateOrCreate(
            ['key' => 'insurance_panels'],
            [
                'value' => $value,
                'lang' => 'bm',
                'category' => 'insurance',
                'notes' => 'Auto-generated dari Panels page. Jangan edit manual — akan di-overwrite bila panel dikemaskini.',
            ],
        );
    }
}
