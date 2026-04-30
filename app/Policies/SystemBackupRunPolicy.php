<?php

namespace App\Policies;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\BackupSecurityService;

class SystemBackupRunPolicy
{
    public function __construct(
        protected BackupSecurityService $backupSecurityService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->allowsAdminPermission('admin.system_maintenance.view');
    }

    public function view(User $user, SystemBackupRun $systemBackupRun): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $user->can('manageSystemMaintenance');
    }

    public function create(User $user): bool
    {
        return $this->manage($user) && $this->backupSecurityService->canManage($user);
    }

    public function restore(User $user): bool
    {
        return $this->create($user);
    }

    public function download(User $user, SystemBackupRun $systemBackupRun): bool
    {
        return $systemBackupRun->status === 'completed'
            && filled($systemBackupRun->file_path)
            && $this->backupSecurityService->canManage($user);
    }

    public function delete(User $user, SystemBackupRun $systemBackupRun): bool
    {
        return $systemBackupRun->status === 'completed'
            && filled($systemBackupRun->file_path)
            && $this->backupSecurityService->canManage($user);
    }
}
