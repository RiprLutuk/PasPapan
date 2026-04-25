<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Core Security Headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS - Force HTTPS (1 year)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions Policy - Restrict sensitive browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // Content Security Policy
        $cspConfig = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https: http:",
            "connect-src 'self' https://tile.openstreetmap.org https://cdn.jsdelivr.net wss:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // Allow Vite dev server in local environment
        if (app()->environment('local')) {
            // Browsers are strict about wildcard ports. We allow common localhost variants
            // and the specific host if we're accessing via LAN IP.
            $host = $request->getHost();

            // Core Vite dev server hosts
            $viteHosts = [
                // localhost
                'http://localhost:5173',
                'ws://localhost:5173',
                'wss://localhost:5173',
                'http://localhost:5174',
                'ws://localhost:5174',
                'wss://localhost:5174',
                // 127.0.0.1
                'http://127.0.0.1:5173',
                'ws://127.0.0.1:5173',
                'wss://127.0.0.1:5173',
                'http://127.0.0.1:5174',
                'ws://127.0.0.1:5174',
                'wss://127.0.0.1:5174',
            ];
            // If accessing via LAN (e.g. 192.168.x.x), allow that IP with port 5173/5174 specifically
            if ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== '[::1]') {
                $viteHosts = array_merge($viteHosts, [
                    "http://{$host}:5173",
                    "ws://{$host}:5173",
                    "wss://{$host}:5173",
                    "http://{$host}:5174",
                    "ws://{$host}:5174",
                    "wss://{$host}:5174",
                ]);
            }

            $viteHostStr = implode(' ', $viteHosts);

            foreach ($cspConfig as &$directive) {
                if (str_starts_with($directive, 'script-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'style-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'font-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'img-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'connect-src')) {
                    $directive .= ' '.$viteHostStr;
                }
            }
        }

        $csp = implode('; ', $cspConfig);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
