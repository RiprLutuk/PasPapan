<?php

use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\System\AuthDebugController;
use App\Http\Controllers\System\E2eDocumentUploadController;
use App\Http\Controllers\System\E2eLoginController;
use App\Http\Controllers\System\EnterpriseSupportController;
use App\Http\Controllers\System\LanguageController;
use App\Http\Controllers\System\ResetServiceWorkerController;
use App\Http\Controllers\System\RootRedirectController;
use App\Http\Controllers\System\TestErrorController;
use App\Http\Controllers\System\VercelMaintenanceController;
use App\Models\SystemBackupRun;
use App\Support\Helpers;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// Test Error Views. Keep this helper out of production so arbitrary users cannot
// trigger dedicated error responses on demand.
Route::get('/test-error/{code}', TestErrorController::class)->whereNumber('code');
Route::get('/reset-sw', ResetServiceWorkerController::class);

Route::get('/__auth-debug', AuthDebugController::class)->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
]);

Route::post('/__e2e-login', E2eLoginController::class)->middleware('throttle:6,1');

Route::post('/__e2e-document-upload', E2eDocumentUploadController::class)->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]);

Route::post('/email/verify-code', VerifyEmailCodeController::class)
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code.verify');

Route::get('/enterprise-support/whatsapp', EnterpriseSupportController::class)
    ->middleware('throttle:10,1')
    ->name('enterprise-support.whatsapp');

Route::post('/__vercel-migrate', VercelMaintenanceController::class)
    ->middleware('throttle:3,1')
    ->withoutMiddleware([PreventRequestForgery::class]);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', RootRedirectController::class);

    Route::prefix('admin')->middleware(['admin'])->group(function () {
        enterprise_livewire_route('/system-maintenance', 'admin.system-maintenance')
            ->name('admin.system-maintenance')
            ->middleware('feature.lock:system_maintenance,admin.system_maintenance.view,admin.dashboard')
            ->can('viewAny', SystemBackupRun::class);
    });
});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post(Helpers::getNonRootBaseUrlPath().'/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    $path = config('app.debug') ? '/livewire/livewire.js' : '/livewire/livewire.min.js';

    return Route::get(Helpers::getNonRootBaseUrlPath().$path, $handle);
});

Route::controller(LanguageController::class)->group(function () {
    Route::post('/user/language', 'update')->name('user.language.update');
});
