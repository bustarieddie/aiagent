<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedSystemData extends Command {
    protected $signature = 'system:seed';
    protected $description = 'Run all idempotent system seeders (automations + panels). Safe to run on every deploy.';

    public function handle(): int {
        $this->info('Seeding system data…');

        foreach (['automation:seed', 'panels:seed'] as $cmd) {
            $this->line("→ {$cmd}");
            if ($this->call($cmd) !== self::SUCCESS) {
                $this->error("Failed: {$cmd}");
                return self::FAILURE;
            }
        }

        $this->info('System data seeded.');
        return self::SUCCESS;
    }
}
