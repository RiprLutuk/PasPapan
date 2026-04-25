<?php

use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\System\LanguageController;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\SystemBackupRun;
use App\Support\Helpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// Test Error Views. Keep this helper out of production so arbitrary users cannot
// trigger dedicated error responses on demand.
Route::get('/test-error/{code}', function ($code) {
    if (! app()->environment(['local', 'testing']) && ! config('app.debug')) {
        abort(404);
    }

    $code = (int) $code;
    if (! in_array($code, [401, 402, 403, 404, 405, 408, 413, 419, 429, 500, 502, 503, 504], true)) {
        abort(404);
    }

    abort($code);
})->whereNumber('code');

Route::post('/email/verify-code', VerifyEmailCodeController::class)
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code.verify');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', fn () => redirect()->route(Auth::user()?->preferredHomeRouteName() ?? 'home'));

    Route::prefix('admin')->middleware(['admin', 'can:accessAdminPanel'])->group(function () {
        Route::get('/system-maintenance', SystemMaintenance::class)
            ->name('admin.system-maintenance')
            ->can('viewAny', SystemBackupRun::class);
    });
});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post(Helpers::getNonRootBaseUrlPath().'/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    $path = config('app.debug') ? '/livewire/livewire.js' : '/livewire/livewire.min.js';

    return Route::get(url($path), $handle);
});

Route::controller(LanguageController::class)->group(function () {
    Route::post('/user/language', 'update')->name('user.language.update');
});
