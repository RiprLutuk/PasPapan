<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\ImportExportRun;
use App\Models\User;

class ImportExportRunPolicy
{
    public function download(User $user, ImportExportRun $run): bool
    {
        if (! $user->can('accessAdminPanel')) {
            return false;
        }

        if ($run->requested_by_user_id && $user->id !== $run->requested_by_user_id && ! $user->isSuperadmin) {
            return false;
        }

        return match ($run->resource) {
            'users' => $run->operation === 'import'
                ? $user->can('accessUserImportExport')
                : $user->can('exportUsers'),
            'attendances' => $user->can('viewAdminAny', Attendance::class),
            'activity_logs' => $user->can('exportActivityLogs'),
            'monthly_report_pdf' => $user->can('exportAdminReports'),
            default => false,
        };
    }
}
