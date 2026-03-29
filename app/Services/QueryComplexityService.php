<?php

namespace App\Services;

/**
 * Classifies query complexity to adjust orchestration behavior.
 *
 * Simple queries → skip heavy pipelines (RAG, tools)
 * Complex queries → allow more context, more chunks, fuller search
 *
 * Returns: 'simple', 'medium', 'complex'
 */
class QueryComplexityService
{
    /** Words that signal multi-part or complex queries */
    private const COMPLEXITY_SIGNALS = [
        'comparați', 'compara', 'diferența', 'diferenta', 'versus', 'sau',
        'avantaj', 'dezavantaj', 'pro', 'contra',
        'de ce', 'cum funcționează', 'cum functioneaza', 'explică', 'explica',
        'care e mai bun', 'care sunt', 'câte', 'cate',
        'dacă', 'daca', 'în cazul', 'in cazul',
    ];

    /** Patterns for simple queries that need no heavy context */
    private const SIMPLE_PATTERNS = [
        '/^(salut|buna|hello|hei|hey|alo|ciao)\b/iu',
        '/^(mulțumesc|multumesc|mersi|ms|ok|da|nu|bine|super)\b/iu',
        '/^(la revedere|pa|bye|gata)\b/iu',
    ];

    /**
     * Classify a query into simple/medium/complex.
     */
    public function classify(string $query): string
    {
        $q = trim($query);

        // Check simple patterns first
        foreach (self::SIMPLE_PATTERNS as $pattern) {
            if (preg_match($pattern, $q)) {
                return 'simple';
            }
        }

        $wordCount = str_word_count($q);
        $qLower = mb_strtolower($q);

        // Very short queries (1-3 words) are usually simple
        if ($wordCount <= 3) {
            return 'simple';
        }

        // Check complexity signals
        $complexityHits = 0;
        foreach (self::COMPLEXITY_SIGNALS as $signal) {
            if (str_contains($qLower, $signal)) {
                $complexityHits++;
            }
        }

        // Multiple questions (contains '?' more than once, or 'și' connecting clauses)
        $questionMarks = substr_count($q, '?');
        if ($questionMarks >= 2) {
            $complexityHits += 2;
        }

        // Long queries with multiple clauses
        if ($wordCount >= 15) {
            $complexityHits++;
        }

        // Vague queries (very short with no specific noun) — treat as complex
        // because the LLM needs to ask for clarification
        if ($wordCount <= 5 && !preg_match('/\b\w{5,}\b/u', $q)) {
            return 'medium'; // vague, needs clarification
        }

        if ($complexityHits >= 2) {
            return 'complex';
        }
        if ($complexityHits >= 1 || $wordCount >= 10) {
            return 'medium';
        }

        return 'medium'; // default to medium (safe middle ground)
    }

    /**
     * Get recommended RAG limit for a complexity level.
     */
    public function ragLimit(string $complexity): int
    {
        return match ($complexity) {
            'simple' => 0,
            'complex' => 5,
            default => 3,
        };
    }

    /**
     * Whether RAG should be skipped entirely.
     */
    public function shouldSkipRag(string $complexity): bool
    {
        return $complexity === 'simple';
    }
}
