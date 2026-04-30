<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound dispatcher for Meta-family channels (WhatsApp Cloud API,
 * Facebook Messenger, Instagram Direct).
 *
 * Reads credentials from the Channel model first, falls back to
 * config()/env() for legacy single-tenant deployments. The fallback is
 * intentionally kept until tenant onboarding has populated channel rows
 * for all live tenants — remove once telemetry shows zero env reads.
 */
class ChannelMessagingService
{
    private const META_GRAPH = 'https://graph.facebook.com/v18.0';

    /**
     * Send a text/media message on a specific channel.
     *
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function sendOnChannel(Channel $channel, string $recipientId, string $message, array $options = []): array
    {
        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => $this->sendWhatsApp($channel, $recipientId, $message, $options),
            Channel::TYPE_FACEBOOK_MESSENGER => $this->sendFacebook($channel, $recipientId, $message, $options),
            Channel::TYPE_INSTAGRAM_DM => $this->sendInstagram($channel, $recipientId, $message, $options),
            default => ['success' => false, 'message_id' => null, 'error' => "Unsupported channel type: {$channel->type}"],
        };
    }

    /**
     * Send a WhatsApp template message via a specific channel.
     */
    public function sendTemplateOnChannel(
        Channel $channel,
        string $recipientId,
        string $templateName,
        array $templateParams = [],
        string $language = 'ro'
    ): array {
        if ($channel->type !== Channel::TYPE_WHATSAPP) {
            return ['success' => false, 'message_id' => null, 'error' => 'Templates only supported on WhatsApp'];
        }

        [$token, $phoneNumberId] = $this->whatsappCredentials($channel);
        if (!$token || !$phoneNumberId) {
            return ['success' => false, 'message_id' => null, 'error' => 'WhatsApp not configured for this channel'];
        }

        $components = [];
        if (!empty($templateParams)) {
            $parameters = array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($templateParams));
            $components[] = ['type' => 'body', 'parameters' => $parameters];
        }

        return $this->callMetaApi(self::META_GRAPH . "/{$phoneNumberId}/messages", $token, [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    private function sendWhatsApp(Channel $channel, string $recipientId, string $message, array $options): array
    {
        [$token, $phoneNumberId] = $this->whatsappCredentials($channel);
        if (!$token || !$phoneNumberId) {
            return ['success' => false, 'message_id' => null, 'error' => 'WhatsApp not configured for this channel'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        if (!empty($options['media_type']) && !empty($options['media_url'])) {
            $type = $options['media_type'];
            $payload['type'] = $type;
            $payload[$type] = ['link' => $options['media_url']];
            if (!empty($message)) {
                $payload[$type]['caption'] = $message;
            }
            unset($payload['text']);
        }

        return $this->callMetaApi(self::META_GRAPH . "/{$phoneNumberId}/messages", $token, $payload);
    }

    private function sendFacebook(Channel $channel, string $recipientId, string $message, array $options): array
    {
        $token = $channel->getCredential('page_access_token')
            ?? config('services.facebook.page_access_token');

        if (empty($token)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Facebook not configured for this channel'];
        }

        $payload = ['recipient' => ['id' => $recipientId], 'message' => ['text' => $message]];
        if (!empty($options['media_url'])) {
            $payload['message'] = [
                'attachment' => [
                    'type' => $options['media_type'] ?? 'image',
                    'payload' => ['url' => $options['media_url']],
                ],
            ];
        }

        return $this->callMetaApi(self::META_GRAPH . '/me/messages', $token, $payload);
    }

    private function sendInstagram(Channel $channel, string $recipientId, string $message, array $options): array
    {
        $token = $channel->getCredential('page_access_token')
            ?? config('services.instagram.page_access_token');

        if (empty($token)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Instagram not configured for this channel'];
        }

        return $this->callMetaApi(self::META_GRAPH . '/me/messages', $token, [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string} [token, phoneNumberId]
     */
    private function whatsappCredentials(Channel $channel): array
    {
        $token = $channel->getCredential('access_token')
            ?? config('services.whatsapp.token', env('WHATSAPP_TOKEN'));
        $phoneNumberId = $channel->getCredential('phone_number_id')
            ?? $channel->external_id
            ?? config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));

        return [$token ?: null, $phoneNumberId ?: null];
    }

    private function callMetaApi(string $url, string $token, array $payload): array
    {
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($url, $payload);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id') ?? $response->json('message_id');
                return ['success' => true, 'message_id' => $messageId, 'error' => null];
            }

            Log::warning('ChannelMessagingService: Meta API error', [
                'url' => $url,
                'status' => $response->status(),
                'body_excerpt' => mb_substr($response->body(), 0, 500),
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('ChannelMessagingService: request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }
}
