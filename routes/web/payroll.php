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
            ->can('viewAny', Payroll::class);
    });

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/payrolls/settings', PayrollSettings::class)
            ->name('admin.payroll.settings')
            ->can('viewAny', Payroll::class);

        Route::get('/payrolls', PayrollManager::class)
            ->name('admin.payrolls')
            ->can('viewAny', Payroll::class);
    });
});
