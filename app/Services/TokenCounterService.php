<?php

namespace App\Services;

class TokenCounterService
{
    private const CHARS_PER_TOKEN = 4;

    /**
     * Estimate token count for a string.
     */
    public function estimate(string $text): int
    {
        return (int) ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * Estimate token count for a messages array.
     */
    public function estimateMessages(array $messages): int
    {
        $total = 0;
        foreach ($messages as $msg) {
            $total += $this->estimate($msg['content'] ?? '');
            $total += 4; // role + overhead tokens
        }
        return $total;
    }

    /**
     * Truncate message history to fit within token budget.
     * Keeps system messages and the last N messages that fit.
     */
    public function truncateHistory(array $messages, int $maxTokens): array
    {
        $systemMessages = [];
        $conversationMessages = [];

        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                $systemMessages[] = $msg;
            } else {
                $conversationMessages[] = $msg;
            }
        }

        $systemTokens = $this->estimateMessages($systemMessages);
        $remainingBudget = $maxTokens - $systemTokens;

        if ($remainingBudget <= 0) {
            return $systemMessages;
        }

        // Keep messages from the end (most recent) that fit in budget
        $kept = [];
        $usedTokens = 0;
        foreach (array_reverse($conversationMessages) as $msg) {
            $msgTokens = $this->estimate($msg['content'] ?? '') + 4;
            if ($usedTokens + $msgTokens > $remainingBudget) {
                break;
            }
            $kept[] = $msg;
            $usedTokens += $msgTokens;
        }

        return array_merge($systemMessages, array_reverse($kept));
    }
}
