<?php

use App\Http\Controllers\Auth\VerifyEmailCodeController;
use App\Http\Controllers\System\LanguageController;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\EmployeeDocumentRequest;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\EmployeeDocumentRequestService;
use App\Support\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

Route::get('/reset-sw', function () {
    return response(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Reset App Cache</title>
</head>
<body>
    <p>Resetting app cache...</p>
    <script>
        (async () => {
            try {
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(registrations.map((registration) => registration.unregister()));
                }

                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
                }
            } finally {
                window.location.replace('/login?sw-reset=done');
            }
        })();
    </script>
</body>
</html>
HTML)
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Clear-Site-Data', '"cache", "storage"');
});

Route::get('/__auth-debug', function (Request $request) {
    if (! app()->environment(['local', 'testing']) && ! config('app.debug')) {
        abort(404);
    }

    $user = $request->user();
    $adminDashboardRoute = Route::getRoutes()->getByName('admin.dashboard');
    $payload = [
        'authenticated' => $user !== null,
        'id' => $user?->id,
        'email' => $user?->email,
        'group' => $user?->group,
        'roles' => $user?->roles()->get(['roles.id', 'name', 'slug', 'permissions', 'is_super_admin'])
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_super_admin' => (bool) $role->is_super_admin,
                'permission_count' => count($role->permissions ?? []),
                'has_dashboard_permission' => in_array('admin.dashboard.view', $role->permissions ?? [], true),
            ])
            ->all() ?? [],
        'is_admin' => $user?->isAdmin,
        'is_user' => $user?->isUser,
        'can_access_admin_panel' => $user?->can('accessAdminPanel'),
        'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
        'preferred_home_url' => $user?->preferredHomeUrl(),
        'session_id' => $request->session()->getId(),
        'intended_url' => $request->session()->get('url.intended'),
        'admin_dashboard_route' => [
            'uri' => $adminDashboardRoute?->uri(),
            'name' => $adminDashboardRoute?->getName(),
            'middleware' => $adminDashboardRoute?->gatherMiddleware() ?? [],
        ],
        'app' => [
            'env' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'url' => config('app.url'),
            'base_path' => base_path(),
        ],
    ];

    Log::info('Auth debug endpoint viewed.', $payload);

    return response()->json($payload)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
]);

Route::get('/__e2e-login', function (Request $request) {
    if (! app()->environment(['local', 'testing'])) {
        abort(404);
    }

    $expectedToken = (string) config('services.e2e.login_token', 'local-apk-e2e');
    $providedToken = (string) $request->query('token', '');

    if (! hash_equals($expectedToken, $providedToken)) {
        abort(403);
    }

    $user = User::query()->where('email', (string) $request->query('email', ''))->firstOrFail();

    if (! $user->canAuthenticate()) {
        abort(403);
    }

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->to((string) $request->query('to', '/home'));
});

Route::post('/__e2e-document-upload', function (Request $request, EmployeeDocumentRequestService $documentRequests) {
    if (! app()->environment(['local', 'testing'])) {
        abort(404);
    }

    $expectedToken = (string) config('services.e2e.login_token', 'local-apk-e2e');
    $providedToken = (string) $request->input('token', '');

    if (! hash_equals($expectedToken, $providedToken)) {
        abort(403);
    }

    $validated = $request->validate([
        'request_id' => ['required', 'integer', 'exists:employee_document_requests,id'],
        'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:10240'],
    ]);

    $documentRequest = EmployeeDocumentRequest::query()->findOrFail($validated['request_id']);
    abort_unless($request->user()?->can('upload', $documentRequest), 403);

    $message = $documentRequests->upload($documentRequest, $request->user(), $validated['attachment']);

    return response()->json([
        'ok' => true,
        'request_id' => $documentRequest->id,
        'message' => $message,
    ]);
})->middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
]);

Route::post('/email/verify-code', VerifyEmailCodeController::class)
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.code.verify');

Route::get('/enterprise-support/whatsapp', function (Request $request) {
    $supportNumber = preg_replace('/\D+/', '', (string) config('services.whatsapp.support_number', ''));

    if ($supportNumber === '') {
        abort(404);
    }

    $message = trim((string) $request->query('text', ''));
    $message = mb_substr($message, 0, 3500);
    $targetUrl = 'https://wa.me/'.$supportNumber;

    if ($message !== '') {
        $targetUrl .= '?text='.rawurlencode($message);
    }

    return redirect()->away($targetUrl);
})->middleware('throttle:10,1')->name('enterprise-support.whatsapp');

Route::post('/__vercel-migrate', function (Request $request) {
    if (! config('services.vercel.maintenance_endpoint_enabled', false)) {
        Log::warning('Vercel maintenance endpoint rejected.', [
            'reason' => 'endpoint_disabled',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        abort(404);
    }

    $expectedToken = (string) config('services.vercel.maintenance_token', '');
    $providedToken = (string) $request->input('token', '');
    $seedRequested = $request->boolean('seed');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        Log::warning('Vercel maintenance endpoint rejected.', [
            'reason' => $expectedToken === '' ? 'missing_configured_token' : 'invalid_token',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        abort(404);
    }

    if ($seedRequested && ! config('services.vercel.allow_web_seed', false)) {
        Log::warning('Vercel maintenance endpoint rejected.', [
            'reason' => 'seed_not_allowed',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        abort(403);
    }

    Log::info('Vercel maintenance migration started.', [
        'seed' => $seedRequested,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = Artisan::output();

    $seedExitCode = null;
    $seedOutput = null;

    if ($seedRequested) {
        $seedExitCode = Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = Artisan::output();
    }

    $ok = $migrateExitCode === 0 && ($seedExitCode === null || $seedExitCode === 0);

    Log::info('Vercel maintenance migration finished.', [
        'ok' => $ok,
        'seed' => $seedRequested,
        'migrate_exit_code' => $migrateExitCode,
        'seed_exit_code' => $seedExitCode,
    ]);

    $payload = [
        'ok' => $ok,
        'migrate_exit_code' => $migrateExitCode,
        'seed_exit_code' => $seedExitCode,
    ];

    if (! app()->environment('production')) {
        $payload['migrate_output'] = $migrateOutput;
        $payload['seed_output'] = $seedOutput;
    }

    return response()->json($payload, $ok ? 200 : 500);
})->middleware('throttle:3,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', fn () => redirect()->route(Auth::user()?->preferredHomeRouteName() ?? 'home'));

    Route::prefix('admin')->middleware(['admin'])->group(function () {
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
