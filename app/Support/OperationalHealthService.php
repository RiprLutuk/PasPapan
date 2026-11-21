<?php

namespace App\Support;

use App\Helpers\Editions;
use App\Models\EmailDelivery;
use App\Models\ImportExportRun;
use App\Models\SystemBackupRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OperationalHealthService
{
    public function __construct(
        private readonly HrComplianceReminderService $hrComplianceReminder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $database = $this->databaseHealth();
        $backup = $this->backupHealth();
        $storageWritable = $this->storageWritable();
        $queueHeartbeat = Cache::get('health:queue_heartbeat_at');
        $schedulerHeartbeat = Cache::get('health:scheduler_heartbeat_at') ?? Cache::get('scheduler:last_run');
        $failedJobsCount = $this->failedJobsCount();
        $emailDelivery = $this->emailDeliveryHealth();
        $diskFreeBytes = $this->diskFreeBytes();
        $diskTotalBytes = $this->diskTotalBytes();
        $alerts = $this->alerts($database, $backup, $queueHeartbeat, $schedulerHeartbeat, $failedJobsCount, $diskFreeBytes, $storageWritable, $emailDelivery);

        return [
            'status' => $alerts === [] ? 'ok' : 'attention',
            'app_version' => $this->appVersion(),
            'database' => $database,
            'failed_jobs_count' => $failedJobsCount,
            'email_delivery' => $emailDelivery,
            'queue_backlog_count' => $this->queueBacklogCount(),
            'queue_heartbeat_at' => $queueHeartbeat,
            'scheduler_heartbeat_at' => $schedulerHeartbeat,
            'backup' => $backup,
            'backup_last_success_at' => $backup['last_success_at'],
            'storage_writable' => $storageWritable,
            'disk_free_bytes' => $diskFreeBytes,
            'disk_total_bytes' => $diskTotalBytes,
            'disk_free_human' => $this->formatBytes($diskFreeBytes),
            'disk_total_human' => $this->formatBytes($diskTotalBytes),
            'disk_used_percent' => $this->diskUsedPercent($diskFreeBytes, $diskTotalBytes),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'php_version' => PHP_VERSION,
            'database_driver' => DB::connection()->getDriverName(),
            'database_version' => $this->databaseVersion(),
            'hr_compliance' => $this->hrComplianceReminder->summary(),
            'import_export' => $this->importExportHealth(),
            'tables' => $this->tableSummary(),
            'scheduler_last_run' => $schedulerHeartbeat,
            'alerts' => $alerts,
            'license' => [
                'payroll_locked' => Editions::payrollLocked(),
                'reporting_locked' => Editions::reportingLocked(),
                'system_backup_locked' => Editions::systemBackupLocked(),
            ],
            'realtime' => [
                'broadcast_connection' => config('broadcasting.default'),
                'reverb_enabled' => config('broadcasting.default') === 'reverb',
                'polling_fallback' => config('realtime.poll_interval', '60s'),
            ],
        ];
    }

    /**
     * @return array{ok:bool,latency_ms:float|null,error:string|null}
     */
    protected function databaseHealth(): array
    {
        $started = microtime(true);

        try {
            DB::select('select 1');

            return [
                'ok' => true,
                'latency_ms' => round((microtime(true) - $started) * 1000, 2),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'latency_ms' => null,
                'error' => 'Database connectivity check failed.',
            ];
        }
    }

    protected function failedJobsCount(): int
    {
        return Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->count()
            : 0;
    }

    protected function queueBacklogCount(): int
    {
        return Schema::hasTable('jobs')
            ? DB::table('jobs')->count()
            : 0;
    }

    /** @return array{queued:int,retrying:int,failed:int,sent_last_24h:int} */
    protected function emailDeliveryHealth(): array
    {
        if (! Schema::hasTable('email_deliveries')) {
            return ['queued' => 0, 'retrying' => 0, 'failed' => 0, 'sent_last_24h' => 0];
        }

        return [
            'queued' => EmailDelivery::query()->where('status', EmailDelivery::STATUS_QUEUED)->count(),
            'retrying' => EmailDelivery::query()->where('status', EmailDelivery::STATUS_RETRYING)->count(),
            'failed' => EmailDelivery::query()->where('status', EmailDelivery::STATUS_FAILED)->count(),
            'sent_last_24h' => EmailDelivery::query()->where('status', EmailDelivery::STATUS_SENT)->where('sent_at', '>=', now()->subDay())->count(),
        ];
    }

    /**
     * @param  array{ok:bool,latency_ms:float|null,error:string|null}  $database
     * @param  array{last_success_at:mixed,file_present:bool,checksum_sha256:string|null,checksum_matches_meta:bool|null,last_failed_at:mixed}  $backup
     * @param  array{queued:int,retrying:int,failed:int,sent_last_24h:int}  $emailDelivery
     * @return array<int, array{code:string,level:string,message:string}>
     */
    protected function alerts(
        array $database,
        array $backup,
        mixed $queueHeartbeat,
        mixed $schedulerHeartbeat,
        int $failedJobsCount,
        int $diskFreeBytes,
        bool $storageWritable,
        array $emailDelivery,
    ): array {
        $alerts = [];

        if (! $database['ok']) {
            $alerts[] = $this->alert('database_unreachable', 'critical', 'Database connectivity check failed.');
        } elseif (($database['latency_ms'] ?? 0) > 500) {
            $alerts[] = $this->alert('database_latency_high', 'warning', 'Database latency is above the operational baseline.');
        }

        if (! $storageWritable) {
            $alerts[] = $this->alert('storage_not_writable', 'critical', 'Application storage is not writable.');
        }

        if ($diskFreeBytes > 0 && $diskFreeBytes < (1024 * 1024 * 1024)) {
            $alerts[] = $this->alert('disk_low', 'warning', 'Storage free space is below 1 GB.');
        }

        if ($failedJobsCount >= 10) {
            $alerts[] = $this->alert('failed_jobs_high', 'warning', 'Failed jobs count is above the support threshold.');
        }

        if ($emailDelivery['failed'] > 0) {
            $alerts[] = $this->alert('email_delivery_failed', 'warning', 'One or more application emails could not be delivered.');
        }

        if ($this->isStaleHeartbeat($schedulerHeartbeat, 5)) {
            $alerts[] = $this->alert('scheduler_stale', 'critical', 'Scheduler heartbeat has not been seen recently.');
        }

        if ($this->isStaleHeartbeat($queueHeartbeat, 5)) {
            $alerts[] = $this->alert('queue_stale', 'critical', 'Queue heartbeat job has not completed recently.');
        }

        if ($backup['last_failed_at'] !== null) {
            $alerts[] = $this->alert('backup_failed', 'warning', 'A backup run failed recently.');
        }

        if ($backup['checksum_matches_meta'] === false) {
            $alerts[] = $this->alert('backup_checksum_mismatch', 'critical', 'Latest backup checksum does not match recorded metadata.');
        }

        return $alerts;
    }

    /**
     * @return array{code:string,level:string,message:string}
     */
    protected function alert(string $code, string $level, string $message): array
    {
        return compact('code', 'level', 'message');
    }

    protected function isStaleHeartbeat(mixed $value, int $minutes): bool
    {
        if (! $value) {
            return true;
        }

        try {
            return Carbon::parse($value)->lt(now()->subMinutes($minutes));
        } catch (\Throwable) {
            return true;
        }
    }

    protected function storageWritable(): bool
    {
        return is_writable(storage_path('app'));
    }

    /**
     * @return array{last_success_at:mixed,file_present:bool,checksum_sha256:string|null,checksum_matches_meta:bool|null,last_failed_at:mixed}
     */
    protected function backupHealth(): array
    {
        $backup = SystemBackupRun::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if (! $backup) {
            return [
                'last_success_at' => null,
                'file_present' => false,
                'checksum_sha256' => null,
                'checksum_matches_meta' => null,
                'last_failed_at' => $this->latestFailedBackupAt(),
            ];
        }

        $path = (string) $backup->file_path;
        $disk = $backup->file_disk ?: 'local';
        $absolutePath = $disk === 'local' && $path !== ''
            ? Storage::disk('local')->path($path)
            : null;
        $checksum = $absolutePath && is_file($absolutePath)
            ? hash_file('sha256', $absolutePath)
            : null;
        $storedChecksum = data_get($backup->meta, 'checksum_sha256');

        return [
            'last_success_at' => $backup->completed_at,
            'file_present' => $checksum !== null,
            'checksum_sha256' => $checksum,
            'checksum_matches_meta' => is_string($storedChecksum) && $checksum !== null
                ? hash_equals($storedChecksum, $checksum)
                : null,
            'last_failed_at' => $this->latestFailedBackupAt(),
        ];
    }

    protected function latestFailedBackupAt(): mixed
    {
        return SystemBackupRun::query()
            ->where('status', 'failed')
            ->latest('failed_at')
            ->value('failed_at');
    }

    protected function diskFreeBytes(): int
    {
        $free = @disk_free_space(storage_path('app'));

        return is_float($free) ? (int) $free : 0;
    }

    protected function diskTotalBytes(): int
    {
        $total = @disk_total_space(storage_path('app'));

        return is_float($total) ? (int) $total : 0;
    }

    protected function diskUsedPercent(int $freeBytes, int $totalBytes): ?int
    {
        if ($freeBytes <= 0 || $totalBytes <= 0) {
            return null;
        }

        return (int) round((1 - ($freeBytes / $totalBytes)) * 100);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }

    protected function appVersion(): string
    {
        $packagePath = base_path('package.json');

        if (! is_file($packagePath)) {
            return (string) config('app.version', 'unknown');
        }

        $package = json_decode((string) file_get_contents($packagePath), true);

        return (string) ($package['version'] ?? config('app.version', 'unknown'));
    }

    protected function databaseVersion(): string
    {
        try {
            $result = DB::select('select version() as version');
            $version = $result[0]->version ?? null;

            return is_string($version) ? $version : 'unknown';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * @return array{queued:int,running:int,failed:int,last_completed_at:mixed}
     */
    protected function importExportHealth(): array
    {
        if (! Schema::hasTable('import_export_runs')) {
            return [
                'queued' => 0,
                'running' => 0,
                'failed' => 0,
                'last_completed_at' => null,
            ];
        }

        return [
            'queued' => ImportExportRun::query()->where('status', 'queued')->count(),
            'running' => ImportExportRun::query()->where('status', 'running')->count(),
            'failed' => ImportExportRun::query()->where('status', 'failed')->count(),
            'last_completed_at' => ImportExportRun::query()
                ->where('status', 'completed')
                ->latest('completed_at')
                ->value('completed_at'),
        ];
    }

    /**
     * @return array<int, array{name:string,rows:int|null,size:string}>
     */
    protected function tableSummary(): array
    {
        try {
            $rows = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => $this->mysqlTableSummaryRows(),
                'pgsql' => $this->postgresTableSummaryRows(),
                default => [],
            };

            return collect($rows)
                ->map(fn (object $row): array => [
                    'name' => (string) ($row->table_name ?? ''),
                    'rows' => isset($row->table_rows) ? (int) $row->table_rows : null,
                    'size' => $this->formatBytes((int) ($row->total_bytes ?? 0)),
                ])
                ->filter(fn (array $row): bool => $row['name'] !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<object>
     */
    private function mysqlTableSummaryRows(): array
    {
        $database = DB::connection()->getDatabaseName();

        return DB::select(
            'select table_name, table_rows, data_length + index_length as total_bytes
            from information_schema.tables
            where table_schema = ?
            order by total_bytes desc
            limit 5',
            [$database]
        );
    }

    /**
     * @return list<object>
     */
    private function postgresTableSummaryRows(): array
    {
        $schema = (string) config('database.connections.pgsql.search_path', 'public');

        return DB::select(
            'select relname as table_name,
                n_live_tup as table_rows,
                pg_total_relation_size(relid) as total_bytes
            from pg_stat_user_tables
            where schemaname = ?
            order by pg_total_relation_size(relid) desc
            limit 5',
            [$schema]
        );
    }
}
