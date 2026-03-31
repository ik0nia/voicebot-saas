<?php

namespace App\Services;

/**
 * Manages context token budgets per channel, ensuring the prompt stays within
 * limits by trimming lower-priority data first.
 *
 * Priority order (highest first):
 * 1. Products (direct revenue impact)
 * 2. RAG knowledge
 * 3. Conversation summary
 * 4. Recent messages
 */
class ContextBudgetService
{
    private const BUDGETS = [
        'chat'  => 3500,
        'voice' => 1600,
    ];

    private TokenizerService $tokenizer;

    public function __construct(TokenizerService $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    /**
     * Trim context blocks to fit within the channel's token budget.
     *
     * @param array $blocks Keyed by priority: ['products' => '...', 'knowledge' => '...', 'summary' => '...', 'history' => [...]]
     * @param string $channel 'chat' or 'voice'
     * @return array Same keys, with values trimmed or emptied if over budget
     */
    public function fit(array $blocks, string $channel = 'chat'): array
    {
        $budget = self::BUDGETS[$channel] ?? self::BUDGETS['chat'];

        // Priority order — highest first (trim from bottom up)
        $priorities = ['products', 'knowledge', 'summary', 'history'];
        $tokens = [];

        foreach ($priorities as $key) {
            if (!isset($blocks[$key]) || $blocks[$key] === '' || $blocks[$key] === []) {
                $tokens[$key] = 0;
                continue;
            }
            $text = is_array($blocks[$key]) ? $this->historyToText($blocks[$key]) : $blocks[$key];
            $tokens[$key] = $this->tokenizer->count($text);
        }

        $total = array_sum($tokens);

        if ($total <= $budget) {
            return $blocks; // Fits, no trimming needed
        }

        // Trim from lowest priority first
        $reversed = array_reverse($priorities);
        foreach ($reversed as $key) {
            if ($total <= $budget) {
                break;
            }

            $excess = $total - $budget;

            if ($tokens[$key] <= 0) {
                continue;
            }

            if ($tokens[$key] <= $excess) {
                // Remove this block entirely
                $total -= $tokens[$key];
                $tokens[$key] = 0;
                $blocks[$key] = is_array($blocks[$key]) ? [] : '';
            } else {
                // Truncate this block to fit
                $allowedTokens = $tokens[$key] - $excess;
                $blocks[$key] = $this->truncateToTokens($blocks[$key], $allowedTokens);
                $total = $budget;
            }
        }

        return $blocks;
    }

    /**
     * Get the token budget for a channel.
     */
    public function getBudget(string $channel): int
    {
        return self::BUDGETS[$channel] ?? self::BUDGETS['chat'];
    }

    /**
     * Truncate a text or history array to approximately N tokens.
     */
    private function truncateToTokens(string|array $content, int $maxTokens): string|array
    {
        if (is_array($content)) {
            return $this->truncateHistory($content, $maxTokens);
        }

        $words = explode(' ', $content);
        $result = '';
        $tokens = 0;

        foreach ($words as $word) {
            $wordTokens = $this->tokenizer->count($word . ' ');
            if ($tokens + $wordTokens > $maxTokens) {
                break;
            }
            $result .= $word . ' ';
            $tokens += $wordTokens;
        }

        return rtrim($result);
    }

    /**
     * Truncate message history from the oldest, keeping recent messages.
     */
    private function truncateHistory(array $messages, int $maxTokens): array
    {
        // Keep from the end (most recent)
        $kept = [];
        $tokens = 0;

        foreach (array_reverse($messages) as $msg) {
            $msgTokens = $this->tokenizer->count($msg['content'] ?? '') + 4;
            if ($tokens + $msgTokens > $maxTokens) {
                break;
            }
            array_unshift($kept, $msg);
            $tokens += $msgTokens;
        }

        return $kept;
    }

    private function historyToText(array $messages): string
    {
        return implode("\n", array_map(fn($m) => $m['content'] ?? '', $messages));
    }
}
