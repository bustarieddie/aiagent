<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model {
    protected $fillable = [
        'slug', 'name', 'icon', 'description',
        'schedule_label', 'schedule_cron',
        'trigger_type', 'trigger_config',
        'action_type', 'action_config',
        'settings',
        'is_active', 'is_system',
        'fire_count', 'runs_last_7d', 'sent_last_7d',
        'last_fired_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config'  => 'array',
        'settings'       => 'array',
        'is_active'      => 'boolean',
        'is_system'      => 'boolean',
        'last_fired_at'  => 'datetime',
    ];

    /**
     * Field definitions for each system automation's settings form.
     * Types: text | textarea | number | toggle | time | select (with options).
     * Rendered dynamically by the Automation page.
     */
    public static function settingsSchema(): array {
        return [
            'appointment_reminder' => [
                ['key' => 'reminder_24h', 'label' => 'Hantar reminder T-24 jam', 'type' => 'toggle', 'default' => true],
                ['key' => 'message_24h', 'label' => 'Template mesej 24 jam', 'type' => 'textarea', 'default' => 'Salam {name}, ini peringatan appointment anda esok jam {time} di Klinik Bustari. Balas OK untuk sahkan.'],
                ['key' => 'reminder_2h', 'label' => 'Hantar reminder T-2 jam', 'type' => 'toggle', 'default' => true],
                ['key' => 'message_2h', 'label' => 'Template mesej 2 jam', 'type' => 'textarea', 'default' => 'Salam {name}, appointment anda dalam 2 jam lagi jam {time}. Jumpa sekejap lagi!'],
                ['key' => 'quiet_start', 'label' => 'Jangan hantar sebelum (jam)', 'type' => 'time', 'default' => '08:00'],
                ['key' => 'quiet_end', 'label' => 'Jangan hantar selepas (jam)', 'type' => 'time', 'default' => '21:00'],
            ],
            'no_reply_followup' => [
                ['key' => 'stop_on_reply', 'label' => 'Berhenti bila lead balas', 'type' => 'toggle', 'default' => true],
                ['key' => 'touch_1_hours', 'label' => 'Touch 1 selepas (jam)', 'type' => 'number', 'default' => 1, 'min' => 0],
                ['key' => 'message_touch_1', 'label' => 'Mesej Touch 1', 'type' => 'textarea', 'default' => 'Hai {name}, masih berminat dengan rawatan di Klinik Bustari? Boleh saya bantu?'],
                ['key' => 'touch_2_hours', 'label' => 'Touch 2 selepas (jam)', 'type' => 'number', 'default' => 24, 'min' => 0],
                ['key' => 'message_touch_2', 'label' => 'Mesej Touch 2', 'type' => 'textarea', 'default' => 'Salam {name}, sekadar ingin follow up. Ada apa-apa soalan tentang rawatan?'],
                ['key' => 'touch_3_hours', 'label' => 'Touch 3 selepas (jam)', 'type' => 'number', 'default' => 72, 'min' => 0],
                ['key' => 'message_touch_3', 'label' => 'Mesej Touch 3', 'type' => 'textarea', 'default' => 'Hai {name}, ini follow up terakhir dari kami. Kami sedia membantu bila-bila anda ready 😊'],
            ],
            'post_consultation_followup' => [
                ['key' => 'thankyou_enabled', 'label' => 'Hantar thank-you (D+0)', 'type' => 'toggle', 'default' => true],
                ['key' => 'message_thankyou', 'label' => 'Mesej thank-you', 'type' => 'textarea', 'default' => 'Terima kasih {name} kerana datang hari ini! Jaga diri & ikut nasihat doktor ya 🙏'],
                ['key' => 'checkin_days', 'label' => 'Check-in selepas (hari)', 'type' => 'number', 'default' => 3, 'min' => 0],
                ['key' => 'message_checkin', 'label' => 'Mesej check-in', 'type' => 'textarea', 'default' => 'Salam {name}, apa khabar selepas rawatan? Ada apa-apa yang boleh kami bantu?'],
                ['key' => 'review_days', 'label' => 'Minta review selepas (hari)', 'type' => 'number', 'default' => 7, 'min' => 0],
                ['key' => 'message_review', 'label' => 'Mesej minta review', 'type' => 'textarea', 'default' => 'Hai {name}, kalau berpuas hati dengan rawatan kami, boleh tinggalkan review di {review_link}? Terima kasih!'],
                ['key' => 'review_link', 'label' => 'Link review (Google/FB)', 'type' => 'text', 'default' => ''],
            ],
            'reactivation_campaign' => [
                ['key' => 'lapsed_days_soft', 'label' => 'Lapsed lembut selepas (hari)', 'type' => 'number', 'default' => 60, 'min' => 1],
                ['key' => 'message_soft', 'label' => 'Mesej lapsed lembut', 'type' => 'textarea', 'default' => 'Salam {name}, dah lama tak jumpa! Masa untuk check-up? Kami rindu anda di Klinik Bustari 😊'],
                ['key' => 'lapsed_days_hard', 'label' => 'Lapsed lama selepas (hari)', 'type' => 'number', 'default' => 180, 'min' => 1],
                ['key' => 'message_hard', 'label' => 'Mesej lapsed lama', 'type' => 'textarea', 'default' => 'Hai {name}, dah lebih 6 bulan. Jom set appointment untuk pastikan kesihatan anda terjaga.'],
                ['key' => 'daily_cap', 'label' => 'Had hantar sehari', 'type' => 'number', 'default' => 100, 'min' => 1],
            ],
            'broadcast_campaigns' => [
                ['key' => 'rate_per_minute', 'label' => 'Kadar hantar (mesej/minit)', 'type' => 'number', 'default' => 20, 'min' => 1],
                ['key' => 'daily_cap', 'label' => 'Had hantar sehari', 'type' => 'number', 'default' => 500, 'min' => 1],
                ['key' => 'send_window_start', 'label' => 'Tetingkap hantar mula', 'type' => 'time', 'default' => '09:00'],
                ['key' => 'send_window_end', 'label' => 'Tetingkap hantar tamat', 'type' => 'time', 'default' => '21:00'],
                ['key' => 'skip_opted_out', 'label' => 'Langkau yang opt-out', 'type' => 'toggle', 'default' => true],
            ],
            'daily_report' => [
                ['key' => 'channel', 'label' => 'Saluran hantar', 'type' => 'select', 'default' => 'telegram',
                    'options' => [
                        ['value' => 'telegram', 'label' => 'Telegram'],
                        ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                        ['value' => 'both', 'label' => 'Telegram + WhatsApp'],
                    ]],
                ['key' => 'recipients', 'label' => 'Penerima (chat ID / no. WA, pisah dgn koma)', 'type' => 'text', 'default' => ''],
                ['key' => 'send_time', 'label' => 'Masa hantar', 'type' => 'time', 'default' => '20:00'],
                ['key' => 'include_leads', 'label' => 'Sertakan statistik leads', 'type' => 'toggle', 'default' => true],
                ['key' => 'include_appointments', 'label' => 'Sertakan appointments', 'type' => 'toggle', 'default' => true],
                ['key' => 'include_conversion', 'label' => 'Sertakan conversion rate', 'type' => 'toggle', 'default' => true],
            ],
            'database_backup' => [
                ['key' => 'backup_time', 'label' => 'Masa backup', 'type' => 'time', 'default' => '03:00'],
                ['key' => 'retention_days', 'label' => 'Simpan backup (hari)', 'type' => 'number', 'default' => 14, 'min' => 1],
                ['key' => 'cloud_enabled', 'label' => 'Backup ke cloud', 'type' => 'toggle', 'default' => false],
                ['key' => 'cloud_destination', 'label' => 'Destinasi cloud (S3/GDrive path)', 'type' => 'text', 'default' => ''],
            ],
        ];
    }

    /** The settings field definitions for this rule (empty if none defined). */
    public function settingsFields(): array {
        return static::settingsSchema()[$this->slug] ?? [];
    }

    /** Stored settings merged over schema defaults, so the UI always has every key. */
    public function settingsWithDefaults(): array {
        $stored = $this->settings ?? [];
        $merged = [];
        foreach ($this->settingsFields() as $field) {
            $merged[$field['key']] = $stored[$field['key']] ?? $field['default'] ?? null;
        }
        return $merged;
    }
}
