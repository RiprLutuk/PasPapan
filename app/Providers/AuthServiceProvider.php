<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\EmployeeDocumentRequest;
use App\Models\Holiday;
use App\Models\ImportExportRun;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\AppraisalPolicy;
use App\Policies\AttendanceCorrectionPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\CashAdvancePolicy;
use App\Policies\CompanyAssetPolicy;
use App\Policies\EmployeeDocumentRequestPolicy;
use App\Policies\HolidayPolicy;
use App\Policies\ImportExportRunPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\ShiftSwapRequestPolicy;
use App\Policies\SystemBackupRunPolicy;
use App\Support\ApprovalActorService;
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
        EmployeeDocumentRequest::class => EmployeeDocumentRequestPolicy::class,
        Holiday::class => HolidayPolicy::class,
        Reimbursement::class => ReimbursementPolicy::class,
        ShiftSwapRequest::class => ShiftSwapRequestPolicy::class,
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

        $adminPermission = fn (User $user, string|array $permissions, bool $legacyFallback = false): bool => $user->allowsAdminPermission($permissions, $legacyFallback);
        $approvalActors = app(ApprovalActorService::class);

        Gate::define('accessAdminPanel', fn (User $user): bool => $user->canAccessAdminPanel());
        Gate::define('reviewSubordinateRequests', fn (User $user): bool => $approvalActors->hasSubordinates($user));
        Gate::define('viewAdminDashboard', fn (User $user): bool => $adminPermission($user, 'admin.dashboard.view', true));
        Gate::define('viewEmployees', fn (User $user): bool => $adminPermission($user, 'admin.employees.view', true));
        Gate::define('viewAdminSettings', fn (User $user): bool => $adminPermission($user, 'admin.settings.view', true));
        Gate::define('viewAdminAttendances', fn (User $user): bool => $adminPermission($user, 'admin.attendances.view', true));
        Gate::define('viewAttendanceReports', fn (User $user): bool => $adminPermission($user, 'admin.attendances.report', true));
        Gate::define('viewAdminAttendanceCorrections', fn (User $user): bool => $adminPermission($user, 'admin.attendance_corrections.view', true));
        Gate::define('viewAdminDocumentRequests', fn (User $user): bool => $adminPermission($user, 'admin.document_requests.view', true));
        Gate::define('viewAdminReimbursements', fn (User $user): bool => $adminPermission($user, 'admin.reimbursements.view', true));
        Gate::define('viewAdminPayroll', fn (User $user): bool => $adminPermission($user, 'admin.payroll.view', true));
        Gate::define('viewAdminAssets', fn (User $user): bool => $adminPermission($user, 'admin.assets.view', true));
        Gate::define('viewAdminAppraisals', fn (User $user): bool => $adminPermission($user, 'admin.appraisals.view', true));
        Gate::define('viewAdminAccounts', fn (User $user): bool => $adminPermission($user, 'admin.admin_accounts.view', true));
        Gate::define('manageMasterData', fn (User $user): bool => $adminPermission($user, [
            'admin.divisions.manage',
            'admin.job_titles.manage',
            'admin.educations.manage',
            'admin.shifts.manage',
            'admin.admin_accounts.manage',
        ], true));
        Gate::define('manageDivisions', fn (User $user): bool => $adminPermission($user, 'admin.divisions.manage', true));
        Gate::define('manageJobTitles', fn (User $user): bool => $adminPermission($user, 'admin.job_titles.manage', true));
        Gate::define('manageEducations', fn (User $user): bool => $adminPermission($user, 'admin.educations.manage', true));
        Gate::define('manageShifts', fn (User $user): bool => $adminPermission($user, 'admin.shifts.manage', true));
        Gate::define('manageBarcodes', fn (User $user): bool => $adminPermission($user, 'admin.barcodes.manage', true));
        Gate::define('manageLeaveApprovals', fn (User $user): bool => $adminPermission($user, 'admin.leave_approvals.approve', true));
        Gate::define('manageSchedules', fn (User $user): bool => $adminPermission($user, 'admin.schedules.manage', true));
        Gate::define('manageOvertime', fn (User $user): bool => $adminPermission($user, 'admin.overtime.manage', true));
        Gate::define('manageAdminNotifications', fn (User $user): bool => $user->can('accessAdminPanel'));
        Gate::define('manageHolidays', fn (User $user): bool => $adminPermission($user, 'admin.holidays.manage', true));
        Gate::define('manageAnnouncements', fn (User $user): bool => $adminPermission($user, 'admin.announcements.manage', true));
        Gate::define('manageCashAdvances', fn (User $user): bool => $adminPermission($user, 'admin.cash_advances.manage', true));
        Gate::define('manageAttendanceCorrections', fn (User $user): bool => $adminPermission($user, 'admin.attendance_corrections.approve', true));
        Gate::define('managePayrollSettings', fn (User $user): bool => $adminPermission($user, 'admin.payroll_settings.manage', true));
        Gate::define('manageKpiSettings', fn (User $user): bool => $adminPermission($user, 'admin.kpi_settings.manage', $user->isSuperadmin));
        Gate::define('manageSystemSettings', fn (User $user): bool => $adminPermission($user, 'admin.settings.manage', $user->isSuperadmin));
        Gate::define('manageEnterpriseLicense', fn (User $user): bool => $adminPermission($user, 'admin.settings.license', $user->isSuperadmin));
        Gate::define('viewUserImportExport', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.view', $user->isSuperadmin));
        Gate::define('importUsers', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.import', $user->isSuperadmin));
        Gate::define('accessUserImportExport', fn (User $user): bool => $user->can('viewUserImportExport'));
        Gate::define('exportUsers', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.export', $user->isSuperadmin));
        Gate::define('viewAttendanceImportExport', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.view', true));
        Gate::define('importAttendances', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.import', true));
        Gate::define('exportAttendances', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.export', true));
        Gate::define('viewActivityLogs', fn (User $user): bool => $adminPermission($user, 'admin.activity_logs.view', true));
        Gate::define('exportActivityLogs', fn (User $user): bool => $adminPermission($user, 'admin.activity_logs.export', $user->isSuperadmin));
        Gate::define('viewAnalyticsDashboard', fn (User $user): bool => $adminPermission($user, 'admin.analytics.view', true));
        Gate::define('exportAdminReports', fn (User $user): bool => $adminPermission($user, 'admin.attendances.export', true));
        Gate::define('manageRbac', fn (User $user): bool => $user->canManageRbac());
        Gate::define('assignRoles', fn (User $user): bool => $user->canAssignRoles());
        Gate::define('manageUserRecord', function (User $user, ?User $subject = null, ?string $targetGroup = 'user'): bool {
            if ($user->isDemo && $targetGroup !== 'user') {
                return false;
            }

            if ($targetGroup === 'user') {
                return $user->allowsAdminPermission('admin.employees.manage', true);
            }

            return $user->isSuperadmin
                || ($subject !== null
                    && $user->allowsAdminPermission('admin.admin_accounts.manage', true)
                    && $user->is($subject));
        });
    }
}
