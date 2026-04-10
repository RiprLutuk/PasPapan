<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Check if the user is authenticated and belongs to the 'admin' or 'superadmin' group
        if (Auth::check() && Auth::user()?->isAdmin) {
            
            // DEMO USER RESTRICTIONS
            if (Auth::user()->is_demo) {
                $restrictedDemoRoutes = [
                    'admin.masters.admin',
                    'admin.settings',
                    'admin.system-maintenance'
                ];

                if (in_array($request->route()->getName(), $restrictedDemoRoutes)) {
                    if ($request->wantsJson()) {
                        abort(403, __('Akun Demo dibatasi untuk mengakses fitur krusial ini.'));
                    }
                    return redirect()->route('admin.dashboard')->with('error', __('Akun Demo dibatasi untuk mengakses menu keamanan dan konfigurasi sistem.'));
                }
            }

            return $next($request);
        }

        // If the user is not an admin, return a 403 Forbidden response
        abort(403);
    }
}
