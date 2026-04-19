<?php

namespace App\Policies;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\BackupSecurityService;

class SystemBackupRunPolicy
{
    public function __construct(
        protected BackupSecurityService $backupSecurityService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin;
    }

    public function view(User $user, SystemBackupRun $systemBackupRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->backupSecurityService->canManage($user);
    }

    public function restore(User $user): bool
    {
        return $this->backupSecurityService->canManage($user);
    }
}
