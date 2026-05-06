<?php

namespace App\Console\Commands;

use App\Support\ImportExportRunRetention;
use Illuminate\Console\Command;

class PruneExpiredImportExportRuns extends Command
{
    protected $signature = 'import-export-runs:prune-expired {--hours=12 : Keep completed and failed jobs for this many hours}';

    protected $description = 'Delete expired import/export job records and generated files.';

    public function handle(ImportExportRunRetention $retention): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $retention->pruneExpired($hours);

        $this->info("Deleted {$deleted} expired import/export job(s).");

        return self::SUCCESS;
    }
}
