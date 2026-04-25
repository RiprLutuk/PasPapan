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

        $adminPermission = fn (User $user, string|array $permissions): bool => $user->allowsAdminPermission($permissions);
        $approvalActors = app(ApprovalActorService::class);

        Gate::define('accessAdminPanel', fn (User $user): bool => $user->canAccessAdminPanel());
        Gate::define('reviewSubordinateRequests', fn (User $user): bool => $approvalActors->hasSubordinates($user));
        Gate::define('viewAdminDashboard', fn (User $user): bool => $adminPermission($user, 'admin.dashboard.view'));
        Gate::define('viewEmployees', fn (User $user): bool => $adminPermission($user, 'admin.employees.view'));
        Gate::define('manageEmployeeStatuses', fn (User $user): bool => $adminPermission($user, 'admin.employees.manage_status'));
        Gate::define('approveEmployeeAccountDeletion', fn (User $user): bool => $adminPermission($user, 'admin.employees.approve_account_deletion'));
        Gate::define('viewAdminSettings', fn (User $user): bool => $adminPermission($user, 'admin.settings.view'));
        Gate::define('viewAdminAttendances', fn (User $user): bool => $adminPermission($user, 'admin.attendances.view'));
        Gate::define('viewAttendanceReports', fn (User $user): bool => $adminPermission($user, 'admin.attendances.report'));
        Gate::define('viewAdminAttendanceCorrections', fn (User $user): bool => $adminPermission($user, 'admin.attendance_corrections.view'));
        Gate::define('viewAdminDocumentRequests', fn (User $user): bool => $adminPermission($user, 'admin.document_requests.view'));
        Gate::define('viewAdminReimbursements', fn (User $user): bool => $adminPermission($user, 'admin.reimbursements.view'));
        Gate::define('viewAdminPayroll', fn (User $user): bool => $adminPermission($user, 'admin.payroll.view'));
        Gate::define('viewAdminAssets', fn (User $user): bool => $adminPermission($user, 'admin.assets.view'));
        Gate::define('viewAdminAppraisals', fn (User $user): bool => $adminPermission($user, 'admin.appraisals.view'));
        Gate::define('viewAdminAccounts', fn (User $user): bool => $adminPermission($user, 'admin.admin_accounts.view'));
        Gate::define('manageMasterData', fn (User $user): bool => $adminPermission($user, [
            'admin.divisions.manage',
            'admin.job_titles.manage',
            'admin.educations.manage',
            'admin.shifts.manage',
            'admin.admin_accounts.manage',
        ]));
        Gate::define('manageDivisions', fn (User $user): bool => $adminPermission($user, 'admin.divisions.manage'));
        Gate::define('manageJobTitles', fn (User $user): bool => $adminPermission($user, 'admin.job_titles.manage'));
        Gate::define('manageEducations', fn (User $user): bool => $adminPermission($user, 'admin.educations.manage'));
        Gate::define('manageShifts', fn (User $user): bool => $adminPermission($user, 'admin.shifts.manage'));
        Gate::define('manageBarcodes', fn (User $user): bool => $adminPermission($user, 'admin.barcodes.manage'));
        Gate::define('manageLeaveApprovals', fn (User $user): bool => $adminPermission($user, 'admin.leave_approvals.approve'));
        Gate::define('manageShiftSwapApprovals', fn (User $user): bool => $adminPermission($user, 'admin.shift_swaps.approve'));
        Gate::define('manageSchedules', fn (User $user): bool => $adminPermission($user, 'admin.schedules.manage'));
        Gate::define('manageOvertime', fn (User $user): bool => $adminPermission($user, 'admin.overtime.manage'));
        Gate::define('manageAdminNotifications', fn (User $user): bool => $adminPermission($user, 'admin.notifications.view'));
        Gate::define('manageHolidays', fn (User $user): bool => $adminPermission($user, 'admin.holidays.manage'));
        Gate::define('manageAnnouncements', fn (User $user): bool => $adminPermission($user, 'admin.announcements.manage'));
        Gate::define('manageCashAdvances', fn (User $user): bool => $adminPermission($user, 'admin.cash_advances.manage'));
        Gate::define('manageAttendanceCorrections', fn (User $user): bool => $adminPermission($user, 'admin.attendance_corrections.approve'));
        Gate::define('managePayrollSettings', fn (User $user): bool => $adminPermission($user, 'admin.payroll_settings.manage'));
        Gate::define('manageKpiSettings', fn (User $user): bool => $adminPermission($user, 'admin.kpi_settings.manage'));
        Gate::define('manageSystemSettings', fn (User $user): bool => $adminPermission($user, 'admin.settings.manage'));
        Gate::define('manageEnterpriseLicense', fn (User $user): bool => $adminPermission($user, 'admin.settings.license'));
        Gate::define('manageSystemMaintenance', fn (User $user): bool => $adminPermission($user, 'admin.system_maintenance.manage'));
        Gate::define('viewUserImportExport', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.view'));
        Gate::define('accessUserImportExport', fn (User $user): bool => $user->can('viewUserImportExport'));
        Gate::define('importUsers', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.import'));
        Gate::define('exportUsers', fn (User $user): bool => $adminPermission($user, 'admin.import_export_users.export'));
        Gate::define('viewAttendanceImportExport', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.view'));
        Gate::define('importAttendances', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.import'));
        Gate::define('exportAttendances', fn (User $user): bool => $adminPermission($user, 'admin.import_export_attendances.export'));
        Gate::define('viewActivityLogs', fn (User $user): bool => $adminPermission($user, 'admin.activity_logs.view'));
        Gate::define('exportActivityLogs', fn (User $user): bool => $adminPermission($user, 'admin.activity_logs.export'));
        Gate::define('viewAnalyticsDashboard', fn (User $user): bool => $adminPermission($user, 'admin.analytics.view'));
        Gate::define('exportAdminReports', fn (User $user): bool => $adminPermission($user, 'admin.attendances.export'));
        Gate::define('manageRbac', fn (User $user): bool => $user->canManageRbac());
        Gate::define('assignRoles', fn (User $user): bool => $user->canAssignRoles());
        Gate::define('manageUserRecord', function (User $user, ?User $subject = null, ?string $targetGroup = 'user'): bool {
            if ($user->isDemo && $targetGroup !== 'user') {
                return false;
            }

            if ($targetGroup === 'user') {
                return $user->allowsAdminPermission('admin.employees.manage');
            }

            if ($targetGroup === 'superadmin') {
                return $user->canManageSuperadminAccounts();
            }

            if ($targetGroup !== 'admin') {
                return false;
            }

            if ($subject?->isSuperadmin) {
                return $user->canManageSuperadminAccounts();
            }

            if ($user->canManageSuperadminAccounts()) {
                return true;
            }

            if ($subject !== null && $user->is($subject)) {
                return true;
            }

            if (! $user->allowsAdminPermission('admin.admin_accounts.manage')) {
                return false;
            }

            return true;
        });
    }
}
