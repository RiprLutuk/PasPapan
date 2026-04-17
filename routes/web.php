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
use App\Http\Controllers\System\LanguageController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendancePhotoController;
use App\Http\Controllers\User\HomeController;
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppraisalManager;
use App\Livewire\Admin\AssetManager;
use App\Livewire\Admin\Finance\CashAdvanceManager;
use App\Livewire\Admin\HolidayManager;
use App\Livewire\Admin\LeaveApproval;
use App\Livewire\Admin\OvertimeManager;
use App\Livewire\Admin\PayrollManager;
use App\Livewire\Admin\PayrollSettings;
use App\Livewire\Admin\ReimbursementManager;
use App\Livewire\Admin\ScheduleComponent;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Settings\KpiSettings;
use App\Livewire\Admin\SystemMaintenance;
use App\Livewire\Shared\NotificationsPage;
use App\Livewire\User\FaceEnrollment;
use App\Livewire\User\Finance\MyCashAdvances;
use App\Livewire\User\Finance\TeamCashAdvanceManager;
use App\Livewire\User\MyAssets;
use App\Livewire\User\MyPayslips;
use App\Livewire\User\MyPerformance;
use App\Livewire\User\OvertimeRequest;
use App\Livewire\User\ReimbursementPage;
use App\Livewire\User\ShiftSchedulePage;
use App\Livewire\User\TeamApprovals;
use App\Livewire\User\TeamApprovalsHistory;
use App\Models\Appraisal;
use App\Models\Setting;
use App\Support\Helpers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

Route::get('/', function () {
    return redirect('/login');
});

Route::redirect('/offline', '/offline.html')->name('offline');

// Test Error Views
Route::get('/test-error/{code}', function ($code) {
    abort($code);
});

Route::controller(AttendancePhotoController::class)->middleware('auth')->group(function () {
    Route::get('/attendance/photo/{attendance}/{type}/{index?}', 'show')
        ->name('attendance.photo');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', fn() => Auth::user()->isAdmin ? redirect('/admin') : redirect('/home'));

    Route::get('/notifications', NotificationsPage::class)->name('notifications');

    // USER AREA
    Route::middleware('user')->group(function () {
        Route::get('/home', HomeController::class)->name('home');
        Route::controller(AttendanceController::class)->group(function () {
            Route::get('/apply-leave', 'applyLeave')->name('apply-leave');
            Route::post('/apply-leave', 'storeLeaveRequest')->name('store-leave-request');
            Route::get('/secure-attachment/{attendance}', 'downloadAttachment')->name('attendance.attachment.download');
            Route::get('/attendance-history', 'history')->name('attendance-history');
            Route::get('/scan', 'scan')->name('scan');
        });
        Route::get('/reimbursement', ReimbursementPage::class)->name('reimbursement');
        Route::get('/my-schedule', ShiftSchedulePage::class)->name('my-schedule');
        Route::get('/approvals', TeamApprovals::class)->name('approvals');
        Route::get('/approvals/history', TeamApprovalsHistory::class)->name('approvals.history');
        Route::get('/overtime', OvertimeRequest::class)->name('overtime');
        Route::get('/payroll', MyPayslips::class)->name('my-payslips');
        Route::get('/my-kasbon', MyCashAdvances::class)->name('my-kasbon');
        Route::get('/team-kasbon', TeamCashAdvanceManager::class)->name('team-kasbon');
        Route::get('/face-enrollment', FaceEnrollment::class)->name('face.enrollment');
        Route::get('/my-assets', MyAssets::class)->name('my-assets');
        Route::get('/my-performance', MyPerformance::class)->name('my-performance');
        Route::get('/appraisal/{appraisal}/export-pdf', function (Appraisal $appraisal) {
            if ($appraisal->user_id !== auth()->id() && !auth()->user()->isAdmin) {
                abort(403);
            }
            $appraisal->load(['user.division', 'user.jobTitle', 'evaluator', 'calibrator', 'evaluations.kpiTemplate']);
            $companyName = Setting::getValue('app.company_name', config('app.name'));
            $pdf = Pdf::loadView('pdf.appraisal-report', compact('appraisal', 'companyName'));
            return $pdf->download("appraisal-{$appraisal->user->name}-{$appraisal->period_month}-{$appraisal->period_year}.pdf");
        })->name('appraisal.export-pdf');
    });

    // ADMIN AREA
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/', fn() => redirect('/admin/dashboard'));
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
        Route::get('/barcodes/download/all', [BarcodeController::class, 'downloadAll'])->name('admin.barcodes.downloadall');
        Route::get('/barcodes/{id}/download', [BarcodeController::class, 'download'])->name('admin.barcodes.download');
        Route::resource('/employees', EmployeeController::class)->only(['index'])->names(['index' => 'admin.employees']);
        Route::get('/masterdata/division', DivisionController::class)->name('admin.masters.division');
        Route::get('/masterdata/job-title', JobTitleController::class)->name('admin.masters.job-title');
        Route::get('/masterdata/education', EducationController::class)->name('admin.masters.education');
        Route::get('/masterdata/shift', ShiftController::class)->name('admin.masters.shift');
        Route::get('/masterdata/admin', MasterAdminController::class)->name('admin.masters.admin');
        Route::get('/schedules', ScheduleComponent::class)->name('admin.schedules');
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('admin.attendances');
        Route::get('/attendances/report', [AdminAttendanceController::class, 'report'])->name('admin.attendances.report');
        Route::get('/import-export/users', UsersPageController::class)->name('admin.import-export.users');
        Route::get('/import-export/attendances', AttendancesPageController::class)->name('admin.import-export.attendances');
        Route::post('/users/import', ImportUsersController::class)->name('admin.users.import');
        Route::post('/attendances/import', ImportAttendancesController::class)->name('admin.attendances.import');
        Route::get('/users/export', ExportUsersController::class)->name('admin.users.export');
        Route::get('/attendances/export', ExportAttendancesController::class)->name('admin.attendances.export');
        Route::get('/activity-logs/export', ExportActivityLogsController::class)->name('admin.activity-logs.export');
        Route::get('/reports/export-pdf', ExportReportPdfController::class)->name('admin.reports.export-pdf');
        Route::get('/settings', Settings::class)->name('admin.settings');
        Route::get('/settings/kpi', KpiSettings::class)->name('admin.settings.kpi');
        Route::get('/system-maintenance', SystemMaintenance::class)->name('admin.system-maintenance');
        Route::get('/leaves', LeaveApproval::class)->name('admin.leaves');
        Route::get('/overtime', OvertimeManager::class)->name('admin.overtime');
        Route::get('/analytics', AnalyticsDashboard::class)->name('admin.analytics');
        Route::get('/activity-logs', ActivityLogs::class)->name('admin.activity-logs');
        Route::get('/holidays', HolidayManager::class)->name('admin.holidays');
        Route::get('/announcements', AnnouncementManager::class)->name('admin.announcements');
        Route::get('/reimbursements', ReimbursementManager::class)->name('admin.reimbursements');
        Route::get('/payrolls/settings', PayrollSettings::class)->name('admin.payroll.settings');
        Route::get('/payrolls', PayrollManager::class)->name('admin.payrolls');
        Route::get('/manage-kasbon', CashAdvanceManager::class)->name('admin.manage-kasbon');
        Route::get('/assets', AssetManager::class)->name('admin.assets');
        Route::get('/appraisals', AppraisalManager::class)->name('admin.appraisals');
    });
});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post(Helpers::getNonRootBaseUrlPath() . '/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    $path = config('app.debug') ? '/livewire/livewire.js' : '/livewire/livewire.min.js';
    return Route::get(url($path), $handle);
});

// Public Language Route
Route::controller(LanguageController::class)->group(function () {
    Route::post('/user/language', 'update')->name('user.language.update');
});
