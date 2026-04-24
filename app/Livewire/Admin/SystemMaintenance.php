<?php

namespace App\Livewire\Admin;

use App\Support\BackupSecurityService;
use App\Support\SystemMaintenanceActionService;
use App\Support\SystemMaintenanceViewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemMaintenance extends Component
{
    use WithFileUploads;

    private const BACKUP_SCHEDULE_TYPES = ['database', 'application', 'both'];

    private const BACKUP_SCHEDULE_FREQUENCIES = ['daily', 'weekly'];

    private const BACKUP_SCHEDULE_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public bool $cleanAttendances = false;

    public bool $cleanActivityLogs = false;

    public bool $cleanNotifications = false;

    public bool $cleanStorage = false;

    public bool $cleanNonAdminUsers = false;

    public bool $cleanCacheEntries = false;

    public bool $cleanQueuedJobs = false;

    public bool $cleanFailedJobs = false;

    public bool $cleanPersonalAccessTokens = false;

    public bool $maintenanceMode = false;

    public bool $backupScheduleEnabled = false;

    public string $cleanupConfirmation = '';

    public string $restoreConfirmation = '';

    public string $backupScheduleType = 'database';

    public string $backupScheduleFrequency = 'daily';

    public string $backupScheduleTime = '02:00';

    public string $backupScheduleDay = 'sunday';

    public int $backupRetentionDays = 14;

    public $backupFile;

    protected SystemMaintenanceActionService $systemMaintenanceActions;

    protected SystemMaintenanceViewService $systemMaintenanceView;

    protected BackupSecurityService $backupSecurityService;

    public function boot(
        SystemMaintenanceActionService $systemMaintenanceActions,
        SystemMaintenanceViewService $systemMaintenanceView,
        BackupSecurityService $backupSecurityService,
    ): void {
        $this->systemMaintenanceActions = $systemMaintenanceActions;
        $this->systemMaintenanceView = $systemMaintenanceView;
        $this->backupSecurityService = $backupSecurityService;
    }

    public function mount(): void
    {
        foreach ($this->systemMaintenanceActions->loadSettings() as $property => $value) {
            $this->{$property} = $value;
        }
    }

    public function toggleMaintenanceMode(): void
    {
        if (! $this->authorizeMaintenanceManager()) {
            return;
        }

        $this->maintenanceMode = ! $this->maintenanceMode;
        $this->systemMaintenanceActions->setMaintenanceMode($this->maintenanceMode);

        $this->dispatch(
            'success',
            message: $this->maintenanceMode
                ? __('Maintenance mode enabled. Admin users can still access the application.')
                : __('Maintenance mode disabled. The application is open again.'),
        );
    }

    public function clearApplicationCaches(): void
    {
        if (! $this->authorizeMaintenanceManager()) {
            return;
        }

        try {
            $this->systemMaintenanceActions->clearApplicationCaches();
            $this->dispatch('success', message: __('Application caches cleared successfully.'));
        } catch (\Throwable $exception) {
            Log::error('Application cache clear failed.', ['exception' => $exception->getMessage()]);
            $this->dispatch('error', message: __('Failed to clear application caches.'));
        }
    }

    public function restoreDatabase(): void
    {
        if (! $this->authorizeBackupManager('backup restore flow')) {
            return;
        }

        $this->validate([
            'backupFile' => 'required|file|max:51200',
        ]);

        if (trim($this->restoreConfirmation) !== 'RESTORE') {
            $this->addError('restoreConfirmation', __('Type RESTORE to confirm database recovery.'));

            return;
        }

        try {
            $this->systemMaintenanceActions->restoreDatabaseFromUploadedSql(
                $this->backupFile->getRealPath(),
                $this->backupFile->getClientOriginalExtension(),
            );

            $this->reset(['backupFile', 'restoreConfirmation']);

            $this->dispatch('success', message: __('Database restored successfully. The page will reload.'));
            $this->js('setTimeout(function(){ window.location.reload(); }, 2000);');
        } catch (\Throwable $exception) {
            Log::error('Database restore failed.', ['exception' => $exception->getMessage()]);
            $this->dispatch('error', message: __('Restore failed. Please verify the backup file and try again.'));
        }
    }

    public function cleanDatabase(): void
    {
        if (! $this->authorizeMaintenanceManager()) {
            return;
        }

        $selectedTasks = collect([
            $this->cleanAttendances,
            $this->cleanActivityLogs,
            $this->cleanNotifications,
            $this->cleanStorage,
            $this->cleanNonAdminUsers,
            $this->cleanCacheEntries,
            $this->cleanQueuedJobs,
            $this->cleanFailedJobs,
            $this->cleanPersonalAccessTokens,
        ])->contains(true);

        if (! $selectedTasks) {
            $this->dispatch('warning', message: __('Please select at least one option to clean.'));

            return;
        }

        if (trim($this->cleanupConfirmation) !== 'CLEAN') {
            $this->addError('cleanupConfirmation', __('Type CLEAN to confirm destructive cleanup tasks.'));

            return;
        }

        try {
            $summary = $this->systemMaintenanceActions->cleanup($this->cleanupPayload());

            $this->resetCleanupState();

            $message = empty($summary)
                ? __('Selected cleanup tasks completed successfully.')
                : __('Cleanup completed successfully. Removed: :summary', ['summary' => implode(', ', $summary)]);

            $this->dispatch('success', message: $message);
        } catch (\Throwable $exception) {
            Log::error('System maintenance cleanup failed.', ['exception' => $exception->getMessage()]);
            $this->dispatch('error', message: __('Failed to clean selected data. Please check the logs and try again.'));
        }
    }

    public function downloadBackup()
    {
        if (! $this->authorizeBackupManager('backup system')) {
            return null;
        }

        try {
            $result = $this->systemMaintenanceActions->createDownloadableDatabaseBackup(Auth::id());

            return response()->download(storage_path('app/'.$result['path']), $result['filename']);
        } catch (\Throwable $exception) {
            Log::error('Database backup failed.', ['exception' => $exception->getMessage()]);
            $this->dispatch('error', message: __('Backup failed. Please check the logs and try again.'));

            return null;
        }
    }

    public function queueDatabaseBackupJob(): void
    {
        if (! $this->authorizeBackupManager('backup system')) {
            return;
        }

        $this->queueBackupRun('database');
    }

    public function queueApplicationBackupJob(): void
    {
        if (! $this->authorizeBackupManager('backup system')) {
            return;
        }

        $this->queueBackupRun('application');
    }

    public function saveBackupAutomationSettings(): void
    {
        if (! $this->authorizeBackupManager('backup automation settings')) {
            return;
        }

        $validated = $this->validate([
            'backupScheduleType' => 'required|string|in:'.implode(',', self::BACKUP_SCHEDULE_TYPES),
            'backupScheduleFrequency' => 'required|string|in:'.implode(',', self::BACKUP_SCHEDULE_FREQUENCIES),
            'backupScheduleTime' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'backupScheduleDay' => 'required|string|in:'.implode(',', self::BACKUP_SCHEDULE_DAYS),
            'backupRetentionDays' => 'required|integer|min:1|max:365',
        ], [
            'backupScheduleTime.regex' => __('Backup schedule time must use 24-hour format like 02:00 or 23:30.'),
        ]);

        $this->systemMaintenanceActions->saveBackupAutomationSettings($validated, $this->backupScheduleEnabled);

        $this->dispatch('success', message: __('Backup automation settings saved successfully.'));
    }

    public function downloadExistingBackup(int $backupRunId)
    {
        if (! $this->authorizeBackupManager('backup artifacts')) {
            return null;
        }

        if (! $this->systemMaintenanceActions->hasBackupRunsTable()) {
            $this->dispatch('error', message: __('Backup history is unavailable until the latest maintenance migration is applied.'));

            return null;
        }

        $backupRun = $this->systemMaintenanceActions->findDownloadableBackupRun($backupRunId);

        if (! $backupRun) {
            $this->dispatch('error', message: __('The selected backup file was not found.'));

            return null;
        }

        return response()->download(storage_path('app/'.$backupRun->file_path), $backupRun->file_name ?? basename($backupRun->file_path));
    }

    public function deleteBackup(int $backupRunId): void
    {
        if (! $this->authorizeBackupManager('backup artifacts')) {
            return;
        }

        if (! $this->systemMaintenanceActions->hasBackupRunsTable()) {
            $this->dispatch('error', message: __('Backup history is unavailable until the latest maintenance migration is applied.'));

            return;
        }

        if (! $this->systemMaintenanceActions->deleteBackup($backupRunId)) {
            $this->dispatch('error', message: __('The selected backup file was not found.'));

            return;
        }

        $this->dispatch('success', message: __('Backup file deleted successfully.'));
    }

    private function authorizeMaintenanceManager(): bool
    {
        $user = Auth::user();

        if (! $user || $user->is_demo || ! $user->can('manageSystemMaintenance')) {
            $this->dispatch('error', message: __('Unauthorized action.'));

            return false;
        }

        return true;
    }

    private function authorizeBackupManager(string $context): bool
    {
        $user = Auth::user();

        if (! $this->authorizeMaintenanceManager() || ! $user) {
            return false;
        }

        try {
            $this->backupSecurityService->assertCanManage($user, $context);

            return true;
        } catch (AuthorizationException $exception) {
            $this->dispatch('error', message: __($exception->getMessage()));

            return false;
        }
    }

    private function resetCleanupState(): void
    {
        $this->reset([
            'cleanAttendances',
            'cleanActivityLogs',
            'cleanNotifications',
            'cleanStorage',
            'cleanNonAdminUsers',
            'cleanCacheEntries',
            'cleanQueuedJobs',
            'cleanFailedJobs',
            'cleanPersonalAccessTokens',
            'cleanupConfirmation',
        ]);
    }

    private function verifiedBackupSql(string $sqlContents): string
    {
        $actions = isset($this->systemMaintenanceActions)
            ? $this->systemMaintenanceActions
            : app(SystemMaintenanceActionService::class);

        return $actions->verifiedBackupSql($sqlContents);
    }

    private function queueBackupRun(string $type): void
    {
        if (! $this->systemMaintenanceActions->hasBackupRunsTable()) {
            $this->dispatch('error', message: __('Run the latest database migration before using queued backup jobs.'));

            return;
        }

        $this->systemMaintenanceActions->queueBackupRun($type, Auth::id());

        $this->dispatch('success', message: match ($type) {
            'application' => __('Application backup job has been submitted.'),
            default => __('Database backup job has been submitted.'),
        });
    }

    private function cleanupPayload(): array
    {
        return [
            'cleanAttendances' => $this->cleanAttendances,
            'cleanActivityLogs' => $this->cleanActivityLogs,
            'cleanNotifications' => $this->cleanNotifications,
            'cleanStorage' => $this->cleanStorage,
            'cleanNonAdminUsers' => $this->cleanNonAdminUsers,
            'cleanCacheEntries' => $this->cleanCacheEntries,
            'cleanQueuedJobs' => $this->cleanQueuedJobs,
            'cleanFailedJobs' => $this->cleanFailedJobs,
            'cleanPersonalAccessTokens' => $this->cleanPersonalAccessTokens,
        ];
    }

    public function render()
    {
        $dashboard = $this->systemMaintenanceView->buildDashboardData(
            $this->maintenanceMode,
            $this->backupScheduleEnabled,
            $this->backupScheduleType,
            $this->backupScheduleFrequency,
            $this->backupScheduleTime,
            $this->backupScheduleDay,
            $this->backupRetentionDays,
        );

        return view('livewire.admin.system-maintenance', [
            'maintenanceMode' => $this->maintenanceMode,
            ...$dashboard,
        ])->layout('layouts.app');
    }
}
