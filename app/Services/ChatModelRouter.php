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
     * Decide which model tier to use based on query complexity and conversation context.
     */
    public function route(string $userMessage, int $historyCount = 0): array
    {
        $message = mb_strtolower(trim($userMessage));
        $wordCount = str_word_count($message);

        // Short continuations (<8 words) in long conversations stay on fast
        // (e.g., "da", "ok", "si cat costa?", "altceva?")
        if ($wordCount < 8 && $historyCount > 4) {
            return $this->models['fast'];
        }

        if ($this->isComplex($message, $wordCount)) {
            return $this->models['smart'];
        }

        // Conversations with >10 messages may need more context understanding
        if ($historyCount > 10 && $wordCount > 15) {
            return $this->models['smart'];
        }

        return $this->models['fast'];
    }

    private function isComplex(string $message, int $wordCount): bool
    {
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
            // Delivery / timing
            '/cat.*dureaz|termen.*livr|cand.*ajung|cand.*primesc|timp.*livr/u',
            // Warranty / legal
            '/garantie|retur|schimb|reclam|drept.*consum|legal/u',
            // Custom orders / services
            '/comanda.*special|personali|la.*comanda|servicii.*domicili|montaj/u',
            // Multiple question marks = complex inquiry
            '/\?.*\?/',
        ];

        foreach ($complexPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        // Long messages with context are likely complex
        if ($wordCount > 30) {
            return true;
        }

        return false;
    }
}
