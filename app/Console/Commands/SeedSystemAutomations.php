<?php

namespace App\Console\Commands;

use App\Models\AutomationRule;
use Illuminate\Console\Command;

class SeedSystemAutomations extends Command {
    protected $signature = 'automation:seed';
    protected $description = 'Seed the fixed set of system automation rules (idempotent).';

    public function handle(): int {
        $rules = [
            [
                'slug' => 'appointment_reminder',
                'name' => 'Appointment Reminder',
                'icon' => '📅',
                'description' => 'T-24h & T-2h reminder ke pesakit yang confirmed appointment.',
                'schedule_label' => 'Setiap jam',
                'schedule_cron' => '0 * * * *',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'no_reply_followup',
                'name' => 'No-Reply Follow-up (3-touch)',
                'icon' => '👋',
                'description' => 'Sequence H+1 / D+1 / D+3 untuk lead yang tak reply (Dr B strategy).',
                'schedule_label' => 'Setiap 30 minit',
                'schedule_cron' => '*/30 * * * *',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'post_consultation_followup',
                'name' => 'Post-Consultation Follow-up',
                'icon' => '💬',
                'description' => 'D+0 thank-you, D+3 check-in, D+7 review request untuk pesakit selepas appointment.',
                'schedule_label' => 'Harian 5pm',
                'schedule_cron' => '0 17 * * *',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'reactivation_campaign',
                'name' => 'Reactivation Campaign',
                'icon' => '♻️',
                'description' => 'Hubungi pesakit yang dah lama tak datang (lapsed >60/180 hari).',
                'schedule_label' => 'Mingguan',
                'schedule_cron' => '0 10 * * 1',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'broadcast_campaigns',
                'name' => 'Broadcast Campaigns',
                'icon' => '📣',
                'description' => 'Mass send berdasarkan campaign queue dari Broadcast module.',
                'schedule_label' => 'On-demand',
                'schedule_cron' => null,
                'trigger_type' => 'new_lead',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'daily_report',
                'name' => 'Daily Report',
                'icon' => '📊',
                'description' => 'Daily summary ke staf Telegram/WA: leads, appointments, conversion.',
                'schedule_label' => 'Harian 8pm',
                'schedule_cron' => '0 20 * * *',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'telegram_monitor',
                'name' => 'Telegram Monitor',
                'icon' => '📡',
                'description' => 'Mirror semua mesej WhatsApp ke group Telegram staf (near real-time).',
                'schedule_label' => 'Setiap minit',
                'schedule_cron' => '* * * * *',
                'trigger_type' => 'new_lead',
                'action_type' => 'send_message',
            ],
            [
                'slug' => 'database_backup',
                'name' => 'Database Backup',
                'icon' => '💾',
                'description' => 'Backup harian patients.db ke local + cloud (jika di-configure).',
                'schedule_label' => 'Harian 3am',
                'schedule_cron' => '0 3 * * *',
                'trigger_type' => 'no_reply_hours',
                'action_type' => 'send_message',
            ],
        ];

        $schema = AutomationRule::settingsSchema();

        foreach ($rules as $r) {
            // Build default settings from the schema (idempotent, only fills missing keys).
            $defaults = [];
            foreach ($schema[$r['slug']] ?? [] as $field) {
                $defaults[$field['key']] = $field['default'] ?? null;
            }

            $existing = AutomationRule::where('slug', $r['slug'])->first();
            $settings = array_merge($defaults, $existing?->settings ?? []);

            AutomationRule::updateOrCreate(
                ['slug' => $r['slug']],
                array_merge($r, [
                    'is_system' => true,
                    'is_active' => $existing->is_active ?? false,
                    'trigger_config' => [],
                    'action_config' => [],
                    'settings' => $settings,
                ]),
            );
            $this->line("  {$r['icon']}  {$r['name']}");
        }
        $this->info(sprintf('%d system rules ensured.', count($rules)));
        return self::SUCCESS;
    }
}
