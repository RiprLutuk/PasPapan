<?php

use App\Helpers;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserAttendanceController;
use App\Http\Controllers\AttendancePhotoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

Route::get('/', function () {
    return redirect('/login');
});

Route::view('/offline', 'offline')->name('offline');

// Test Error Views
Route::get('/test-error/{code}', function ($code) {
    abort($code);
});


Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/photo/{attendance}/{type}/{index?}', [AttendancePhotoController::class, 'show'])
        ->name('attendance.photo');
});


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', fn () => Auth::user()->isAdmin ? redirect('/admin') : redirect('/home'));

    // USER AREA
    Route::middleware('user')->group(function () {
        Route::get('/home', HomeController::class)->name('home');

        Route::get('/apply-leave', [UserAttendanceController::class, 'applyLeave'])
            ->name('apply-leave');
        Route::post('/apply-leave', [UserAttendanceController::class, 'storeLeaveRequest'])
            ->name('store-leave-request');

        // Enterprise V2.0: Secure Attachment Download
        Route::get('/secure-attachment/{attendance}', [UserAttendanceController::class, 'downloadAttachment'])
            ->name('attendance.attachment.download');
        


        Route::get('/attendance-history', [UserAttendanceController::class, 'history'])
            ->name('attendance-history');
            
        Route::get('/scan', [UserAttendanceController::class, 'scan'])
            ->name('scan');

        Route::get('/notifications', \App\Livewire\NotificationsPage::class)
            ->name('notifications');

        Route::get('/reimbursement', \App\Livewire\ReimbursementPage::class)
            ->name('reimbursement');

        Route::get('/my-schedule', \App\Livewire\ShiftSchedulePage::class)
            ->name('my-schedule');
            
        Route::get('/approvals', \App\Livewire\TeamApprovals::class)
            ->name('approvals');
            
        Route::get('/approvals/history', \App\Livewire\TeamApprovalsHistory::class)
            ->name('approvals.history');

        Route::get('/overtime', \App\Livewire\OvertimeRequest::class)
            ->name('overtime');

        Route::get('/payroll', \App\Livewire\MyPayslips::class)
            ->name('my-payslips');

        Route::get('/face-enrollment', \App\Livewire\FaceEnrollment::class)
            ->name('face.enrollment');
    });

    // ADMIN AREA
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/', fn () => redirect('/admin/dashboard'));
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        // Barcode
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
        Route::get('/barcodes/download/all', [BarcodeController::class, 'downloadAll'])
            ->name('admin.barcodes.downloadall');
        Route::get('/barcodes/{id}/download', [BarcodeController::class, 'download'])
            ->name('admin.barcodes.download');

        // User/Employee/Karyawan
        Route::resource('/employees', EmployeeController::class)
            ->only(['index'])
            ->names(['index' => 'admin.employees']);

        // Master Data
        Route::get('/masterdata/division', [MasterDataController::class, 'division'])
            ->name('admin.masters.division');
        Route::get('/masterdata/job-title', [MasterDataController::class, 'jobTitle'])
            ->name('admin.masters.job-title');
        Route::get('/masterdata/education', [MasterDataController::class, 'education'])
            ->name('admin.masters.education');
        Route::get('/masterdata/shift', [MasterDataController::class, 'shift'])
            ->name('admin.masters.shift');
        Route::get('/masterdata/admin', [MasterDataController::class, 'admin'])
            ->name('admin.masters.admin');
        Route::get('/schedules', \App\Livewire\Admin\ScheduleComponent::class)
            ->name('admin.schedules');

        // Presence/Absensi
        Route::get('/attendances', [AttendanceController::class, 'index'])
            ->name('admin.attendances');

        // Presence/Absensi
        Route::get('/attendances/report', [AttendanceController::class, 'report'])
            ->name('admin.attendances.report');

        // Import/Export
        Route::get('/import-export/users', [ImportExportController::class, 'users'])
            ->name('admin.import-export.users');
        Route::get('/import-export/attendances', [ImportExportController::class, 'attendances'])
            ->name('admin.import-export.attendances');

        Route::post('/users/import', [ImportExportController::class, 'importUsers'])
            ->name('admin.users.import');
        Route::post('/attendances/import', [ImportExportController::class, 'importAttendances'])
            ->name('admin.attendances.import');

        Route::get('/users/export', [ImportExportController::class, 'exportUsers'])
            ->name('admin.users.export');
        Route::get('/attendances/export', [ImportExportController::class, 'exportAttendances'])
            ->name('admin.attendances.export');
        Route::get('/activity-logs/export', [ImportExportController::class, 'exportActivityLogs'])
            ->name('admin.activity-logs.export');
        Route::get('/reports/export-pdf', [ImportExportController::class, 'exportReportPdf'])
            ->name('admin.reports.export-pdf');

        // Settings
        Route::get('/settings', \App\Livewire\Admin\Settings::class)
            ->name('admin.settings');
        
        Route::get('/system-maintenance', \App\Livewire\Admin\SystemMaintenance::class)
            ->name('admin.system-maintenance');

        Route::get('/leaves', \App\Livewire\Admin\LeaveApproval::class)
            ->name('admin.leaves');

        Route::get('/overtime', \App\Livewire\Admin\OvertimeManager::class)
            ->name('admin.overtime');
        
        Route::get('/analytics', \App\Livewire\Admin\AnalyticsDashboard::class)
            ->name('admin.analytics');
            
        Route::get('/activity-logs', \App\Livewire\Admin\ActivityLogs::class)
            ->name('admin.activity-logs');
        
        // Holidays & Announcements (v1.3.0)
        Route::get('/holidays', \App\Livewire\Admin\HolidayManager::class)
            ->name('admin.holidays');
        Route::get('/announcements', \App\Livewire\Admin\AnnouncementManager::class)
            ->name('admin.announcements');
        
        // Reimbursements (v1.3.0)
        Route::get('/reimbursements', \App\Livewire\Admin\ReimbursementManager::class)
            ->name('admin.reimbursements');
            
        // Payroll Settings
        Route::get('/payrolls/settings', \App\Livewire\Admin\PayrollSettings::class)
            ->name('admin.payroll.settings');

        // Payroll (v2.0)
        Route::get('/payrolls', \App\Livewire\PayrollManager::class)
            ->name('admin.payrolls');
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
Route::post('/user/language', [\App\Http\Controllers\LanguageController::class, 'update'])->name('user.language.update');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Other auth routes...
});

