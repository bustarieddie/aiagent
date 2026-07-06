<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * One-time data migration: pull Prisma-managed tables from Turso libSQL
 * into the local MySQL schema.
 *
 * Uses Turso HTTP API (libsql-server v2 protocol).
 */
class PullFromTurso extends Command {
    protected $signature = 'turso:pull {--truncate : truncate MySQL tables before insert}';
    protected $description = 'Import all data from Turso (Prisma-era) into local MySQL';

    /** Table name (Turso, camelCase Prisma) => target Laravel MySQL table + column mapping. */
    protected array $tables = [
        'Booking' => 'bookings',
        'Slot' => 'slots',
        'BlockedDate' => 'blocked_dates',
        'Setting' => 'settings',
        'Appointment' => 'appointments',
        'WhatsAppSession' => 'whats_app_sessions',
        'KnowledgeEntry' => 'knowledge_entries',
        'Service' => 'services',
        'ConversationFlag' => 'conversation_flags',
        'AiConfidenceEvent' => 'ai_confidence_events',
        'AutomationRunLog' => 'automation_run_logs',
        'PushSubscription' => 'push_subscriptions',
    ];

    public function handle(): int {
        $url = rtrim(config('services.turso.url', ''), '/');
        $token = config('services.turso.auth_token', '');
        if (empty($url) || empty($token)) {
            $this->error('TURSO_URL or TURSO_AUTH_TOKEN not set in .env');
            return 1;
        }

        // Turso HTTP protocol expects URL of the form https://<db>.turso.io
        // (drop libsql://). Endpoint for pipelined SQL is /v2/pipeline.
        $httpUrl = preg_replace('/^libsql:/', 'https:', $url);
        $endpoint = $httpUrl . '/v2/pipeline';

        foreach ($this->tables as $tursoTable => $mysqlTable) {
            $this->info("Fetching {$tursoTable} → {$mysqlTable}");
            $rows = $this->fetchAll($endpoint, $token, $tursoTable);
            $this->info("  " . count($rows) . " rows");
            if (empty($rows)) continue;

            if ($this->option('truncate')) {
                DB::table($mysqlTable)->truncate();
            }

            $chunks = array_chunk($rows, 200);
            foreach ($chunks as $chunk) {
                $mapped = array_map(fn ($r) => $this->camelToSnakeKeys($r), $chunk);
                DB::table($mysqlTable)->upsert($mapped, [$this->primaryKey($mysqlTable)]);
            }
        }

        $this->info('✅ Done.');
        return 0;
    }

    protected function fetchAll(string $endpoint, string $token, string $table): array {
        $resp = Http::withToken($token)->post($endpoint, [
            'requests' => [[
                'type' => 'execute',
                'stmt' => ['sql' => "SELECT * FROM \"{$table}\""],
            ], ['type' => 'close']],
        ]);
        if (!$resp->ok()) {
            $this->warn("  fetch failed: HTTP {$resp->status()} — {$resp->body()}");
            return [];
        }
        $json = $resp->json();
        $result = $json['results'][0]['response']['result'] ?? null;
        if (!$result) return [];
        $cols = collect($result['cols'])->pluck('name')->all();
        $rows = [];
        foreach ($result['rows'] ?? [] as $rawRow) {
            $obj = [];
            foreach ($cols as $i => $col) {
                $obj[$col] = $rawRow[$i]['value'] ?? null;
            }
            $rows[] = $obj;
        }
        return $rows;
    }

    protected function camelToSnakeKeys(array $row): array {
        $out = [];
        foreach ($row as $k => $v) {
            $snake = Str::snake($k);
            $out[$snake] = $v;
        }
        return $out;
    }

    protected function primaryKey(string $mysqlTable): string {
        return $mysqlTable === 'conversation_flags' ? 'phone' : 'id';
    }
}
