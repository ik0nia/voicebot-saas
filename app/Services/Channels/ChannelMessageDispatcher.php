<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Models\Channel;
use App\Services\Channels\Messages\OutboundMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The single point through which typed OutboundMessage objects hit the wire.
 *
 * This is the typed parallel of ChannelMessagingService::sendOnChannel(),
 * not a replacement: the legacy string-based send() / sendOnChannel() entry
 * points keep working for callers that have not migrated. Once everything
 * upstream constructs OutboundMessage objects, the legacy paths can be
 * folded into typed equivalents (TextMessage + MediaMessage cover the
 * legacy surface area).
 *
 * Credentials are read off the channel record via getCredential(), with
 * env() fallback for tenants that haven't been onboarded yet (see
 * feedback note in ChannelMessagingService).
 */
class ChannelMessageDispatcher
{
    private const META_GRAPH = 'https://graph.facebook.com/v18.0';

    /**
     * Send a typed OutboundMessage and return the wire result.
     *
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function dispatch(Channel $channel, string $recipientId, OutboundMessage $message): array
    {
        try {
            $payload = $message->toMetaPayload($channel, $recipientId);
        } catch (\InvalidArgumentException $e) {
            // Validation error from the message class itself — NOT a Meta
            // round-trip. Log loudly because this means a caller built an
            // illegal payload and should fix the calling code.
            Log::error('ChannelMessageDispatcher: invalid OutboundMessage', [
                'channel_id' => $channel->id,
                'channel_type' => $channel->type,
                'message_kind' => $message->kind(),
                'reason' => $e->getMessage(),
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        $endpoint = $this->endpointFor($channel);
        $token = $this->tokenFor($channel);

        if ($endpoint === null || $token === null) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "Channel {$channel->id} ({$channel->type}) missing credentials",
            ];
        }

        return $this->callMetaApi($endpoint, $token, $payload, $channel, $message);
    }

    private function endpointFor(Channel $channel): ?string
    {
        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => self::META_GRAPH . '/' . ($channel->getCredential('phone_number_id')
                ?? $channel->external_id) . '/messages',
            Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM => self::META_GRAPH . '/me/messages',
            default => null,
        };
    }

    private function tokenFor(Channel $channel): ?string
    {
        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => $channel->getCredential('access_token')
                ?? config('services.whatsapp.token'),
            Channel::TYPE_FACEBOOK_MESSENGER => $channel->getCredential('page_access_token')
                ?? config('services.facebook.page_access_token'),
            Channel::TYPE_INSTAGRAM_DM => $channel->getCredential('page_access_token')
                ?? config('services.instagram.page_access_token'),
            default => null,
        };
    }

    private function callMetaApi(
        string $url,
        string $token,
        array $payload,
        Channel $channel,
        OutboundMessage $message
    ): array {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($url, $payload);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id') ?? $response->json('message_id');
                return ['success' => true, 'message_id' => $messageId, 'error' => null];
            }

            // Same sanitization as ChannelMessagingService — never leak
            // EAA tokens that Meta sometimes embeds in error responses.
            $code = $response->json('error.code');
            $msg = $response->json('error.message') ?? 'Meta API error';
            $sanitized = is_string($msg)
                ? preg_replace('/EAA[A-Za-z0-9_\-]{20,}/', 'EAA[REDACTED]', $msg)
                : 'Meta API error';
            $errorString = $code ? "[{$code}] {$sanitized}" : $sanitized;

            Log::warning('ChannelMessageDispatcher: Meta API error', [
                'channel_id' => $channel->id,
                'channel_type' => $channel->type,
                'message_kind' => $message->kind(),
                'status' => $response->status(),
                'meta_error_code' => $code,
                'meta_error_message' => mb_substr($sanitized, 0, 200),
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $errorString];
        } catch (\Throwable $e) {
            Log::error('ChannelMessageDispatcher: request failed', [
                'channel_id' => $channel->id,
                'message_kind' => $message->kind(),
                'exception' => $e::class,
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $e::class];
        }
    }
}
