<?php

use App\Http\Controllers\Admin\Attendance\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\Barcode\BarcodeController;
use App\Http\Controllers\Admin\Employees\EmployeeController;
use App\Http\Controllers\Admin\ImportExport\AttendancesPageController;
use App\Http\Controllers\Admin\ImportExport\ExportActivityLogsController;
use App\Http\Controllers\Admin\ImportExport\ExportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ExportReportPdfController;
use App\Http\Controllers\Admin\ImportExport\ExportUsersController;
use App\Http\Controllers\Admin\ImportExport\DownloadImportExportRunController;
use App\Http\Controllers\Admin\ImportExport\ImportAttendancesController;
use App\Http\Controllers\Admin\ImportExport\ImportUsersController;
use App\Http\Controllers\Admin\ImportExport\UsersPageController;
use App\Http\Controllers\Admin\MasterData\AdminController as MasterAdminController;
use App\Http\Controllers\Admin\MasterData\DivisionController;
use App\Http\Controllers\Admin\MasterData\EducationController;
use App\Http\Controllers\Admin\MasterData\JobTitleController;
use App\Http\Controllers\Admin\MasterData\ShiftController;
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\AssetManager;
use App\Livewire\Admin\AttendanceCorrectionManager;
use App\Livewire\Admin\Finance\CashAdvanceManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\LeaveApproval;
use App\Livewire\Admin\NotificationsPage as AdminNotificationsPage;
use App\Livewire\Admin\OvertimeManager;
use App\Livewire\Admin\ReimbursementManager;
use App\Livewire\Admin\ScheduleComponent;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Settings\KpiSettings;
use App\Models\Appraisal;
use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\CompanyAsset;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::prefix('admin')->middleware(['admin', 'can:accessAdminPanel'])->group(function () {
        Route::get('/', fn () => redirect('/admin/dashboard'));

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
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

        Route::get('/masterdata/division', DivisionController::class)->name('admin.masters.division')->can('manageMasterData');
        Route::get('/masterdata/job-title', JobTitleController::class)->name('admin.masters.job-title')->can('manageMasterData');
        Route::get('/masterdata/education', EducationController::class)->name('admin.masters.education')->can('manageMasterData');
        Route::get('/masterdata/shift', ShiftController::class)->name('admin.masters.shift')->can('manageMasterData');
        Route::get('/masterdata/admin', MasterAdminController::class)->name('admin.masters.admin')->can('manageMasterData');
        Route::get('/schedules', ScheduleComponent::class)->name('admin.schedules')->can('manageSchedules');
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('admin.attendances')->can('viewAdminAny', AttendanceRecord::class);
        Route::get('/attendance-corrections', AttendanceCorrectionManager::class)->name('admin.attendance-corrections')->can('viewAdminAny', AttendanceCorrection::class);
        Route::get('/attendances/report', [AdminAttendanceController::class, 'report'])->name('admin.attendances.report')->can('viewAdminAny', AttendanceRecord::class);
        Route::get('/import-export/users', UsersPageController::class)->name('admin.import-export.users')->can('accessUserImportExport');
        Route::get('/import-export/attendances', AttendancesPageController::class)->name('admin.import-export.attendances')->can('viewAdminAny', AttendanceRecord::class);
        Route::post('/users/import', ImportUsersController::class)->name('admin.users.import')->can('accessUserImportExport');
        Route::post('/attendances/import', ImportAttendancesController::class)->name('admin.attendances.import')->can('viewAdminAny', AttendanceRecord::class);
        Route::get('/users/export', ExportUsersController::class)->name('admin.users.export')->can('exportUsers');
        Route::get('/attendances/export', ExportAttendancesController::class)->name('admin.attendances.export')->can('viewAdminAny', AttendanceRecord::class);
        Route::get('/activity-logs/export', ExportActivityLogsController::class)->name('admin.activity-logs.export')->can('exportActivityLogs');
        Route::get('/reports/export-pdf', ExportReportPdfController::class)->name('admin.reports.export-pdf')->can('exportAdminReports');
        Route::get('/import-export/runs/{run}/download', DownloadImportExportRunController::class)->name('admin.import-export.runs.download')->can('download', 'run');
        Route::get('/settings', Settings::class)->name('admin.settings')->can('viewAdminSettings');
        Route::get('/settings/kpi', KpiSettings::class)->name('admin.settings.kpi')->can('manageKpiSettings');
        Route::get('/leaves', LeaveApproval::class)->name('admin.leaves')->can('manageLeaveApprovals');
        Route::get('/overtime', OvertimeManager::class)->name('admin.overtime')->can('manageOvertime');
        Route::get('/notifications', AdminNotificationsPage::class)->name('admin.notifications')->can('manageAdminNotifications');
        Route::get('/analytics', AnalyticsDashboard::class)->name('admin.analytics')->can('viewAnalyticsDashboard');
        Route::get('/activity-logs', ActivityLogs::class)->name('admin.activity-logs')->can('viewActivityLogs');
        Route::get('/holidays', HolidayManager::class)->name('admin.holidays')->can('manageHolidays');
        Route::get('/announcements', AnnouncementManager::class)->name('admin.announcements')->can('manageAnnouncements');
        Route::get('/reimbursements', ReimbursementManager::class)->name('admin.reimbursements')->can('viewAdminAny', Reimbursement::class);
        Route::get('/manage-kasbon', CashAdvanceManager::class)->name('admin.manage-kasbon')->can('manageCashAdvances');
        Route::get('/assets', AssetManager::class)->name('admin.assets')->can('viewAdminAny', CompanyAsset::class);
        Route::get('/appraisals', AppraisalManager::class)->name('admin.appraisals')->can('viewAdminAny', Appraisal::class);
    });
});
