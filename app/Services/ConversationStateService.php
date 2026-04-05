<?php

namespace App\Services;

/**
 * Server-side conversation state tracking for voice calls.
 *
 * Maintains per-call context that survives across turns, enabling proper
 * handling of follow-up utterances like "da", "primul", "cât costă ăla?"
 * without relying solely on OpenAI's session memory.
 *
 * State is held in-memory (per MediaStreamHandler process lifetime)
 * and is lightweight — no DB or cache writes needed.
 */
class ConversationStateService
{
    /** @var array<string, array> Per-call state indexed by call ID */
    private array $states = [];

    /**
     * Get or initialize state for a call.
     */
    public function getState(string $callId): array
    {
        if (!isset($this->states[$callId])) {
            $this->states[$callId] = $this->defaultState();
        }

        return $this->states[$callId];
    }

    /**
     * Record a user turn: what they asked, what intent was detected, what products/categories were involved.
     */
    public function recordUserTurn(string $callId, array $turnData): void
    {
        $state = $this->getState($callId);

        // Shift current to previous
        $state['previous_intent'] = $state['last_intent'];
        $state['previous_products'] = $state['last_products'];
        $state['previous_category'] = $state['last_category'];
        $state['previous_brand'] = $state['last_brand'];
        $state['previous_transcript'] = $state['last_transcript'];

        // Set current turn
        $state['last_intent'] = $turnData['intent'] ?? null;
        $state['last_transcript'] = $turnData['transcript'] ?? null;
        $state['last_products'] = $turnData['products'] ?? [];
        $state['last_category'] = $turnData['category'] ?? $state['last_category'];
        $state['last_brand'] = $turnData['brand'] ?? $state['last_brand'];
        $state['turn_count']++;
        $state['last_turn_at'] = microtime(true);

        $this->states[$callId] = $state;
    }

    /**
     * Detect if the current transcript is a follow-up to the previous turn.
     *
     * A follow-up is a short, contextual response that references the previous
     * turn implicitly (e.g., "da", "primul", "cât costă?", "ăla roșu").
     *
     * @return array|null Follow-up context or null if not a follow-up
     */
    public function detectFollowUp(string $callId, string $transcript): ?array
    {
        $state = $this->getState($callId);

        // No previous turn — can't be a follow-up
        if ($state['turn_count'] < 1 || !$state['last_intent']) {
            return null;
        }

        $lower = mb_strtolower(trim($transcript));
        $normalized = $this->removeDiacritics($lower);
        $wordCount = count(preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY));

        // Only consider short utterances as potential follow-ups (max 6 words)
        if ($wordCount > 6) {
            return null;
        }

        // Confirmation patterns
        $isConfirmation = (bool) preg_match(
            '/^(da|sigur|exact|corect|aha|mhm|ok|bine|desigur|normal|perfect|super|gata|hai|vreau|doresc|asta|acela|aia|ăla|ăsta|primul|ultimul|al doilea|al treilea)\b/u',
            $normalized
        );

        // Price inquiry follow-up
        $isPriceFollowUp = (bool) preg_match(
            '/^(cat costa|cât costă|pretul|prețul|ce pret|ce preț|cat e|cât e|la ce pret|la ce preț)/u',
            $normalized
        );

        // Selection pattern ("pe ăla", "primul", "al doilea", ordinal references)
        $isSelection = (bool) preg_match(
            '/\b(primul|prima|ultimul|ultima|al doilea|a doua|al treilea|a treia|ala|ăla|asta|ăsta|aia|acela|aceea|cel de|cea de|cel cu|cea cu)\b/u',
            $normalized
        );

        // "More info" follow-up
        $isMoreInfo = (bool) preg_match(
            '/\b(mai multe|detalii|specificat|dimensiun|ce mai|altceva|si ce|și ce|alte|alt|alta|altă)\b/u',
            $normalized
        );

        if (!$isConfirmation && !$isPriceFollowUp && !$isSelection && !$isMoreInfo) {
            return null;
        }

        // Build follow-up context from previous turn
        $followUp = [
            'type' => 'follow_up',
            'previous_intent' => $state['last_intent'],
            'previous_transcript' => $state['last_transcript'],
        ];

        // Re-attach previous products for price/selection follow-ups
        if (($isPriceFollowUp || $isSelection) && !empty($state['last_products'])) {
            $followUp['products'] = $state['last_products'];
            $followUp['context_hint'] = 'Clientul se referă la produsele menționate anterior.';
        }

        // Re-attach category/brand context
        if ($state['last_category']) {
            $followUp['category'] = $state['last_category'];
        }
        if ($state['last_brand']) {
            $followUp['brand'] = $state['last_brand'];
        }

        // Specific follow-up type hints for the AI
        if ($isConfirmation) {
            $followUp['follow_up_type'] = 'confirmation';
            $followUp['context_hint'] = ($followUp['context_hint'] ?? '')
                . ' Clientul a confirmat. Continuă cu pasul următor din conversație.';
        } elseif ($isPriceFollowUp) {
            $followUp['follow_up_type'] = 'price_inquiry';
            $followUp['context_hint'] = ($followUp['context_hint'] ?? '')
                . ' Clientul întreabă de preț pentru produsele discutate anterior.';
        } elseif ($isSelection) {
            $followUp['follow_up_type'] = 'selection';
            $followUp['context_hint'] = ($followUp['context_hint'] ?? '')
                . ' Clientul selectează un produs din lista anterioară.';
        } elseif ($isMoreInfo) {
            $followUp['follow_up_type'] = 'more_info';
            $followUp['context_hint'] = ($followUp['context_hint'] ?? '')
                . ' Clientul vrea mai multe detalii despre subiectul anterior.';
        }

        return $followUp;
    }

    /**
     * Build context string for injection into the AI prompt when a follow-up is detected.
     */
    public function buildFollowUpContext(array $followUp): string
    {
        $context = "\n\n=== CONTEXT CONVERSAȚIE ANTERIOARĂ ===";
        $context .= "\nClientul continuă o discuție anterioară.";

        if (!empty($followUp['previous_transcript'])) {
            $context .= "\nÎntrebarea anterioară: \"{$followUp['previous_transcript']}\"";
        }

        if (!empty($followUp['products'])) {
            $context .= "\nProduse discutate anterior:\n";
            foreach ($followUp['products'] as $p) {
                $name = $p['name'] ?? $p->name ?? '';
                $price = $p['price'] ?? $p->price ?? '';
                $currency = $p['currency'] ?? $p->currency ?? 'RON';
                $context .= "- {$name}: {$price} {$currency}\n";
            }
        }

        if (!empty($followUp['category'])) {
            $context .= "\nCategoria discutată: {$followUp['category']}";
        }

        if (!empty($followUp['brand'])) {
            $context .= "\nBrandul discutat: {$followUp['brand']}";
        }

        if (!empty($followUp['context_hint'])) {
            $context .= "\n\nINSTRUCȚIUNI: " . trim($followUp['context_hint']);
        }

        $context .= "\n=== SFÂRȘIT CONTEXT ANTERIOR ===";

        return $context;
    }

    /**
     * Reset state for a call (call ended).
     */
    public function resetCall(string $callId): void
    {
        unset($this->states[$callId]);
    }

    /**
     * Get the last products shown in a call (for re-search on follow-ups).
     *
     * @return array Product objects/arrays from the last turn
     */
    public function getLastProducts(string $callId): array
    {
        return $this->states[$callId]['last_products'] ?? [];
    }

    /**
     * Get the last category discussed in a call.
     */
    public function getLastCategory(string $callId): ?string
    {
        return $this->states[$callId]['last_category'] ?? null;
    }

    /**
     * Get the last brand discussed in a call.
     */
    public function getLastBrand(string $callId): ?string
    {
        return $this->states[$callId]['last_brand'] ?? null;
    }

    // -----------------------------------------------------------------

    private function defaultState(): array
    {
        return [
            'last_intent' => null,
            'last_transcript' => null,
            'last_products' => [],
            'last_category' => null,
            'last_brand' => null,
            'previous_intent' => null,
            'previous_transcript' => null,
            'previous_products' => [],
            'previous_category' => null,
            'previous_brand' => null,
            'turn_count' => 0,
            'last_turn_at' => null,
        ];
    }

    private function removeDiacritics(string $text): string
    {
        return str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
            $text
        );
    }
}
