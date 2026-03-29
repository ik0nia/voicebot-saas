<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Logs all pipeline decisions for a single request.
 * Stores to Redis (short-term) and structured log (persistent).
 *
 * Usage:
 *   $logger = app(DecisionLoggerService::class);
 *   $logger->startRequest($botId, $query);
 *   $logger->logIntents($intents);
 *   $logger->logDataSources($decision);
 *   $logger->logRag($resultsCount, $topScore);
 *   $logger->logCompletion($result);
 *   $logger->finalize();
 */
class DecisionLoggerService
{
    private array $log = [];
    private float $startTime = 0;

    public function startRequest(int $botId, string $query, ?string $channel = 'chat'): void
    {
        $this->startTime = microtime(true);
        $normalizer = app(QueryNormalizerService::class);

        $this->log = [
            'bot_id' => $botId,
            'query' => mb_substr($query, 0, 300),
            'normalized_query' => $normalizer->normalize($query),
            'channel' => $channel,
            'timestamp' => now()->toIso8601String(),
            'intent_scores' => [],
            'data_sources' => [],
            'rag_results_count' => 0,
            'rag_top_score' => 0,
            'confidence' => null,
            'tool_called' => null,
            'tool_duration_ms' => 0,
            'tokens_used' => 0,
            'cost_cents' => 0,
            'latency_ms' => 0,
            'model' => null,
            'context_tokens' => [
                'products' => 0,
                'knowledge' => 0,
                'summary' => 0,
                'history' => 0,
            ],
            'fallback_used' => false,
        ];
    }

    public function logIntents(array $intents): void
    {
        $this->log['intent_scores'] = array_map(function ($intent) {
            if (is_object($intent) && property_exists($intent, 'name')) {
                return ['name' => $intent->name, 'confidence' => $intent->confidence];
            }
            if (is_array($intent)) {
                return $intent;
            }
            return ['raw' => (string) $intent];
        }, $intents);
    }

    public function logDataSources(array $decision): void
    {
        $this->log['data_sources'] = $decision;
    }

    public function logRag(int $resultsCount, float $topScore = 0): void
    {
        $this->log['rag_results_count'] = $resultsCount;
        $this->log['rag_top_score'] = round($topScore, 4);
    }

    public function logConfidence(string $level): void
    {
        $this->log['confidence'] = $level;
    }

    public function logTool(string $name, int $durationMs): void
    {
        $this->log['tool_called'] = $name;
        $this->log['tool_duration_ms'] = $durationMs;
    }

    public function logCompletion(array $result): void
    {
        $this->log['tokens_used'] = ($result['input_tokens'] ?? 0) + ($result['output_tokens'] ?? 0);
        $this->log['cost_cents'] = round($result['cost_cents'] ?? 0, 4);
        $this->log['model'] = $result['model'] ?? null;
    }

    public function logContextTokens(array $tokens): void
    {
        $this->log['context_tokens'] = $tokens;
    }

    public function logFallback(bool $used): void
    {
        $this->log['fallback_used'] = $used;
    }

    /**
     * Write the decision log to Redis (last 100 per bot) and structured log file.
     */
    public function finalize(): array
    {
        $this->log['latency_ms'] = (int) ((microtime(true) - $this->startTime) * 1000);

        // Store in Redis ring buffer (last 100 decisions per bot)
        $botId = $this->log['bot_id'] ?? 0;
        if ($botId) {
            $key = "decision_log:{$botId}";
            try {
                $existing = Cache::get($key, []);
                $existing[] = $this->log;
                // Keep last 100
                if (count($existing) > 100) {
                    $existing = array_slice($existing, -100);
                }
                Cache::put($key, $existing, now()->addHours(24));
            } catch (\Throwable $e) {
                // Redis unavailable — don't break the request
            }
        }

        // Structured log for persistent storage
        Log::channel(config('logging.rag_channel', 'stack'))->info('Decision log', $this->log);

        return $this->log;
    }

    /**
     * Get recent decision logs for a bot (from Redis).
     */
    public static function getRecent(int $botId, int $limit = 20): array
    {
        $logs = Cache::get("decision_log:{$botId}", []);
        return array_slice($logs, -$limit);
    }

    /**
     * Get the current (in-progress) log data.
     */
    public function current(): array
    {
        return $this->log;
    }
}
