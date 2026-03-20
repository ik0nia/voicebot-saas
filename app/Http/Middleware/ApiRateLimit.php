<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 60): Response
    {
        $key = 'api:' . ($request->user()?->id ?? $request->ip());
        $limiter = app(RateLimiter::class);

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many requests.',
                'retry_after' => $limiter->availableIn($key),
            ], 429);
        }

        $limiter->hit($key, 60);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $limiter->attempts($key)));

        return $response;
    }
}
