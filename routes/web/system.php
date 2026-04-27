<?php

use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\System\LanguageController;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\SystemBackupRun;
use App\Support\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

Route::match(['GET', 'POST'], '/__vercel-migrate', function (Request $request) {
    $expectedToken = (string) config('services.vercel.maintenance_token', '');
    $providedToken = (string) $request->input('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = Artisan::output();

    $seedExitCode = null;
    $seedOutput = null;

    if ($request->boolean('seed')) {
        $seedExitCode = Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = Artisan::output();
    }

    $connection = config('database.default');
    $connectionConfig = config("database.connections.{$connection}", []);

    return response()->json([
        'ok' => $migrateExitCode === 0 && ($seedExitCode === null || $seedExitCode === 0),
        'connection' => $connection,
        'host' => $connectionConfig['host'] ?? null,
        'database' => $connectionConfig['database'] ?? null,
        'migrate_exit_code' => $migrateExitCode,
        'migrate_output' => $migrateOutput,
        'seed_exit_code' => $seedExitCode,
        'seed_output' => $seedOutput,
    ], $migrateExitCode === 0 && ($seedExitCode === null || $seedExitCode === 0) ? 200 : 500);
});

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

    return Route::get(Helpers::getNonRootBaseUrlPath().$path, $handle);
});

Route::controller(LanguageController::class)->group(function () {
    Route::post('/user/language', 'update')->name('user.language.update');
});
