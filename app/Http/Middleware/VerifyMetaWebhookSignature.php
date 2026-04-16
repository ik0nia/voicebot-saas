<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyMetaWebhookSignature
{
    /**
     * Verify X-Hub-Signature-256 HMAC for Meta webhooks (WhatsApp, Facebook, Instagram).
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip signature verification for GET requests (webhook verification)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            Log::warning('VerifyMetaWebhookSignature: missing signature header', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $appSecret = config('services.meta.app_secret');
        if (empty($appSecret)) {
            // Fail CLOSED. Previously this path fell through as "backward
            // compat" — which meant a missing/rotated secret silently turned
            // signature verification off and every unsigned webhook was
            // accepted. Refuse until the secret is configured.
            Log::error('VerifyMetaWebhookSignature: META_APP_SECRET not configured — rejecting webhook');
            return response()->json(['error' => 'Webhook signing not configured'], 503);
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('VerifyMetaWebhookSignature: invalid signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
