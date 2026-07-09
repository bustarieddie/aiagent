<?php
namespace App\Console\Commands;

use App\Models\AccessLog;
use App\Models\LabReport;
use Illuminate\Console\Command;

// PDPA s.10 retention auto-purge. Schedule daily (routes/console.php).
class PurgeExpiredData extends Command
{
    protected $signature = 'pdpa:purge';
    protected $description = 'Purge personal/health data past its retention period (PDPA s.10)';

    public function handle(): int
    {
        $days = config('pdpa.retention_days');

        LabReport::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days['lab_report']))
            ->forceDelete();

        AccessLog::where('created_at', '<', now()->subDays($days['access_log']))->delete();

        $this->info('PDPA purge complete.');
        return self::SUCCESS;
    }
}
