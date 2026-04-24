<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class BackupSecurityService
{
    public function canManage(User $user): bool
    {
        if (! $user->isSuperadmin) {
            return false;
        }

        if (! $this->requiresMfa()) {
            return true;
        }

        return $user->hasEnabledTwoFactorAuthentication();
    }

    public function assertCanManage(User $user, string $context = 'backup system'): void
    {
        if ($this->canManage($user)) {
            return;
        }

        if (! $user->isSuperadmin) {
            throw new AuthorizationException('Only superadmins can manage the '.$context.'.');
        }

        throw new AuthorizationException('Multi-factor authentication is required before managing the '.$context.'.');
    }

    public function requiresMfa(): bool
    {
        return filter_var(Setting::getValue('backup.require_mfa', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function maxFileSizeBytes(): int
    {
        $configured = (int) Setting::getValue('backup.max_file_size_bytes', 104857600);

        return max(1024, $configured);
    }

    public function isWithinSizeLimit(?int $sizeBytes): bool
    {
        return $sizeBytes === null || $sizeBytes <= $this->maxFileSizeBytes();
    }

    public function enforceSizeLimit(SystemBackupRun $backupRun): void
    {
        if ($backupRun->status !== 'completed' || $this->isWithinSizeLimit($backupRun->size_bytes)) {
            return;
        }

        $backupRun->status = 'failed';
        $backupRun->error_message = 'Backup artifact exceeds configured size limit of '.$this->maxFileSizeBytes().' bytes.';
        $backupRun->failed_at = now();
        $backupRun->completed_at = null;
    }

    public function auditQueued(SystemBackupRun $backupRun): void
    {
        ActivityLog::record($this->label($backupRun, 'Queued'), $this->description($backupRun));
    }

    public function auditCompleted(SystemBackupRun $backupRun): void
    {
        ActivityLog::record($this->label($backupRun, 'Completed'), $this->description($backupRun));
    }

    public function auditFailed(SystemBackupRun $backupRun): void
    {
        ActivityLog::record($this->label($backupRun, 'Failed'), $this->description($backupRun, $backupRun->error_message));
    }

    protected function label(SystemBackupRun $backupRun, string $suffix): string
    {
        $type = $backupRun->type === 'restore' ? 'Backup Restore' : 'Backup '.ucfirst($backupRun->type);

        return trim($type.' '.$suffix);
    }

    protected function description(SystemBackupRun $backupRun, ?string $suffix = null): string
    {
        $parts = [
            'Run #'.$backupRun->id,
            'type='.$backupRun->type,
        ];

        if ($backupRun->file_name) {
            $parts[] = 'file='.$backupRun->file_name;
        }

        if ($backupRun->size_bytes) {
            $parts[] = 'size='.$backupRun->size_bytes.' bytes';
        }

        if ($suffix) {
            $parts[] = $suffix;
        }

        return implode(', ', $parts);
    }
}
