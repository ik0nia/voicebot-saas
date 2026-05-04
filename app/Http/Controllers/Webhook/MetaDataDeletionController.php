<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Meta-mandatory endpoint for App Review.
 *
 * When a Facebook/Instagram user revokes the Sambla app from
 * https://www.facebook.com/settings → Apps and Websites, Meta POSTs
 * here with a `signed_request` field. We must:
 *
 *   1. Verify the HMAC-SHA256 signature using META_APP_SECRET.
 *   2. Decode the payload; extract `user_id` (Meta-scoped user ID).
 *   3. Schedule deletion of any data we hold against that user_id.
 *   4. Return JSON `{ url, confirmation_code }` so the user can check
 *      progress at the URL.
 *
 * Reference: https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback
 */
class MetaDataDeletionController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        $signedRequest = $request->input('signed_request');
        if (!is_string($signedRequest) || !str_contains($signedRequest, '.')) {
            return response()->json(['error' => 'missing signed_request'], 400);
        }

        $payload = $this->parseSignedRequest($signedRequest, (string) config('services.meta.app_secret'));
        if ($payload === null) {
            Log::warning('Meta data-deletion: signature mismatch', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $userId = (string) ($payload['user_id'] ?? '');
        if ($userId === '') {
            return response()->json(['error' => 'missing user_id in payload'], 400);
        }

        // Confirmation code is opaque from Meta's perspective — we reuse
        // it as a lookup key so the user (or Meta itself) can come back
        // and check status. Cache only — no DB row needed; the actual
        // deletion of channels/messages is the authoritative record.
        $confirmationCode = strtoupper(Str::random(16));

        Cache::put(
            "meta_deletion:{$confirmationCode}",
            [
                'user_id' => $userId,
                'requested_at' => now()->toIso8601String(),
                'status' => 'pending',
            ],
            now()->addDays(30),
        );

        // Best-effort synchronous deletion. We deactivate channels keyed
        // by this Meta user_id (stored in credentials.fb_user_id at OAuth
        // time) and null their tokens — anything Meta cached at their
        // end is already revoked the moment they hit "Remove" in FB.
        $this->purgeChannelsForMetaUser($userId, $confirmationCode);

        $statusUrl = url('/legal/data-deletion?id=' . $confirmationCode);

        Log::info('Meta data-deletion processed', [
            'meta_user_id' => $userId,
            'confirmation_code' => $confirmationCode,
        ]);

        return response()->json([
            'url' => $statusUrl,
            'confirmation_code' => $confirmationCode,
        ]);
    }

    /**
     * Public status checker. Hit by the user from
     * /legal/data-deletion?id=XXX after clicking the URL we returned.
     */
    public function status(string $code): array
    {
        return Cache::get("meta_deletion:{$code}", [
            'status' => 'unknown',
            'message' => 'Codul de confirmare nu a fost găsit sau a expirat.',
        ]);
    }

    /**
     * Verify HMAC-SHA256 over the unsigned payload using app secret.
     * Returns decoded array on success, null on signature mismatch.
     */
    private function parseSignedRequest(string $signedRequest, string $appSecret): ?array
    {
        if ($appSecret === '') {
            return null;
        }

        [$encodedSig, $encodedPayload] = explode('.', $signedRequest, 2);

        $sig = $this->base64UrlDecode($encodedSig);
        $expected = hash_hmac('sha256', $encodedPayload, $appSecret, true);

        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $decoded = json_decode($this->base64UrlDecode($encodedPayload), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder !== 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Deactivate FB/IG channels owned by this Meta user. We don't
     * hard-delete the conversations themselves — those belong to the
     * tenant business, not the connecting user — but we sever access
     * (clear tokens, mark inactive) so we can't continue messaging.
     */
    private function purgeChannelsForMetaUser(string $metaUserId, string $confirmationCode): void
    {
        $channels = Channel::withoutGlobalScopes()
            ->whereIn('type', [Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM])
            ->get()
            ->filter(fn (Channel $c) => ($c->credentials['fb_user_id'] ?? null) === $metaUserId);

        foreach ($channels as $channel) {
            $creds = $channel->credentials ?? [];
            unset($creds['page_access_token'], $creds['user_access_token']);
            $creds['revoked_at'] = now()->toIso8601String();
            $creds['revocation_code'] = $confirmationCode;

            $channel->update([
                'credentials' => $creds,
                'is_active' => false,
                'status' => 'revoked',
            ]);
        }

        Cache::put("meta_deletion:{$confirmationCode}", [
            'user_id' => $metaUserId,
            'requested_at' => now()->toIso8601String(),
            'status' => 'completed',
            'channels_revoked' => $channels->count(),
        ], now()->addDays(30));
    }
}
