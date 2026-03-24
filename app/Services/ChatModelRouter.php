<?php

namespace App\Services;

class ChatModelRouter
{
    /**
     * Available model tiers, ordered by capability.
     *
     * Each tier has a provider, model ID, and parameters.
     * Provider support: 'openai', 'anthropic'
     */
    private array $models = [
        'fast' => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'max_tokens' => 500,
            'temperature' => 0.6,
        ],
        'smart' => [
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-5-20241022',
            'max_tokens' => 800,
            'temperature' => 0.5,
        ],
    ];

    /**
     * Decide which model tier to use based on query complexity.
     */
    public function route(string $userMessage, int $historyCount = 0): array
    {
        $message = mb_strtolower(trim($userMessage));

        if ($this->isComplex($message)) {
            return $this->models['smart'];
        }

        return $this->models['fast'];
    }

    private function isComplex(string $message): bool
    {
        $wordCount = str_word_count($message);

        // Complex indicators: calculations, comparisons, multi-criteria, advice
        $complexPatterns = [
            '/\d+\s*(mp|m2|m²|metri|litri|l|kg|bucati|buc)/',   // quantities / calculations
            '/recomand|suger|sfatu|ce.*alegi|ce.*iei|ce.*trebui|ce.*potrivit/u', // advice
            '/compar|diferent|versus|sau.*mai bun|care.*mai/u',   // comparisons
            '/proiect|renovez|construi|izol|termoizol|amenaj/u',  // project context
            '/cum.*fac|cum.*aplic|cum.*montez|cum.*instalez/u',   // how-to
            '/alternativ|inlocui|echivalent/u',                    // alternatives
            '/buget|cost.*total|cat.*cheltuiesc|cat.*costa.*pentru/u', // budget
            '/avantaj|dezavantaj|pro.*contra|merita/u',            // pros/cons
        ];

        foreach ($complexPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        // Long messages with context are likely complex
        if ($wordCount > 20) {
            return true;
        }

        return false;
    }
}
