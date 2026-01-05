<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow Admins to bypass
        if (Auth::check() && Auth::user()->isAdmin) {
             return $next($request);
        }

        // Allow Login/Logout routes
        if ($request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        // Check Setting
        if (Setting::getValue('app.maintenance_mode', 0) == 1) {
            abort(503, 'System is currently under maintenance. Please try again later.');
        }

        return $next($request);
    }
}
