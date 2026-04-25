<?php

use App\Http\Controllers\User\AppraisalExportPdfController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\HomeController;
use App\Livewire\User\AttendanceCorrectionPage;
use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Livewire\User\FaceEnrollment;
use App\Livewire\User\Finance\MyCashAdvances;
use App\Livewire\User\Finance\TeamCashAdvanceManager;
use App\Livewire\User\MyAssets;
use App\Livewire\User\MyPerformance;
use App\Livewire\User\NotificationsPage as UserNotificationsPage;
use App\Livewire\User\OvertimeRequest;
use App\Livewire\User\ReimbursementPage;
use App\Livewire\User\ShiftSchedulePage;
use App\Livewire\User\ShiftSwapRequestPage;
use App\Livewire\User\TeamApprovals;
use App\Livewire\User\TeamApprovalsHistory;
use App\Models\Appraisal;
use App\Models\Attendance as AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\CompanyAsset;
use App\Models\EmployeeDocumentRequest;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/notifications', UserNotificationsPage::class)->name('notifications');

    Route::middleware('user')->group(function () {
        Route::get('/home', HomeController::class)->name('home');

        Route::controller(AttendanceController::class)->group(function () {
            Route::get('/apply-leave', 'applyLeave')->name('apply-leave')->can('create', AttendanceRecord::class);
            Route::post('/apply-leave', 'storeLeaveRequest')->name('store-leave-request')->can('create', AttendanceRecord::class);
            Route::get('/attendance-history', 'history')->name('attendance-history')->can('viewAny', AttendanceRecord::class);
            Route::get('/scan', 'scan')->name('scan')->can('create', AttendanceRecord::class);
        });

        Route::get('/attendance-corrections', AttendanceCorrectionPage::class)
            ->name('attendance-corrections')
            ->can('viewAny', AttendanceCorrection::class);

        Route::get('/reimbursement', ReimbursementPage::class)
            ->name('reimbursement')
            ->can('viewAny', Reimbursement::class);

        Route::get('/my-schedule', ShiftSchedulePage::class)->name('my-schedule');
        Route::get('/shift-swap-requests', ShiftSwapRequestPage::class)
            ->name('shift-swap-requests')
            ->can('viewAny', \App\Models\ShiftSwapRequest::class);
        Route::get('/document-requests', EmployeeDocumentRequestPage::class)
            ->name('document-requests')
            ->can('viewAny', EmployeeDocumentRequest::class);
        Route::get('/approvals', TeamApprovals::class)
            ->name('approvals')
            ->can('reviewSubordinateRequests');
        Route::get('/approvals/history', TeamApprovalsHistory::class)
            ->name('approvals.history')
            ->can('reviewSubordinateRequests');
        Route::get('/overtime', OvertimeRequest::class)->name('overtime');
        Route::get('/my-kasbon', MyCashAdvances::class)->name('my-kasbon');
        Route::get('/team-kasbon', TeamCashAdvanceManager::class)
            ->name('team-kasbon')
            ->can('reviewSubordinateRequests');
        Route::get('/face-enrollment', FaceEnrollment::class)->name('face.enrollment');
        Route::get('/my-assets', MyAssets::class)->name('my-assets')->can('viewAny', CompanyAsset::class);
        Route::get('/my-performance', MyPerformance::class)->name('my-performance')->can('viewAny', Appraisal::class);
        Route::get('/appraisal/{appraisal}/export-pdf', AppraisalExportPdfController::class)
            ->name('appraisal.export-pdf')
            ->can('exportPdf', 'appraisal');
    });
});
