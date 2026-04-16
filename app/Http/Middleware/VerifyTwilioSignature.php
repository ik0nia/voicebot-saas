<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the X-Twilio-Signature header on incoming Twilio webhooks.
 *
 * Twilio's signature scheme: HMAC-SHA1 of
 *   URL + concatenated sorted(param_key + param_value)
 * keyed with the account Auth Token, base64-encoded. The URL must be
 * the exact absolute URL Twilio called — behind a reverse proxy that
 * means trusting X-Forwarded-* (already configured in bootstrap/app.php).
 *
 * Fail-closed behaviour mirrors iter 1 on the Meta middleware: missing
 * auth token => 503 (don't pretend to verify with no secret); missing
 * header => 401; bad signature => 403.
 *
 * Unlike Telnyx's ed25519 + timestamp (iter 10), Twilio's signature
 * has no timestamp, so replay protection has to be per-event via the
 * CallSid (planned for a follow-up iter, mirroring the Meta dedupe
 * added in iter 11).
 */
class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $authToken = PlatformSetting::get('twilio_auth_token')
            ?: config('services.twilio.auth_token');

        if (empty($authToken)) {
            Log::error('VerifyTwilioSignature: no Twilio auth token configured — rejecting webhook');
            abort(503, 'Twilio webhook signing not configured.');
        }

        $signature = $request->header('X-Twilio-Signature');
        if (empty($signature)) {
            Log::warning('VerifyTwilioSignature: missing X-Twilio-Signature header', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(401, 'Missing Twilio signature.');
        }

        // Reconstruct what Twilio signed: the full request URL plus the
        // POST parameters in alphabetical order, concatenated key+value
        // with no delimiter. Query params are already in the URL so they
        // are not re-included.
        $url = $request->fullUrl();
        $params = $request->post();
        ksort($params);

        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        if (!hash_equals($expected, $signature)) {
            Log::warning('VerifyTwilioSignature: invalid signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(403, 'Invalid Twilio signature.');
        }

        return $next($request);
    }
}
