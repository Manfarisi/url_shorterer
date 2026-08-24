<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class CustomRateLimiter
{

private const MAX_ATTEMPTS = 10;
private const WINDOW_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $routeName = $request->route()->getName()?? $request->path();
        $key = "rate_limit:{$ip}:{$routeName}";
        $attempts = Redis::incr($key);

        if ($attempts === 1){
            Redis::expire($key, self::WINDOW_SECONDS);
        }

        if ($attempts > self::MAX_ATTEMPTS){
            $ttl = Redis::ttl($key);

            return response()->json([
                'message'=> 'Terlalu banyak request. Coba lagi nanti.',
                'retry_after_seconds'=> $ttl,
            ],429);
        }
        return $next($request);
    }
}
