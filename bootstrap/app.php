<?php

use App\Exceptions\EnterpriseObfuscatorKeyMissingException;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureSecurityHeaders;
use App\Http\Middleware\LogUserActivity;
use App\Http\Middleware\RedirectLockedEnterpriseFeature;
use App\Http\Middleware\SetUserLocale;
use App\Http\Middleware\ThrottleRequestsByIP;
use App\Http\Middleware\TrackRedisSessions;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\VerifyAttendanceIntegrationSignature;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust Cloudflare proxies for HTTPS detection
        $middleware->trustProxies(at: '*');
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->preferredHomeUrl() ?? '/');

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'user' => UserMiddleware::class,
            'ability' => CheckForAnyAbility::class,
            'abilities' => CheckAbilities::class,
            'feature.lock' => RedirectLockedEnterpriseFeature::class,
            'throttle.ip' => ThrottleRequestsByIP::class,
            'attendance.integration.signature' => VerifyAttendanceIntegrationSignature::class,
        ]);
        $middleware->preventRequestForgery(except: [
            '__vercel-migrate',
        ]);
        $middleware->web(append: [
            TrackRedisSessions::class,
            LogUserActivity::class,
            EnsureSecurityHeaders::class,
            CheckMaintenanceMode::class,
            SetUserLocale::class,
            EnsureActiveAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderEnterpriseRuntimeLock = function (Request $request) {
            Log::warning('Enterprise runtime obfuscator key is missing.', [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->id,
            ]);

            $payload = [
                'title' => __('Enterprise Runtime Locked'),
                'message' => __('Enterprise runtime is not fully configured. Please contact the administrator to complete the Enterprise deployment key setup.'),
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $payload['message'],
                    'feature_locked' => true,
                    'enterprise_runtime_locked' => true,
                ], 423);
            }

            $user = $request->user();
            $fallbackRoute = $user?->preferredAdminRouteName();

            if (is_string($fallbackRoute) && Route::has($fallbackRoute)) {
                return redirect()
                    ->route($fallbackRoute)
                    ->with('show-feature-lock', $payload)
                    ->with('flash.banner', $payload['message'])
                    ->with('flash.bannerStyle', 'warning');
            }

            return response(
                '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Enterprise Runtime Locked</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;color:#e5e7eb;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;padding:24px}main{max-width:520px;border:1px solid rgba(148,163,184,.18);border-radius:18px;background:rgba(15,23,42,.92);padding:28px;box-shadow:0 24px 80px rgba(0,0,0,.28)}h1{margin:0 0 10px;font-size:22px;line-height:1.25}p{margin:0;color:#cbd5e1;font-size:15px;line-height:1.7}</style></head><body><main><h1>Enterprise runtime belum lengkap</h1><p>Server ini belum memiliki deployment key yang diperlukan untuk menjalankan artifact Enterprise. Hubungi administrator untuk melengkapi konfigurasi runtime.</p></main></body></html>',
                503,
                [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                    'Pragma' => 'no-cache',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ],
            );
        };

        $exceptions->render(function (EnterpriseObfuscatorKeyMissingException $e, Request $request) use ($renderEnterpriseRuntimeLock) {
            return $renderEnterpriseRuntimeLock($request);
        });

        $exceptions->render(function (RuntimeException $e, Request $request) use ($renderEnterpriseRuntimeLock) {
            if ($e->getMessage() !== 'Enterprise obfuscator key is missing.') {
                return null;
            }

            return $renderEnterpriseRuntimeLock($request);
        });

        $exceptions->render(function (AuthorizationException $e) {
            $request = request();
            $user = $request->user();

            Log::warning('AuthorizationException rendered.', [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'user_id' => $user?->id,
                'email' => $user?->email,
                'group' => $user?->group,
                'roles' => $user?->roles()->pluck('slug')->all() ?? [],
                'is_admin' => $user?->isAdmin,
                'can_access_admin_panel' => $user?->can('accessAdminPanel'),
                'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
                'message' => $e->getMessage(),
            ]);

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $e) {
            $statusCode = $e->getStatusCode();

            if ($statusCode === 403) {
                $request = request();
                $user = $request->user();

                Log::warning('HTTP 403 rendered.', [
                    'path' => $request->path(),
                    'route' => $request->route()?->getName(),
                    'user_id' => $user?->id,
                    'email' => $user?->email,
                    'group' => $user?->group,
                    'roles' => $user?->roles()->pluck('slug')->all() ?? [],
                    'is_admin' => $user?->isAdmin,
                    'can_access_admin_panel' => $user?->can('accessAdminPanel'),
                    'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }

            // Check if a specific view exists for this status code
            if (view()->exists("errors.{$statusCode}")) {
                return null; // Let Laravel handle usage of that view
            }

            // Fallback to 404 for any other HTTP error
            return response()->view('errors.404', [], 404);
        });
    })
    ->create();

if ($storagePath = env('APP_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);

    foreach ([
        'app',
        'app/livewire-tmp',
        'app/import-export/uploads',
        'app/import-export/exports',
        'framework/cache/data',
        'framework/sessions',
        'framework/testing',
        'framework/views',
        'logs',
    ] as $directory) {
        $path = $storagePath.DIRECTORY_SEPARATOR.$directory;

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}

return $app;
