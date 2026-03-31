<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

class TwilioService
{
    protected ?Client $client = null;

    protected function getClient(): Client
    {
        if (!$this->client) {
            // Read from DB (PlatformSettings) first, fallback to config/.env
            $sid = \App\Models\PlatformSetting::get('twilio_sid')
                ?: config('services.twilio.sid');
            $token = \App\Models\PlatformSetting::get('twilio_auth_token')
                ?: config('services.twilio.auth_token');

            if (empty($sid) || empty($token) || $sid === 'your-twilio-sid') {
                throw new \RuntimeException('Twilio credentials not configured. Set them in Admin → Settings → Twilio.');
            }

            $this->client = new Client($sid, $token);
        }
        return $this->client;
    }

    public function makeCall(string $to, string $from, string $webhookUrl): object
    {
        // Validate E.164 format: + followed by 8-15 digits
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
            throw new \InvalidArgumentException("Invalid E.164 phone number: {$to}");
        }
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $from)) {
            throw new \InvalidArgumentException("Invalid E.164 caller number: {$from}");
        }

        return $this->getClient()->calls->create(
            $to,
            $from,
            [
                'url' => $webhookUrl,
                'statusCallback' => route('webhook.twilio.status'),
                'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed', 'failed', 'busy', 'no-answer'],
                'statusCallbackMethod' => 'POST',
            ]
        );
    }

    public function getAvailableNumbers(string $country = 'RO', string $type = 'local', int $limit = 10): array
    {
        try {
            $numbers = $this->getClient()->availablePhoneNumbers($country)
                ->local
                ->read([], $limit);

            return array_map(fn($n) => [
                'number' => $n->phoneNumber,
                'friendly_name' => $n->friendlyName,
                'capabilities' => [
                    'voice' => $n->capabilities['voice'] ?? false,
                    'sms' => $n->capabilities['SMS'] ?? false,
                ],
                'monthly_cost' => 1.00, // EUR estimate
            ], $numbers);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('TwilioService: getAvailableNumbers failed', ['country' => $country, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function purchaseNumber(string $phoneNumber): ?object
    {
        try {
            return $this->getClient()->incomingPhoneNumbers->create([
                'phoneNumber' => $phoneNumber,
                'voiceUrl' => route('webhook.twilio.voice'),
                'voiceMethod' => 'POST',
                'statusCallback' => route('webhook.twilio.status'),
                'statusCallbackMethod' => 'POST',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TwilioService: purchaseNumber failed', ['number' => $phoneNumber, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function releaseNumber(string $sid): bool
    {
        try {
            $this->getClient()->incomingPhoneNumbers($sid)->delete();
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('TwilioService: releaseNumber failed', ['sid' => $sid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function generateMediaStreamTwiml(string $botId, string $callId): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->say('Buna ziua! Va conectam cu asistentul nostru virtual.', ['language' => 'ro-RO']);

        $connect = $response->connect();
        $stream = $connect->stream([
            'url' => "wss://" . config('app.url_host', 'sambla.ro') . "/ws/media-stream",
            'track' => 'both_tracks',
        ]);
        $stream->parameter(['name' => 'bot_id', 'value' => $botId]);
        $stream->parameter(['name' => 'call_id', 'value' => $callId]);

        return $response;
    }
}
