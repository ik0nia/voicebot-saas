<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevenLabsService
{
    private function apiKey(): string
    {
        return PlatformSetting::get('elevenlabs_api_key', config('services.elevenlabs.api_key', ''));
    }

    private function modelId(): string
    {
        return PlatformSetting::get('elevenlabs_model_id', 'eleven_multilingual_v2');
    }

    public function isConfigured(): bool
    {
        $key = $this->apiKey();
        return !empty($key) && !str_starts_with($key, 'xi-your');
    }

    /**
     * Create a cloned voice from an audio file.
     */
    public function createVoice(string $name, string $audioFilePath, string $description = '', ?int $botId = null, ?int $tenantId = null): ?array
    {
        if (!$this->isConfigured()) {
            Log::error('ElevenLabs: API key not configured');
            return null;
        }

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey(),
            ])->timeout(120)->attach(
                'files', file_get_contents($audioFilePath), basename($audioFilePath)
            )->post('https://api.elevenlabs.io/v1/voices/add', [
                'name' => $name,
                'description' => $description ?: "Cloned voice: {$name}",
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();

                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => 'elevenlabs',
                        'model' => 'voice-cloning',
                        'input_tokens' => 0,
                        'output_tokens' => 0,
                        'cost_cents' => 10.0, // ~$0.10 per clone
                        'response_time_ms' => $responseTimeMs,
                        'status' => 'success',
                        'error_type' => null,
                        'bot_id' => $botId,
                        'tenant_id' => $tenantId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
                }

                return [
                    'voice_id' => $data['voice_id'],
                    'name' => $name,
                ];
            }

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'elevenlabs',
                    'model' => 'voice-cloning',
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => 'http_' . $response->status(),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
            }

            Log::error('ElevenLabs createVoice failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'elevenlabs',
                    'model' => 'voice-cloning',
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => get_class($e),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $metricEx) {
                Log::warning('Failed to record API metric', ['error' => $metricEx->getMessage()]);
            }

            Log::error('ElevenLabs createVoice exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Delete a cloned voice from ElevenLabs.
     */
    public function deleteVoice(string $voiceId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey(),
            ])->timeout(30)->delete("https://api.elevenlabs.io/v1/voices/{$voiceId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ElevenLabs deleteVoice exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Synthesize text to speech. Returns base64-encoded g711_ulaw audio.
     */
    public function synthesize(string $voiceId, string $text, string $outputFormat = 'ulaw_8000', ?int $botId = null, ?int $tenantId = null): ?string
    {
        if (!$this->isConfigured() || empty($text)) {
            return null;
        }

        $stability = (float) PlatformSetting::get('elevenlabs_stability', 0.7);
        $similarityBoost = (float) PlatformSetting::get('elevenlabs_similarity_boost', 0.75);
        $charCount = mb_strlen($text);
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(
                "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}?output_format={$outputFormat}",
                [
                    'text' => $text,
                    'model_id' => $this->modelId(),
                    'voice_settings' => [
                        'stability' => $stability,
                        'similarity_boost' => $similarityBoost,
                    ],
                ]
            );

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                // ElevenLabs TTS: ~$0.30/1000 chars = 0.03 cents/char
                $costCents = $charCount * 0.03;

                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => 'elevenlabs',
                        'model' => 'tts',
                        'input_tokens' => 0,
                        'output_tokens' => $charCount,
                        'cost_cents' => $costCents,
                        'response_time_ms' => $responseTimeMs,
                        'status' => 'success',
                        'error_type' => null,
                        'bot_id' => $botId,
                        'tenant_id' => $tenantId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
                }

                return base64_encode($response->body());
            }

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'elevenlabs',
                    'model' => 'tts',
                    'input_tokens' => 0,
                    'output_tokens' => $charCount,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => 'http_' . $response->status(),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
            }

            Log::error('ElevenLabs synthesize failed', [
                'status' => $response->status(),
                'voice_id' => $voiceId,
            ]);
            return null;
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'elevenlabs',
                    'model' => 'tts',
                    'input_tokens' => 0,
                    'output_tokens' => $charCount,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => get_class($e),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $metricEx) {
                Log::warning('Failed to record API metric', ['error' => $metricEx->getMessage()]);
            }

            Log::error('ElevenLabs synthesize exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Synthesize with streaming - yields base64 audio chunks.
     */
    public function synthesizeStream(string $voiceId, string $text, string $outputFormat = 'ulaw_8000', ?int $botId = null, ?int $tenantId = null): \Generator
    {
        if (!$this->isConfigured() || empty($text)) {
            return;
        }

        $stability = (float) PlatformSetting::get('elevenlabs_stability', 0.7);
        $similarityBoost = (float) PlatformSetting::get('elevenlabs_similarity_boost', 0.75);
        $charCount = mb_strlen($text);
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(30)->withOptions([
                'stream' => true,
            ])->post(
                "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}/stream?output_format={$outputFormat}",
                [
                    'text' => $text,
                    'model_id' => $this->modelId(),
                    'voice_settings' => [
                        'stability' => $stability,
                        'similarity_boost' => $similarityBoost,
                    ],
                ]
            );

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                // ElevenLabs TTS: ~$0.30/1000 chars = 0.03 cents/char
                $costCents = $charCount * 0.03;

                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => 'elevenlabs',
                        'model' => 'tts',
                        'input_tokens' => 0,
                        'output_tokens' => $charCount,
                        'cost_cents' => $costCents,
                        'response_time_ms' => $responseTimeMs,
                        'status' => 'success',
                        'error_type' => null,
                        'bot_id' => $botId,
                        'tenant_id' => $tenantId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
                }

                $body = $response->toPsrResponse()->getBody();
                $buffer = '';
                $chunkSize = 8000; // ~1 second of ulaw_8000

                while (!$body->eof()) {
                    $buffer .= $body->read(4096);

                    while (strlen($buffer) >= $chunkSize) {
                        $chunk = substr($buffer, 0, $chunkSize);
                        $buffer = substr($buffer, $chunkSize);
                        yield base64_encode($chunk);
                    }
                }

                if (strlen($buffer) > 0) {
                    yield base64_encode($buffer);
                }
            } else {
                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => 'elevenlabs',
                        'model' => 'tts',
                        'input_tokens' => 0,
                        'output_tokens' => $charCount,
                        'cost_cents' => 0,
                        'response_time_ms' => $responseTimeMs,
                        'status' => 'error',
                        'error_type' => 'http_' . $response->status(),
                        'bot_id' => $botId,
                        'tenant_id' => $tenantId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
                }

                Log::error('ElevenLabs synthesizeStream failed', [
                    'status' => $response->status(),
                    'voice_id' => $voiceId,
                ]);
            }
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'elevenlabs',
                    'model' => 'tts',
                    'input_tokens' => 0,
                    'output_tokens' => $charCount,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => get_class($e),
                    'bot_id' => $botId,
                    'tenant_id' => $tenantId,
                ]);
            } catch (\Exception $metricEx) {
                Log::warning('Failed to record API metric', ['error' => $metricEx->getMessage()]);
            }

            Log::error('ElevenLabs synthesizeStream exception', ['error' => $e->getMessage()]);
        }
    }
}
