<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminProfileRequests
{
    /**
     * Keep administrators on the admin-specific profile page when they open
     * Jetstream's default profile route directly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $request->isMethod('GET')
            && $request->routeIs('profile.show')
            && $user?->can('accessAdminPanel')
        ) {
            return redirect()->route('admin.profile.show');
        }

        return $next($request);
    }
}
