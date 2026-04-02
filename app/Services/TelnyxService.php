<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelnyxService
{
    protected const BASE_URL = 'https://api.telnyx.com/v2';

    protected ?string $apiKey = null;
    protected ?string $connectionId = null;

    protected function getApiKey(): string
    {
        if (!$this->apiKey) {
            $this->apiKey = \App\Models\PlatformSetting::get('telnyx_api_key')
                ?: config('services.telnyx.api_key');

            if (empty($this->apiKey)) {
                throw new \RuntimeException('Telnyx API key not configured. Set it in Admin → Settings → Telnyx.');
            }
        }
        return $this->apiKey;
    }

    protected function getConnectionId(): string
    {
        if (!$this->connectionId) {
            $this->connectionId = \App\Models\PlatformSetting::get('telnyx_connection_id')
                ?: config('services.telnyx.connection_id');

            if (empty($this->connectionId)) {
                throw new \RuntimeException('Telnyx Connection ID not configured. Set it in Admin → Settings → Telnyx.');
            }
        }
        return $this->connectionId;
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->getApiKey())
            ->acceptJson()
            ->asJson();
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

        $response = $this->request()->post('/calls', [
            'connection_id' => $this->getConnectionId(),
            'to' => $to,
            'from' => $from,
            'webhook_url' => $webhookUrl,
            'webhook_url_method' => 'POST',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Telnyx makeCall failed: ' . $response->body());
        }

        return (object) $response->json('data');
    }

    public function getAvailableNumbers(string $country = 'RO', string $type = 'local', int $limit = 10): array
    {
        try {
            $response = $this->request()->get('/available_phone_numbers', [
                'filter[country_code]' => $country,
                'filter[phone_number_type]' => $type,
                'filter[limit]' => $limit,
            ]);

            if ($response->failed()) {
                Log::warning('TelnyxService: getAvailableNumbers failed', ['country' => $country, 'error' => $response->body()]);
                return [];
            }

            $numbers = $response->json('data', []);

            $featureNames = fn($features) => array_map(fn($f) => $f['name'] ?? $f, $features ?? []);

            return array_map(fn($n) => [
                'number' => $n['phone_number'],
                'friendly_name' => $n['phone_number'],
                'capabilities' => [
                    'voice' => in_array('voice', $featureNames($n['features'] ?? [])),
                    'sms' => in_array('sms', $featureNames($n['features'] ?? [])),
                ],
                'region' => $n['region_information'] ?? [],
                'monthly_cost' => (float) ($n['cost_information']['monthly_cost'] ?? 1.00),
            ], $numbers);
        } catch (\Exception $e) {
            Log::warning('TelnyxService: getAvailableNumbers failed', ['country' => $country, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function purchaseNumber(string $phoneNumber): ?object
    {
        try {
            $response = $this->request()->post('/number_orders', [
                'phone_numbers' => [
                    ['phone_number' => $phoneNumber],
                ],
                'connection_id' => $this->getConnectionId(),
            ]);

            if ($response->failed()) {
                Log::error('TelnyxService: purchaseNumber failed', ['number' => $phoneNumber, 'error' => $response->body()]);
                return null;
            }

            return (object) $response->json('data');
        } catch (\Exception $e) {
            Log::error('TelnyxService: purchaseNumber failed', ['number' => $phoneNumber, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function releaseNumber(string $phoneNumberId): bool
    {
        try {
            $response = $this->request()->delete("/phone_numbers/{$phoneNumberId}");

            if ($response->failed()) {
                Log::error('TelnyxService: releaseNumber failed', ['id' => $phoneNumberId, 'error' => $response->body()]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('TelnyxService: releaseNumber failed', ['id' => $phoneNumberId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateNumberTags(string $phoneNumber, array $tags): bool
    {
        try {
            // Find the Telnyx phone number ID by number
            $response = $this->request()->get('/phone_numbers', [
                'filter[phone_number]' => $phoneNumber,
            ]);

            if ($response->failed() || empty($response->json('data'))) {
                Log::warning('TelnyxService: number not found for tagging', ['number' => $phoneNumber]);
                return false;
            }

            $telnyxId = $response->json('data.0.id');

            $updateResponse = $this->request()->patch("/phone_numbers/{$telnyxId}", [
                'tags' => $tags,
            ]);

            return $updateResponse->successful();
        } catch (\Exception $e) {
            Log::warning('TelnyxService: updateNumberTags failed', ['number' => $phoneNumber, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function generateMediaStreamTexml(string $botId, string $callId): string
    {
        $host = config('app.url_host', 'sambla.ro');
        $wsUrl = "wss://{$host}/ws/media-stream";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Response>';
        $xml .= '<Say language="ro-RO">Buna ziua! Va conectam cu asistentul nostru virtual.</Say>';
        $xml .= '<Connect>';
        $xml .= '<Stream url="' . htmlspecialchars($wsUrl, ENT_XML1) . '">';
        $xml .= '<Parameter name="bot_id" value="' . htmlspecialchars($botId, ENT_XML1) . '"/>';
        $xml .= '<Parameter name="call_id" value="' . htmlspecialchars($callId, ENT_XML1) . '"/>';
        $xml .= '</Stream>';
        $xml .= '</Connect>';
        $xml .= '</Response>';

        return $xml;
    }
}
