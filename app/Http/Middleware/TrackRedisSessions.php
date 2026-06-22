<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TrackRedisSessions
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (config('session.driver') === 'redis' && $request->user()) {
            $userId = $request->user()->getKey();
            $sessionId = $request->session()->getId();

            $key = "user_sessions:{$userId}";

            $payload = json_encode([
                'id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => now()->getTimestamp(),
            ]);

            $redis = Redis::connection(config('session.connection'));
            $redis->hset($key, $sessionId, $payload);

            // Set expiry of the whole hash to match session lifetime
            $redis->expire($key, config('session.lifetime', 120) * 60);
        }

        return $response;
    }
}
