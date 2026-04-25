<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsByIP
{
    /**
     * Handle an incoming request.
     * Aggressive IP-based throttling for DDoS protection.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'throttle_ip_'.$ip;

        // Check if IP is temporarily blocked
        if (Cache::has('blocked_ip_'.$ip)) {
            abort(429, 'Too Many Requests. Please try again later.');
        }

        // Aggressive rate limit: 100 requests per minute per IP
        $maxAttempts = (int) \App\Models\Setting::getValue('security.rate_limit_ip', 100);

        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () {
                // Request allowed
            },
            60 // Per minute
        );

        if (! $executed) {
            // Log suspicious activity
            \App\Models\ActivityLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'Rate Limit Exceeded',
                'description' => "IP {$ip} exceeded rate limit ({$maxAttempts}/min)",
                'ip_address' => $ip,
            ]);

            // Block IP for 5 minutes after exceeding limit
            Cache::put('blocked_ip_'.$ip, true, now()->addMinutes(5));

            abort(429, 'Too Many Requests. You have been temporarily blocked.');
        }

        $response = $next($request);

        // Add rate limit headers for transparency
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));

        return $response;
    }
}
