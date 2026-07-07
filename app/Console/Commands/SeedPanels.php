<?php

namespace App\Console\Commands;

use App\Models\Panel;
use Illuminate\Console\Command;

class SeedPanels extends Command {
    protected $signature = 'panels:seed {--file= : Path to a panels CSV (name,code,status)}';
    protected $description = 'Import insurance/corporate panels from the payor report CSV (idempotent) and sync bot knowledge.';

    public function handle(): int {
        $path = $this->option('file') ?: database_path('data/panels.csv');
        if (!is_file($path)) {
            $this->error("Panels CSV not found: {$path}");
            return self::FAILURE;
        }

        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $header = array_map(fn ($h) => strtolower(trim($h)), array_shift($rows));
        $idx = array_flip($header);

        if (!isset($idx['name'], $idx['code'])) {
            $this->error('CSV must have at least "name" and "code" columns.');
            return self::FAILURE;
        }

        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row[$idx['name']] ?? '');
            $code = trim($row[$idx['code']] ?? '');
            if ($name === '' || $code === '') {
                continue;
            }
            $status = strtolower(trim($row[$idx['status']] ?? 'active'));

            Panel::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => $status !== 'inactive'],
            );
            $count++;
        }

        Panel::syncKnowledge();

        $active = Panel::where('is_active', true)->count();
        $this->info("{$count} panels ensured ({$active} active). Knowledge entry 'insurance_panels' synced.");
        return self::SUCCESS;
    }
}
