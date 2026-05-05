<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $context = [
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'user_id' => $user?->id,
            'group' => $user?->group,
            'is_admin' => $user?->isAdmin,
            'can_access_admin_panel' => $user?->can('accessAdminPanel'),
            'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
        ];

        if (config('auth.debug_log', false)) {
            $context['email'] = $user?->email;
            $context['roles'] = $user?->roles()->pluck('slug')->all() ?? [];
        }

        Log::info('AdminMiddleware checked request.', $context);

        if (Auth::check() && $user?->can('accessAdminPanel')) {
            $response = $next($request);

            Log::info('AdminMiddleware completed request.', [
                ...$context,
                'response_status' => $response->getStatusCode(),
            ]);

            return $response;
        }

        Log::warning('AdminMiddleware denied request.', $context);

        abort(403);
    }
}
