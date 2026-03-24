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

        $appSecret = config('services.meta.app_secret', env('META_APP_SECRET'));
        if (empty($appSecret)) {
            Log::error('VerifyMetaWebhookSignature: META_APP_SECRET not configured');
            // Allow through if not configured (backward compat), but log
            return $next($request);
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
