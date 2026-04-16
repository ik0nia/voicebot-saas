<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the X-Twilio-Signature header on incoming Twilio webhooks.
 *
 * Twilio's signature scheme: HMAC-SHA1 of
 *   URL + concatenated sorted(param_key + param_value)
 * keyed with the Auth Token of the account that OWNS the number. With
 * per-tenant subaccounts (see TelephonyManager::forTenant), that's the
 * subaccount's Auth Token, not the master's. We extract AccountSid
 * from the POST params, resolve it to a tenant if it matches a
 * provisioned subaccount, and use that tenant's encrypted auth token.
 * Falls back to master when AccountSid matches the master SID.
 *
 * Fail-closed behaviour mirrors iter 1 on the Meta middleware: missing
 * auth token => 503 (don't pretend to verify with no secret); missing
 * header => 401; bad signature => 403; unknown AccountSid => 403
 * (prevents an attacker from bypassing verification by claiming to be
 * a subaccount we don't own).
 */
class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $authToken = $this->resolveAuthToken($request);
        if ($authToken === null) {
            // Diagnostic already logged by resolveAuthToken.
            abort(503, 'Twilio webhook signing not configured.');
        }
        if ($authToken === false) {
            abort(403, 'Unknown Twilio account.');
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

    /**
     * Returns:
     *   - string auth token when verification can proceed
     *   - null when the platform is misconfigured (no master token at all → 503)
     *   - false when AccountSid in the request is unknown (403)
     */
    private function resolveAuthToken(Request $request): string|null|false
    {
        $masterSid = PlatformSetting::get('twilio_account_sid')
            ?: config('services.twilio.account_sid');
        $masterToken = PlatformSetting::get('twilio_auth_token')
            ?: config('services.twilio.auth_token');

        if (empty($masterToken)) {
            Log::error('VerifyTwilioSignature: no Twilio master auth token configured');
            return null;
        }

        $accountSid = $request->input('AccountSid');

        // No AccountSid in the payload — Twilio always sends one on
        // real webhooks, but fall back to master for safety (still
        // fails the signature check below if the payload was tampered).
        if (empty($accountSid)) {
            return $masterToken;
        }

        if ($masterSid && hash_equals((string) $masterSid, $accountSid)) {
            return $masterToken;
        }

        // Subaccount case — look up the tenant that owns this AccountSid.
        $tenant = Tenant::withoutGlobalScopes()
            ->where('telephony_subaccount_sid', $accountSid)
            ->first();

        if (!$tenant || empty($tenant->telephony_subaccount_auth_token)) {
            Log::warning('VerifyTwilioSignature: AccountSid does not match master or any known subaccount', [
                'account_sid' => $accountSid,
                'ip' => $request->ip(),
            ]);
            return false;
        }

        return $tenant->telephony_subaccount_auth_token;
    }
}
