<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated and belongs to the 'user' group
        if (Auth::check() && Auth::user()->isUser) {
            return $next($request);
        }

        Log::warning('UserMiddleware denied request.', [
            'path' => $request->path(),
            'user_id' => Auth::id(),
            'email' => Auth::user()?->email,
            'group' => Auth::user()?->group,
            'roles' => Auth::user()?->roles()->pluck('slug')->all(),
            'can_access_admin_panel' => Auth::user()?->can('accessAdminPanel'),
            'can_view_admin_dashboard' => Auth::user()?->can('viewAdminDashboard'),
        ]);

        // If the user is not an user, return a 403 Forbidden response
        abort(403);
    }
}
