<?php

use App\Http\Controllers\Admin\Attendance\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\Barcode\BarcodeController;
use App\Http\Controllers\Admin\Employees\EmployeeController;
use App\Http\Controllers\Admin\ImportExport\AttendancesPageController;
use App\Http\Controllers\Admin\ImportExport\DownloadImportExportRunController;
use App\Http\Controllers\Admin\ImportExport\ExportActivityLogsController;
use App\Http\Controllers\Admin\ImportExport\ExportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ExportReportPdfController;
use App\Http\Controllers\Admin\ImportExport\ExportUsersController;
use App\Http\Controllers\Admin\ImportExport\ImportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ImportUsersController;
use App\Http\Controllers\Admin\ImportExport\UsersPageController;
use App\Http\Controllers\Admin\MasterData\AdminController as MasterAdminController;
use App\Http\Controllers\Admin\MasterData\DivisionController;
use App\Http\Controllers\Admin\MasterData\EducationController;
use App\Http\Controllers\Admin\MasterData\JobTitleController;
use App\Http\Controllers\Admin\MasterData\ShiftController;
use App\Http\Controllers\Admin\Reports\ExportLeaveReportController;
use App\Http\Controllers\Admin\Reports\ExportOvertimeReportController;
use App\Http\Controllers\Admin\Reports\ExportPayrollReportController;
use App\Http\Controllers\Admin\Reports\ExportScheduleReportController;
use App\Http\Controllers\Admin\Reports\ReportCenterController;
use App\Http\Controllers\User\EmployeeDocumentDownloadController;
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\AssetManager;
use App\Livewire\Admin\AttendanceCorrectionManager;
use App\Livewire\Admin\DocumentTemplateManager;
use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\Admin\Finance\CashAdvanceManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\LeaveApproval;
use App\Livewire\Admin\MasterData\LeaveTypeManager;
use App\Livewire\Admin\NotificationsPage as AdminNotificationsPage;
use App\Livewire\Admin\OvertimeManager;
use App\Livewire\Admin\ReimbursementManager;
use App\Livewire\Admin\ScheduleComponent;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Settings\KpiSettings;
use App\Livewire\Admin\ShiftSwapApprovalManager;
use App\Models\Appraisal;
use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\CompanyAsset;
use App\Models\EmployeeDocumentRequest;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::get('/', fn () => redirect()->route(request()->user()?->preferredAdminRouteName() ?? 'home'))
            ->can('accessAdminPanel');

        Route::get('/dashboard', function (Request $request) {
            $user = $request->user();

            Log::info('Admin dashboard route reached.', [
                'path' => $request->path(),
                'user_id' => $user?->id,
                'group' => $user?->group,
                'can_access_admin_panel' => $user?->can('accessAdminPanel'),
                'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
                ...(config('auth.debug_log', false) ? [
                    'email' => $user?->email,
                    'roles' => $user?->roles()->pluck('slug')->all() ?? [],
                ] : []),
            ]);

            return response()
                ->view('admin.dashboard')
                ->header('X-Paspapan-Dashboard-Route', 'reached');
        })->name('admin.dashboard')->can('viewAdminDashboard');

        Route::resource('/barcodes', BarcodeController::class)
            ->only(['index', 'show', 'create', 'store', 'edit', 'update'])
            ->middleware('can:manageBarcodes')
            ->names([
                'index' => 'admin.barcodes',
                'show' => 'admin.barcodes.show',
                'create' => 'admin.barcodes.create',
                'store' => 'admin.barcodes.store',
                'edit' => 'admin.barcodes.edit',
                'update' => 'admin.barcodes.update',
            ]);

        Route::post('/barcodes/{barcode}/regenerate-secret', [BarcodeController::class, 'regenerateSecret'])->name('admin.barcodes.regenerate-secret')->can('manageBarcodes');
        Route::get('/barcodes/{barcode}/dynamic-display', [BarcodeController::class, 'dynamicDisplay'])->name('admin.barcodes.dynamic-display')->can('manageBarcodes');
        Route::get('/barcodes/{barcode}/dynamic-token', [BarcodeController::class, 'dynamicToken'])->name('admin.barcodes.dynamic-token')->can('manageBarcodes');
        Route::get('/barcodes/download/all', [BarcodeController::class, 'downloadAll'])->name('admin.barcodes.downloadall')->can('manageBarcodes');
        Route::get('/barcodes/{id}/download', [BarcodeController::class, 'download'])->name('admin.barcodes.download')->can('manageBarcodes');

        Route::resource('/employees', EmployeeController::class)
            ->only(['index'])
            ->middleware('can:viewEmployees')
            ->names(['index' => 'admin.employees']);

        Route::get('/masterdata/division', DivisionController::class)->name('admin.masters.division')->can('manageDivisions');
        Route::get('/masterdata/job-title', JobTitleController::class)->name('admin.masters.job-title')->can('manageJobTitles');
        Route::get('/masterdata/education', EducationController::class)->name('admin.masters.education')->can('manageEducations');
        Route::get('/masterdata/shift', ShiftController::class)->name('admin.masters.shift')->can('manageShifts');
        Route::get('/masterdata/leave-types', LeaveTypeManager::class)->name('admin.masters.leave-types')->can('manageLeaveTypes');
        Route::get('/masterdata/admin', MasterAdminController::class)->name('admin.masters.admin')->can('viewAdminAccounts');
        Route::get('/schedules', ScheduleComponent::class)->name('admin.schedules')->can('manageSchedules');
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('admin.attendances')->can('viewAdminAny', AttendanceRecord::class);
        Route::get('/attendance-corrections', AttendanceCorrectionManager::class)->name('admin.attendance-corrections')->can('viewAdminAny', AttendanceCorrection::class);
        Route::get('/document-requests', EmployeeDocumentRequestManager::class)->name('admin.document-requests')->can('viewAdminAny', EmployeeDocumentRequest::class);
        Route::get('/document-templates', DocumentTemplateManager::class)->name('admin.document-templates')->can('manageDocumentTemplates');
        Route::redirect('/document-templates/library', '/admin/document-templates')
            ->name('admin.document-templates.library')
            ->middleware('can:manageDocumentTemplates');
        Route::get('/document-requests/{documentRequest}/download', [EmployeeDocumentDownloadController::class, 'generated'])
            ->name('admin.document-requests.download')
            ->can('download', 'documentRequest');
        Route::get('/document-requests/{documentRequest}/uploaded', [EmployeeDocumentDownloadController::class, 'uploaded'])
            ->name('admin.document-requests.uploaded')
            ->can('downloadUpload', 'documentRequest');
        Route::get('/attendances/report', [AdminAttendanceController::class, 'report'])->name('admin.attendances.report')->can('viewAttendanceReports');
        Route::get('/import-export/users', UsersPageController::class)->name('admin.import-export.users')->middleware('feature.lock:reporting,admin.import_export_users.view,admin.dashboard')->can('viewUserImportExport');
        Route::get('/import-export/attendances', AttendancesPageController::class)->name('admin.import-export.attendances')->middleware('feature.lock:reporting,admin.import_export_attendances.view,admin.dashboard')->can('viewAttendanceImportExport');
        Route::post('/users/import', ImportUsersController::class)->name('admin.users.import')->middleware('feature.lock:reporting,admin.import_export_users.import,admin.dashboard')->can('importUsers');
        Route::post('/attendances/import', ImportAttendancesController::class)->name('admin.attendances.import')->middleware('feature.lock:reporting,admin.import_export_attendances.import,admin.dashboard')->can('importAttendances');
        Route::get('/users/export', ExportUsersController::class)->name('admin.users.export')->middleware('feature.lock:reporting,admin.import_export_users.export,admin.dashboard')->can('exportUsers');
        Route::get('/attendances/export', ExportAttendancesController::class)->name('admin.attendances.export')->middleware('feature.lock:reporting,admin.import_export_attendances.export,admin.dashboard')->can('exportAttendances');
        Route::get('/activity-logs/export', ExportActivityLogsController::class)->name('admin.activity-logs.export')->middleware('feature.lock:audit,admin.activity_logs.export,admin.dashboard')->can('exportActivityLogs');
        Route::get('/reports', ReportCenterController::class)->name('admin.reports.index')->can('viewOperationalReports');
        Route::get('/reports/leaves/export', ExportLeaveReportController::class)->name('admin.reports.leaves.export')->can('manageLeaveApprovals');
        Route::get('/reports/overtime/export', ExportOvertimeReportController::class)->name('admin.reports.overtime.export')->can('manageOvertime');
        Route::get('/reports/schedules/export', ExportScheduleReportController::class)->name('admin.reports.schedules.export')->can('manageSchedules');
        Route::get('/reports/payrolls/export', ExportPayrollReportController::class)->name('admin.reports.payrolls.export')->middleware('feature.lock:payroll,admin.payroll.view,admin.dashboard')->can('viewAdminPayroll');
        Route::get('/reports/export-pdf', ExportReportPdfController::class)->name('admin.reports.export-pdf')->middleware('feature.lock:reporting,admin.attendances.export,admin.dashboard')->can('exportAdminReports');
        Route::get('/import-export/runs/{run}/download', DownloadImportExportRunController::class)->name('admin.import-export.runs.download')->can('download', 'run');
        Route::get('/settings', Settings::class)->name('admin.settings')->can('viewAdminSettings');
        Route::get('/settings/kpi', KpiSettings::class)->name('admin.settings.kpi')->middleware('feature.lock:appraisal,admin.kpi_settings.manage,admin.dashboard')->can('manageKpiSettings');
        Route::get('/profile', fn () => view('profile.admin-show'))->name('admin.profile.show')->can('accessAdminPanel');
        Route::get('/leaves', LeaveApproval::class)->name('admin.leaves')->can('manageLeaveApprovals');
        Route::get('/shift-swaps', ShiftSwapApprovalManager::class)->name('admin.shift-swaps')->can('manageShiftSwapApprovals');
        Route::get('/overtime', OvertimeManager::class)->name('admin.overtime')->can('manageOvertime');
        Route::get('/notifications', AdminNotificationsPage::class)->name('admin.notifications')->can('manageAdminNotifications');
        Route::get('/analytics', AnalyticsDashboard::class)->name('admin.analytics')->middleware('feature.lock:analytics,admin.analytics.view,admin.dashboard')->can('viewAnalyticsDashboard');
        Route::get('/activity-logs', ActivityLogs::class)->name('admin.activity-logs')->middleware('feature.lock:audit,admin.activity_logs.view,admin.dashboard')->can('viewActivityLogs');
        Route::get('/holidays', HolidayManager::class)->name('admin.holidays')->can('manageHolidays');
        Route::get('/announcements', AnnouncementManager::class)->name('admin.announcements')->can('manageAnnouncements');
        Route::get('/reimbursements', ReimbursementManager::class)->name('admin.reimbursements')->can('viewAdminAny', Reimbursement::class);
        Route::get('/manage-kasbon', CashAdvanceManager::class)->name('admin.manage-kasbon')->middleware('feature.lock:cash_advance,admin.cash_advances.manage,admin.dashboard')->can('manageCashAdvances');
        Route::get('/assets', AssetManager::class)->name('admin.assets')->middleware('feature.lock:assets,admin.assets.view,admin.dashboard')->can('viewAdminAny', CompanyAsset::class);
        Route::get('/appraisals', AppraisalManager::class)->name('admin.appraisals')->middleware('feature.lock:appraisal,admin.appraisals.view,admin.dashboard')->can('viewAdminAny', Appraisal::class);
        Route::get('/roles-permissions', \App\Livewire\Admin\RolePermissionManager::class)->name('admin.roles.permissions')->can('manageRbac');
    });
});
