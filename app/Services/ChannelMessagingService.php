<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelMessagingService
{
    /**
     * Send a message to a channel contact.
     *
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function send(string $channel, string $recipientId, string $message, array $options = []): array
    {
        return match ($channel) {
            'whatsapp' => $this->sendWhatsApp($recipientId, $message, $options),
            'facebook' => $this->sendFacebook($recipientId, $message, $options),
            'instagram' => $this->sendInstagram($recipientId, $message, $options),
            default => ['success' => false, 'message_id' => null, 'error' => "Unknown channel: {$channel}"],
        };
    }

    /**
     * Send a WhatsApp template message.
     */
    public function sendTemplate(string $recipientId, string $templateName, array $templateParams = [], string $language = 'ro'): array
    {
        $token = config('services.whatsapp.token', env('WHATSAPP_TOKEN'));
        $phoneNumberId = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));

        if (empty($token) || empty($phoneNumberId)) {
            return ['success' => false, 'message_id' => null, 'error' => 'WhatsApp not configured'];
        }

        $components = [];
        if (!empty($templateParams)) {
            $parameters = array_map(fn($v) => ['type' => 'text', 'text' => $v], array_values($templateParams));
            $components[] = ['type' => 'body', 'parameters' => $parameters];
        }

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientId,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => $language],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');
                return ['success' => true, 'message_id' => $messageId, 'error' => null];
            }

            return ['success' => false, 'message_id' => null, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('ChannelMessagingService: WhatsApp template failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }

    private function sendWhatsApp(string $recipientId, string $message, array $options): array
    {
        $token = config('services.whatsapp.token', env('WHATSAPP_TOKEN'));
        $phoneNumberId = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));

        if (empty($token) || empty($phoneNumberId)) {
            return ['success' => false, 'message_id' => null, 'error' => 'WhatsApp not configured'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        // Support media messages
        if (!empty($options['media_type']) && !empty($options['media_url'])) {
            $payload['type'] = $options['media_type']; // image, document, audio
            $payload[$options['media_type']] = ['link' => $options['media_url']];
            if (!empty($message)) {
                $payload[$options['media_type']]['caption'] = $message;
            }
            unset($payload['text']);
        }

        return $this->callMetaApi("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $token, $payload);
    }

    private function sendFacebook(string $recipientId, string $message, array $options): array
    {
        $token = config('services.facebook.page_token', env('FACEBOOK_PAGE_TOKEN'));

        if (empty($token)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Facebook not configured'];
        }

        $payload = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ];

        if (!empty($options['media_url'])) {
            $payload['message'] = [
                'attachment' => [
                    'type' => $options['media_type'] ?? 'image',
                    'payload' => ['url' => $options['media_url']],
                ],
            ];
        }

        return $this->callMetaApi('https://graph.facebook.com/v18.0/me/messages', $token, $payload);
    }

    private function sendInstagram(string $recipientId, string $message, array $options): array
    {
        $token = config('services.instagram.page_token', env('INSTAGRAM_PAGE_TOKEN'));

        if (empty($token)) {
            return ['success' => false, 'message_id' => null, 'error' => 'Instagram not configured'];
        }

        $payload = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
        ];

        return $this->callMetaApi('https://graph.facebook.com/v18.0/me/messages', $token, $payload);
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

            Log::warning('ChannelMessagingService: API error', [
                'url' => $url, 'status' => $response->status(), 'body' => $response->body(),
            ]);
            return ['success' => false, 'message_id' => null, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('ChannelMessagingService: request failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }
    }
}
