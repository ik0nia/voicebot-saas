<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-tenant rate limiter — caps configurabile prin
 * `tenant.settings.rate_limits.{api_per_minute, api_per_hour}`.
 * Fallback la config-level defaults dacă lipsesc.
 *
 * Aplicat doar pe rute autentificate (necesită user/tenant rezolvat).
 *
 * Key-uri:
 *   tenant_rl:{tenant_id}:m (minute bucket)
 *   tenant_rl:{tenant_id}:h (hour bucket)
 *
 * Răspuns 429 cu header `Retry-After` standard.
 */
class TenantRateLimit
{
    public function handle(Request $request, Closure $next, string $bucket = 'api')
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        if (!$tenantId) {
            return $next($request);
        }

        $tenant = $user->tenant ?? null;
        $settings = is_array($tenant?->settings ?? null) ? $tenant->settings : [];
        $limits = $settings['rate_limits'] ?? [];

        $perMin = (int) ($limits[$bucket . '_per_minute'] ?? config('app.tenant_rate_per_minute', 600));
        $perHour = (int) ($limits[$bucket . '_per_hour'] ?? config('app.tenant_rate_per_hour', 10000));

        // Minute bucket
        $keyMin = "tenant_rl:{$tenantId}:{$bucket}:m";
        if (RateLimiter::tooManyAttempts($keyMin, $perMin)) {
            return $this->tooMany('per-minute', RateLimiter::availableIn($keyMin));
        }
        RateLimiter::hit($keyMin, 60);

        // Hour bucket
        $keyHour = "tenant_rl:{$tenantId}:{$bucket}:h";
        if (RateLimiter::tooManyAttempts($keyHour, $perHour)) {
            return $this->tooMany('per-hour', RateLimiter::availableIn($keyHour));
        }
        RateLimiter::hit($keyHour, 3600);

        return $next($request);
    }

    private function tooMany(string $window, int $retryAfter): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'rate_limit_exceeded',
            'window' => $window,
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }
}
