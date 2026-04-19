<?php

use App\Http\Controllers\Admin\Attendance\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\Barcode\BarcodeController;
use App\Http\Controllers\Admin\Employees\EmployeeController;
use App\Http\Controllers\Admin\ImportExport\AttendancesPageController;
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
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\AssetManager;
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
use App\Models\CompanyAsset;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect('/admin/dashboard'));

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::resource('/barcodes', BarcodeController::class)
            ->only(['index', 'show', 'create', 'store', 'edit', 'update'])
            ->names([
                'index' => 'admin.barcodes',
                'show' => 'admin.barcodes.show',
                'create' => 'admin.barcodes.create',
                'store' => 'admin.barcodes.store',
                'edit' => 'admin.barcodes.edit',
                'update' => 'admin.barcodes.update',
            ]);

        Route::post('/barcodes/{barcode}/regenerate-secret', [BarcodeController::class, 'regenerateSecret'])->name('admin.barcodes.regenerate-secret');
        Route::get('/barcodes/{barcode}/dynamic-display', [BarcodeController::class, 'dynamicDisplay'])->name('admin.barcodes.dynamic-display');
        Route::get('/barcodes/{barcode}/dynamic-token', [BarcodeController::class, 'dynamicToken'])->name('admin.barcodes.dynamic-token');
        Route::get('/barcodes/download/all', [BarcodeController::class, 'downloadAll'])->name('admin.barcodes.downloadall');
        Route::get('/barcodes/{id}/download', [BarcodeController::class, 'download'])->name('admin.barcodes.download');

        Route::resource('/employees', EmployeeController::class)
            ->only(['index'])
            ->names(['index' => 'admin.employees']);

        Route::get('/masterdata/division', DivisionController::class)->name('admin.masters.division');
        Route::get('/masterdata/job-title', JobTitleController::class)->name('admin.masters.job-title');
        Route::get('/masterdata/education', EducationController::class)->name('admin.masters.education');
        Route::get('/masterdata/shift', ShiftController::class)->name('admin.masters.shift');
        Route::get('/masterdata/admin', MasterAdminController::class)->name('admin.masters.admin');
        Route::get('/schedules', ScheduleComponent::class)->name('admin.schedules');
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('admin.attendances')->can('viewAny', AttendanceRecord::class);
        Route::get('/attendances/report', [AdminAttendanceController::class, 'report'])->name('admin.attendances.report')->can('viewAny', AttendanceRecord::class);
        Route::get('/import-export/users', UsersPageController::class)->name('admin.import-export.users');
        Route::get('/import-export/attendances', AttendancesPageController::class)->name('admin.import-export.attendances')->can('viewAny', AttendanceRecord::class);
        Route::post('/users/import', ImportUsersController::class)->name('admin.users.import');
        Route::post('/attendances/import', ImportAttendancesController::class)->name('admin.attendances.import')->can('viewAny', AttendanceRecord::class);
        Route::get('/users/export', ExportUsersController::class)->name('admin.users.export');
        Route::get('/attendances/export', ExportAttendancesController::class)->name('admin.attendances.export')->can('viewAny', AttendanceRecord::class);
        Route::get('/activity-logs/export', ExportActivityLogsController::class)->name('admin.activity-logs.export');
        Route::get('/reports/export-pdf', ExportReportPdfController::class)->name('admin.reports.export-pdf');
        Route::get('/settings', Settings::class)->name('admin.settings');
        Route::get('/settings/kpi', KpiSettings::class)->name('admin.settings.kpi');
        Route::get('/leaves', LeaveApproval::class)->name('admin.leaves');
        Route::get('/overtime', OvertimeManager::class)->name('admin.overtime');
        Route::get('/notifications', AdminNotificationsPage::class)->name('admin.notifications');
        Route::get('/analytics', AnalyticsDashboard::class)->name('admin.analytics');
        Route::get('/activity-logs', ActivityLogs::class)->name('admin.activity-logs');
        Route::get('/holidays', HolidayManager::class)->name('admin.holidays');
        Route::get('/announcements', AnnouncementManager::class)->name('admin.announcements');
        Route::get('/reimbursements', ReimbursementManager::class)->name('admin.reimbursements')->can('viewAny', Reimbursement::class);
        Route::get('/manage-kasbon', CashAdvanceManager::class)->name('admin.manage-kasbon');
        Route::get('/assets', AssetManager::class)->name('admin.assets')->can('viewAny', CompanyAsset::class);
        Route::get('/appraisals', AppraisalManager::class)->name('admin.appraisals')->can('viewAny', Appraisal::class);
    });
});
