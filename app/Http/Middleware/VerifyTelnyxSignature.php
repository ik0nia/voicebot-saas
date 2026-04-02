<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelnyxSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Read public key from DB (PlatformSettings) first, fallback to config/.env
        $publicKey = \App\Models\PlatformSetting::get('telnyx_public_key')
            ?: config('services.telnyx.public_key');

        if (empty($publicKey)) {
            Log::error('VerifyTelnyxSignature: no public key configured');
            abort(403, 'Telnyx public key not configured.');
        }

        $signature = $request->header('telnyx-signature-ed25519', '');
        $timestamp = $request->header('telnyx-timestamp', '');

        if (empty($signature) || empty($timestamp)) {
            Log::warning('VerifyTelnyxSignature: missing signature or timestamp headers', [
                'has_signature' => !empty($signature),
                'has_timestamp' => !empty($timestamp),
            ]);
            abort(403, 'Missing Telnyx signature headers.');
        }

        $payload = $request->getContent();
        $signedPayload = $timestamp . '|' . $payload;

        try {
            $decodedSignature = base64_decode($signature, true);
            $decodedPublicKey = base64_decode($publicKey, true);

            if ($decodedSignature === false || $decodedPublicKey === false) {
                Log::warning('VerifyTelnyxSignature: failed to decode signature or public key');
                abort(403, 'Invalid Telnyx signature format.');
            }

            $isValid = sodium_crypto_sign_verify_detached(
                $decodedSignature,
                $signedPayload,
                $decodedPublicKey
            );
        } catch (\SodiumException $e) {
            Log::warning('VerifyTelnyxSignature: sodium verification error', [
                'error' => $e->getMessage(),
            ]);
            $isValid = false;
        }

        if (!$isValid) {
            Log::warning('VerifyTelnyxSignature: validation failed', [
                'has_signature' => !empty($signature),
                'has_timestamp' => !empty($timestamp),
            ]);
            abort(403, 'Invalid Telnyx signature.');
        }

        return $next($request);
    }
}
