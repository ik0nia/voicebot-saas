<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-style auth for service-to-service calls inside the platform —
 * currently the media-stream bridge posting transcript chunks and
 * OpenAI usage back to Laravel.
 *
 * The token lives in env only (never PlatformSetting — this isn't a
 * customer-facing setting, and leaking it into the DB widens its
 * exposure). Fail-closed if unset, same shape as the Meta / Twilio /
 * carrier signature middlewares.
 */
class VerifyInternalServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Env is the canonical source but fall back to PlatformSetting
        // so the operator can rotate the token via Admin UI without a
        // full app redeploy. Rotation flow: set the new token in both
        // Laravel and the media-stream container, verify both update,
        // then clear from PlatformSetting to leave env as SSOT again.
        $expected = config('services.internal.service_token')
            ?: \App\Models\PlatformSetting::get('internal_service_token');
        if (empty($expected)) {
            Log::error('VerifyInternalServiceToken: INTERNAL_SERVICE_TOKEN not configured');
            abort(503, 'Internal service auth not configured.');
        }

        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            Log::warning('VerifyInternalServiceToken: missing Bearer header', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(401, 'Missing service token.');
        }

        $provided = substr($header, 7);
        if (!hash_equals($expected, $provided)) {
            Log::warning('VerifyInternalServiceToken: invalid token', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(403, 'Invalid service token.');
        }

        return $next($request);
    }
}
