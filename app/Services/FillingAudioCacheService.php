<?php

namespace App\Services;

use App\Contracts\TtsOutputStrategy;
use App\Models\Bot;
use App\Services\TtsStrategies\ElevenLabsClonedTts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Pre-generates and caches TTS audio for filling messages.
 *
 * For native OpenAI TTS: filling is sent via response.create with audio modality,
 * so we cache nothing — OpenAI generates audio in real-time (already fast).
 *
 * For cloned voice (ElevenLabs): we pre-generate audio for common filling messages
 * and cache them. At runtime, instead of calling ElevenLabs TTS (~300-600ms),
 * we serve the cached audio (~0ms). This also ensures voice consistency.
 *
 * Cache storage: filesystem (storage/app/filling-audio/{bot_id}/{hash}.ulaw)
 * Cache index: Redis/array cache with bot_id + message hash → file path mapping
 */
class FillingAudioCacheService
{
    private const CACHE_DIR = 'filling-audio';
    private const CACHE_TTL_HOURS = 168; // 7 days
    private const MAX_PRECACHE_MESSAGES = 40; // Pre-cache top N messages per bot

    private ElevenLabsService $elevenLabs;

    public function __construct()
    {
        $this->elevenLabs = app(ElevenLabsService::class);
    }

    /**
     * Get cached audio for a filling message, or null if not cached.
     *
     * @param Bot    $bot     The bot (to determine voice)
     * @param string $message The filling message text
     * @return string|null Base64-encoded ulaw audio, or null
     */
    public function getCachedAudio(Bot $bot, string $message): ?string
    {
        if (!$bot->usesClonedVoice()) {
            return null; // Native TTS doesn't need caching
        }

        $cacheKey = $this->cacheKey($bot->id, $message);

        try {
            $filePath = Cache::get($cacheKey);
            if (!$filePath) {
                return null;
            }

            if (Storage::disk('local')->exists($filePath)) {
                return base64_encode(Storage::disk('local')->get($filePath));
            }

            // File missing — clear stale cache entry
            Cache::forget($cacheKey);
        } catch (\Throwable $e) {
            Log::debug("FillingAudioCache: read failed for bot {$bot->id}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Generate and cache audio for a filling message.
     *
     * @return string|null Base64-encoded ulaw audio
     */
    public function generateAndCache(Bot $bot, string $message): ?string
    {
        if (!$bot->usesClonedVoice() || !$bot->clonedVoice) {
            return null;
        }

        $voiceId = $bot->clonedVoice->elevenlabs_voice_id;
        if (!$voiceId) {
            return null;
        }

        try {
            $audioBase64 = $this->elevenLabs->synthesize(
                $voiceId,
                $message,
                'ulaw_8000',
                $bot->id,
                $bot->tenant_id
            );

            if (!$audioBase64) {
                return null;
            }

            // Store to disk
            $hash = md5($message);
            $filePath = self::CACHE_DIR . "/{$bot->id}/{$hash}.ulaw";
            Storage::disk('local')->put($filePath, base64_decode($audioBase64));

            // Index in cache
            $cacheKey = $this->cacheKey($bot->id, $message);
            Cache::put($cacheKey, $filePath, now()->addHours(self::CACHE_TTL_HOURS));

            Log::debug("FillingAudioCache: cached audio for bot {$bot->id}", [
                'message' => mb_substr($message, 0, 50),
                'file' => $filePath,
            ]);

            return $audioBase64;

        } catch (\Throwable $e) {
            Log::warning("FillingAudioCache: generation failed for bot {$bot->id}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get audio for a filling message — from cache or generate on-the-fly.
     *
     * @return string|null Base64-encoded ulaw audio
     */
    public function getOrGenerate(Bot $bot, string $message): ?string
    {
        $cached = $this->getCachedAudio($bot, $message);
        if ($cached) {
            return $cached;
        }

        return $this->generateAndCache($bot, $message);
    }

    /**
     * Pre-generate audio for the most common filling messages.
     * Called asynchronously (e.g., from a job or artisan command) after bot setup.
     *
     * @return int Number of messages cached
     */
    public function warmCache(Bot $bot): int
    {
        if (!$bot->usesClonedVoice()) {
            return 0;
        }

        $bot->load('clonedVoice');
        if (!$bot->clonedVoice?->elevenlabs_voice_id) {
            return 0;
        }

        $fillingService = new FillingMessageService();
        $intents = [
            FillingMessageService::INTENT_GENERAL,
            FillingMessageService::INTENT_PRODUCT_SEARCH,
            FillingMessageService::INTENT_BRAND_LOOKUP,
            FillingMessageService::INTENT_CATEGORY_BROWSE,
            FillingMessageService::INTENT_PRICE_CHECK,
            FillingMessageService::INTENT_STOCK_CHECK,
            FillingMessageService::INTENT_ORDER_STATUS,
            FillingMessageService::INTENT_TECHNICAL,
        ];

        $cached = 0;
        $perIntent = (int) ceil(self::MAX_PRECACHE_MESSAGES / count($intents));

        foreach ($intents as $intent) {
            for ($i = 0; $i < $perIntent; $i++) {
                $message = $fillingService->getMessage($bot, $intent, 'warmup');

                // Skip if already cached
                if ($this->getCachedAudio($bot, $message)) {
                    $cached++;
                    continue;
                }

                $result = $this->generateAndCache($bot, $message);
                if ($result) {
                    $cached++;
                }

                // Small delay to avoid rate limiting
                usleep(100_000); // 100ms
            }
        }

        $fillingService->resetCall('warmup');

        Log::info("FillingAudioCache: warmed cache for bot {$bot->id}", [
            'cached' => $cached,
            'total_requested' => self::MAX_PRECACHE_MESSAGES,
        ]);

        return $cached;
    }

    /**
     * Clear all cached audio for a bot (e.g., when voice changes).
     */
    public function clearCache(int $botId): void
    {
        try {
            Storage::disk('local')->deleteDirectory(self::CACHE_DIR . "/{$botId}");

            // Clear all cache keys for this bot (pattern-based)
            // Since we can't iterate Redis keys easily, we track via a manifest
            $manifestKey = "filling_audio_manifest_{$botId}";
            $manifest = Cache::get($manifestKey, []);
            foreach ($manifest as $key) {
                Cache::forget($key);
            }
            Cache::forget($manifestKey);

            Log::info("FillingAudioCache: cleared cache for bot {$botId}");
        } catch (\Throwable $e) {
            Log::warning("FillingAudioCache: clear failed for bot {$botId}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Build a Telnyx-compatible audio action from cached audio.
     */
    public function buildTelnyxAudioAction(string $audioBase64, string $streamSid): array
    {
        return [
            'action' => 'send_audio_to_telnyx',
            'data' => [
                'event' => 'media',
                'streamSid' => $streamSid,
                'media' => [
                    'payload' => $audioBase64,
                ],
            ],
        ];
    }

    // -----------------------------------------------------------------

    private function cacheKey(int $botId, string $message): string
    {
        return "filling_audio_{$botId}_" . md5($message);
    }
}
