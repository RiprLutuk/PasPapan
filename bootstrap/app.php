<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->user()?->preferredHomeUrl() ?? '/');

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'throttle.ip' => \App\Http\Middleware\ThrottleRequestsByIP::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LogUserActivity::class,
            \App\Http\Middleware\EnsureSecurityHeaders::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\SetUserLocale::class,
            \App\Http\Middleware\EnsureActiveAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            $request = request();
            $user = $request->user();

            \Illuminate\Support\Facades\Log::warning('AuthorizationException rendered.', [
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

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $statusCode = $e->getStatusCode();

            if ($statusCode === 403) {
                $request = request();
                $user = $request->user();

                \Illuminate\Support\Facades\Log::warning('HTTP 403 rendered.', [
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
