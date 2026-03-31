<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;
use Symfony\Component\HttpFoundation\Response;

class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Read auth token from DB (PlatformSettings) first, fallback to config/.env
        $authToken = \App\Models\PlatformSetting::get('twilio_auth_token')
            ?: config('services.twilio.auth_token');

        if (empty($authToken)) {
            \Illuminate\Support\Facades\Log::error('VerifyTwilioSignature: no auth token configured');
            abort(403, 'Twilio auth token not configured.');
        }

        $validator = new RequestValidator($authToken);
        $signature = $request->header('X-Twilio-Signature', '');

        // Behind reverse proxy (Traefik/Coolify), $request->fullUrl() may return http://
        // but Twilio signed on https://. Use APP_URL + path (without query string) to match Twilio's URL.
        // getPathInfo() returns only the path, getRequestUri() includes query string which breaks validation.
        $url = rtrim(config('app.url'), '/') . '/' . ltrim($request->getPathInfo(), '/');
        $params = $request->all();

        if (!$validator->validate($signature, $url, $params)) {
            \Illuminate\Support\Facades\Log::warning('VerifyTwilioSignature: validation failed', [
                'url_used' => $url,
                'request_url' => $request->fullUrl(),
                'has_signature' => !empty($signature),
                'params' => array_keys($params),
            ]);
            abort(403, 'Invalid Twilio signature.');
        }

        return $next($request);
    }
}
