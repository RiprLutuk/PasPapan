<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            'throttle.ip' => \App\Http\Middleware\ThrottleRequestsByIP::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LogUserActivity::class,
            \App\Http\Middleware\EnsureSecurityHeaders::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\SetUserLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $statusCode = $e->getStatusCode();
            
            // Check if a specific view exists for this status code
            if (view()->exists("errors.{$statusCode}")) {
                return null; // Let Laravel handle usage of that view
            }

            // Fallback to 404 for any other HTTP error
            return response()->view('errors.404', [], 404);
        });
    })
    ->create();
