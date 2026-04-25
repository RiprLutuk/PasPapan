<?php

namespace App\Observers;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\BackupSecurityService;

class SystemBackupRunObserver
{
    public function __construct(
        protected BackupSecurityService $backupSecurityService,
    ) {}

    public function creating(SystemBackupRun $systemBackupRun): void
    {
        if (! $systemBackupRun->requested_by_user_id) {
            return;
        }

        $user = User::query()->find($systemBackupRun->requested_by_user_id);

        if ($user) {
            $this->backupSecurityService->assertCanManage($user, $systemBackupRun->type === 'restore' ? 'backup restore flow' : 'backup system');
        }
    }

    public function saving(SystemBackupRun $systemBackupRun): void
    {
        $this->backupSecurityService->enforceSizeLimit($systemBackupRun);
    }

    public function created(SystemBackupRun $systemBackupRun): void
    {
        $this->backupSecurityService->auditQueued($systemBackupRun);
    }

    public function updated(SystemBackupRun $systemBackupRun): void
    {
        if (! $systemBackupRun->wasChanged('status')) {
            return;
        }

        if ($systemBackupRun->status === 'completed') {
            $this->backupSecurityService->auditCompleted($systemBackupRun);

            return;
        }

        if ($systemBackupRun->status === 'failed') {
            $this->backupSecurityService->auditFailed($systemBackupRun);
        }
    }
}
