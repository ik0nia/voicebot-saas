<?php

namespace App\Services;

/**
 * Computes a confidence level for the system's ability to answer a query.
 *
 * Based on:
 * - Best RAG similarity score
 * - Number of RAG results found
 * - Whether tools returned data
 * - Whether any context was provided at all
 *
 * Returns: 'high', 'medium', or 'low'
 */
class ConfidenceService
{
    /**
     * @param float $topSimilarity Best cosine similarity from RAG (0-1)
     * @param int $ragResultsCount Number of RAG chunks returned
     * @param bool $hasProductResults Whether product search returned results
     * @param bool $hasOrderResults Whether order lookup found data
     * @param bool $contextEmpty Whether all context blocks are empty
     */
    public function evaluate(
        float $topSimilarity = 0,
        int $ragResultsCount = 0,
        bool $hasProductResults = false,
        bool $hasOrderResults = false,
        bool $contextEmpty = true,
    ): string {
        $score = 0;

        // RAG quality signals
        if ($topSimilarity >= 0.80) {
            $score += 40;
        } elseif ($topSimilarity >= 0.65) {
            $score += 25;
        } elseif ($topSimilarity >= 0.50) {
            $score += 10;
        }

        // RAG quantity
        if ($ragResultsCount >= 3) {
            $score += 20;
        } elseif ($ragResultsCount >= 1) {
            $score += 10;
        }

        // Product results are high-confidence (direct DB match)
        if ($hasProductResults) {
            $score += 30;
        }

        // Order lookup is definitive
        if ($hasOrderResults) {
            $score += 40;
        }

        // Penalty for completely empty context
        if ($contextEmpty && !$hasProductResults && !$hasOrderResults) {
            $score = max(0, $score - 20);
        }

        if ($score >= 50) {
            return 'high';
        }
        if ($score >= 25) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get prompt modifier text for low/medium confidence.
     */
    public function getPromptModifier(string $level): string
    {
        return match ($level) {
            'low' => implode("\n", [
                '',
                'ATENȚIE: Încrederea în contextul disponibil este SCĂZUTĂ.',
                '- Fii precaut în afirmații. NU face claims definitive.',
                '- Pune o întrebare de clarificare pentru a înțelege mai bine ce caută clientul.',
                '- Dacă nu ai informații suficiente, sugerează contactarea directă.',
            ]),
            'medium' => implode("\n", [
                '',
                'NOTĂ: Contextul disponibil este parțial.',
                '- Răspunde pe baza informațiilor existente, dar menționează dacă sunt incomplete.',
                '- Oferă opțiuni suplimentare (contact, vizită site) dacă răspunsul nu e complet.',
            ]),
            default => '', // high confidence — no modifier needed
        };
    }
}
