<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatModelRouter
{
    /**
     * Decide which model tier to use based on query complexity, conversation context,
     * cost budget, voice channel, and circuit breaker state.
     */
    public function route(
        string $userMessage,
        int $historyCount = 0,
        int $conversationCostCents = 0,
        bool $isVoiceChannel = false,
        string $language = 'ro',
    ): array {
        $message = mb_strtolower(trim($userMessage));
        $wordCount = str_word_count($message);
        $reasons = [];

        $tiers = config('routing.tiers');
        $fast = $tiers['fast'];
        $smart = $tiers['smart'];

        // 1. Cost-aware: force fast if conversation budget exceeded
        $budget = config('routing.cost_budget_cents', 15);
        if ($conversationCostCents > $budget) {
            $reasons[] = "cost_budget_exceeded ({$conversationCostCents}c > {$budget}c)";
            $this->logDecision('fast', $fast, $reasons, $message, $isVoiceChannel);
            return $fast;
        }

        // 2. Circuit breaker: check provider availability
        $smart = $this->applyCircuitBreaker($smart, $fast, $reasons);

        // 3. Fallback if Anthropic API key missing
        if (($smart['provider'] ?? '') === 'anthropic' && empty(config('services.anthropic.api_key', env('ANTHROPIC_API_KEY')))) {
            $reasons[] = 'anthropic_key_missing';
            $smart = $fast; // degrade to fast
        }

        // 4. Voice channel: bias toward fast for latency
        if ($isVoiceChannel) {
            // Only use smart for clearly complex queries on voice
            if (!$this->isComplex($message, $wordCount, $language)) {
                $reasons[] = 'voice_channel_fast_bias';
                $this->logDecision('fast', $fast, $reasons, $message, $isVoiceChannel);
                return $fast;
            }
            $reasons[] = 'voice_complex_override';
        }

        // 5. Short continuations stay on fast
        $shortThreshold = config('routing.short_message_threshold', 8);
        if ($wordCount < $shortThreshold && $historyCount > 4) {
            $reasons[] = 'short_continuation';
            $this->logDecision('fast', $fast, $reasons, $message, $isVoiceChannel);
            return $fast;
        }

        // 6. Complex message detection
        if ($this->isComplex($message, $wordCount, $language)) {
            $reasons[] = 'complex_query';
            $this->logDecision('smart', $smart, $reasons, $message, $isVoiceChannel);
            return $smart;
        }

        // 7. Long conversations with moderate messages
        $longConvThreshold = config('routing.long_conversation_threshold', 10);
        $longConvWordMin = config('routing.long_conversation_word_min', 15);
        if ($historyCount > $longConvThreshold && $wordCount > $longConvWordMin) {
            $reasons[] = 'long_conversation_context';
            $this->logDecision('smart', $smart, $reasons, $message, $isVoiceChannel);
            return $smart;
        }

        $reasons[] = 'default_fast';
        $this->logDecision('fast', $fast, $reasons, $message, $isVoiceChannel);
        return $fast;
    }

    private function isComplex(string $message, int $wordCount, string $language = 'ro'): bool
    {
        $patterns = config("routing.patterns.{$language}", config('routing.patterns.ro', []));

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        $threshold = config('routing.word_count_threshold', 30);
        if ($wordCount > $threshold) {
            return true;
        }

        return false;
    }

    /**
     * Check circuit breaker and swap provider if needed.
     */
    private function applyCircuitBreaker(array $smart, array $fast, array &$reasons): array
    {
        $provider = $smart['provider'] ?? 'anthropic';

        if ($this->isCircuitOpen($provider)) {
            $reasons[] = "circuit_open_{$provider}";
            return $fast;
        }

        return $smart;
    }

    /**
     * Circuit breaker: Redis-backed, fail fast if >80% fail rate.
     */
    public function isCircuitOpen(string $provider): bool
    {
        $config = config('routing.circuit_breaker', []);
        $window = $config['window_minutes'] ?? 5;
        $minRequests = $config['min_requests'] ?? 5;
        $failThreshold = $config['fail_rate_threshold'] ?? 0.8;

        $failures = (int) Cache::get("routing_cb_{$provider}_fail", 0);
        $successes = (int) Cache::get("routing_cb_{$provider}_ok", 0);
        $total = $failures + $successes;

        if ($total < $minRequests) {
            return false;
        }

        return ($failures / $total) > $failThreshold;
    }

    public function recordSuccess(string $provider): void
    {
        $window = config('routing.circuit_breaker.window_minutes', 5);
        $key = "routing_cb_{$provider}_ok";
        Cache::increment($key);
        Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes($window));
    }

    public function recordFailure(string $provider): void
    {
        $window = config('routing.circuit_breaker.window_minutes', 5);
        $key = "routing_cb_{$provider}_fail";
        Cache::increment($key);
        Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes($window));
    }

    /**
     * Log routing decision for analytics.
     */
    private function logDecision(string $tier, array $config, array $reasons, string $message, bool $isVoice): void
    {
        Log::debug('ChatModelRouter: routing decision', [
            'tier' => $tier,
            'provider' => $config['provider'] ?? 'unknown',
            'model' => $config['model'] ?? 'unknown',
            'reasons' => $reasons,
            'voice_channel' => $isVoice,
            'message_length' => mb_strlen($message),
            'word_count' => str_word_count($message),
        ]);
    }
}
