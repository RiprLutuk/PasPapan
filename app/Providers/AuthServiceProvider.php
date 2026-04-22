<?php

namespace App\Providers;

use App\Models\Appraisal;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Holiday;
use App\Models\ImportExportRun;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\AppraisalPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\AttendanceCorrectionPolicy;
use App\Policies\CashAdvancePolicy;
use App\Policies\CompanyAssetPolicy;
use App\Policies\HolidayPolicy;
use App\Policies\ImportExportRunPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\SystemBackupRunPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Announcement::class => AnnouncementPolicy::class,
        Attendance::class => AttendancePolicy::class,
        AttendanceCorrection::class => AttendanceCorrectionPolicy::class,
        Appraisal::class => AppraisalPolicy::class,
        CashAdvance::class => CashAdvancePolicy::class,
        Holiday::class => HolidayPolicy::class,
        Reimbursement::class => ReimbursementPolicy::class,
        CompanyAsset::class => CompanyAssetPolicy::class,
        ImportExportRun::class => ImportExportRunPolicy::class,
        Payroll::class => PayrollPolicy::class,
        SystemBackupRun::class => SystemBackupRunPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('accessAdminPanel', fn (User $user): bool => $user->isAdmin);
        Gate::define('viewAdminDashboard', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('viewEmployees', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('viewAdminSettings', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageMasterData', fn (User $user): bool => $user->isAdmin);
        Gate::define('manageBarcodes', fn (User $user): bool => $user->isAdmin);
        Gate::define('manageLeaveApprovals', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageSchedules', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageOvertime', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageAdminNotifications', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageHolidays', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageAnnouncements', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageCashAdvances', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageAttendanceCorrections', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageKpiSettings', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('manageSystemSettings', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('manageEnterpriseLicense', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('accessUserImportExport', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('exportUsers', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('viewActivityLogs', fn (User $user): bool => $user->isAdmin);
        Gate::define('exportActivityLogs', fn (User $user): bool => $user->isSuperadmin);
        Gate::define('viewAnalyticsDashboard', fn (User $user): bool => $user->isAdmin);
        Gate::define('exportAdminReports', fn (User $user): bool => $user->isAdmin);
        Gate::define('manageUserRecord', function (User $user, ?User $subject = null, ?string $targetGroup = 'user'): bool {
            if ($user->isDemo && $targetGroup !== 'user') {
                return false;
            }

            if ($targetGroup === 'user') {
                return $user->isAdmin;
            }

            return $user->isSuperadmin || ($subject !== null && $user->isAdmin && $user->is($subject));
        });
    }
}
