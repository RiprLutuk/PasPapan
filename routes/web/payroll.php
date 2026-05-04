<?php

use App\Livewire\Admin\PayrollManager;
use App\Livewire\Admin\PayrollSettings;
use App\Livewire\User\MyPayslips;
use App\Models\Payroll;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::middleware('user')->group(function () {
        Route::get('/payroll', MyPayslips::class)
            ->name('my-payslips')
            ->middleware('feature.lock:payroll,user,home')
            ->can('viewAny', Payroll::class);
    });

    Route::prefix('admin')->middleware(['admin', 'can:accessAdminPanel'])->group(function () {
        Route::get('/payrolls/settings', PayrollSettings::class)
            ->name('admin.payroll.settings')
            ->middleware('feature.lock:payroll,admin.payroll_settings.manage,admin.dashboard')
            ->can('managePayrollSettings');

        Route::get('/payrolls', PayrollManager::class)
            ->name('admin.payrolls')
            ->middleware('feature.lock:payroll,admin.payroll.view,admin.dashboard')
            ->can('viewAdminAny', Payroll::class);
    });
});
