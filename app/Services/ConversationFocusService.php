<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

/**
 * Conversation Focus Service.
 *
 * Tracks the active product topic across conversation turns so that short
 * follow-up messages ("pentru exterior, 10cm") are combined with the topic
 * ("polistiren") when running product search. Without this, product search
 * runs on each user message in isolation and loses the conversation context.
 *
 * Storage: conversation.metadata['focus'] with shape:
 * {
 *   "active_topic": "polistiren",
 *   "set_at_turn": 3,
 *   "reinforced_at_turn": 5,
 *   "ttl_turns": 5,
 *   "raw_last_query": "polistiren"
 * }
 *
 * Design principle: err on the side of CLEARING focus when unsure —
 * false-keeping a stale topic (like conv 130) is worse than false-resetting.
 */
class ConversationFocusService
{
    /** Default follow-up TTL in turns (user messages) before focus expires. */
    private const DEFAULT_TTL_TURNS = 5;

    /** Max words for a message to be treated as a short follow-up. */
    private const SHORT_FOLLOWUP_MAX_WORDS = 4;

    /** Minimum length for a token to count as a product-type candidate. */
    private const MIN_TOKEN_LEN = 4;

    /**
     * Romanian/English stopwords that should NEVER be treated as a new
     * product topic. Kept tight — things that look like product nouns
     * (polistiren, adeziv, ciment) are intentionally NOT listed here.
     */
    private const STOPWORDS = [
        // Function words
        'un', 'una', 'de', 'la', 'pe', 'in', 'nu', 'am', 'cu', 'sa',
        'ce', 'al', 'ai', 'ei', 'le', 'se', 'ne', 'te',
        'si', 'va', 'ar', 'fi', 'ca', 'da', 'ok',
        'pentru', 'care', 'sunt', 'este', 'din', 'sau', 'dar', 'cum',
        'cat', 'ale', 'cel', 'lui', 'lor', 'unde', 'asta', 'prin',
        'daca', 'asa', 'tot', 'mai', 'prea', 'ceva', 'niste',
        // Generic quantity/dimension tokens
        'mult', 'putin', 'cativa', 'cateva', 'mare', 'mic', 'mici', 'mari',
        'bun', 'buna', 'noi', 'nou',
        // Directions / usage qualifiers (NOT product types)
        'exterior', 'interior', 'afara', 'inauntru', 'sus', 'jos',
        'stanga', 'dreapta', 'gros', 'subtire', 'lat', 'larg',
        // Generic commerce verbs
        'caut', 'cauta', 'vreau', 'vrea', 'doresc', 'trebuie', 'gasesc',
        'cumpar', 'cumpara', 'arata', 'spune', 'avea', 'exista',
        'aveti', 'avem', 'are', 'au', 'ati', 'aveau',
        'puteti', 'pot', 'poti', 'putem', 'recomanda', 'sugerati',
        'gasiti', 'vinde', 'vand', 'vindeti',
        // "produse" / generic product noun
        'produs', 'produse', 'produsul', 'produsele', 'articol',
        // Units
        'cm', 'mm', 'm', 'kg', 'ml', 'l', 'mp',
        // Polite/acknowledgement
        'salut', 'buna', 'bun', 'multumesc', 'merci',
    ];

    /**
     * Returns the query string to use for product search.
     * Combines the active focus with a short follow-up message when appropriate.
     *
     * Rules:
     *  1. No active focus → return $userMessage unchanged.
     *  2. Short message + no own product type → "{active_topic} {userMessage}".
     *  3. Message has its own product type → return as-is (new search).
     */
    public function augmentQuery(Conversation $conversation, string $userMessage): string
    {
        $focus = $this->getFocus($conversation);
        if (!$focus || empty($focus['active_topic'])) {
            return $userMessage;
        }

        $trimmed = trim($userMessage);
        if ($trimmed === '') {
            return $userMessage;
        }

        $activeTopic = (string) $focus['active_topic'];
        $normalized = $this->normalize($trimmed);
        $tokens = $this->tokenize($normalized);
        $wordCount = count($tokens);

        // If the message already contains the active topic (or its stem),
        // no augmentation needed — it's already specific.
        $topicStem = $this->stem($this->normalize($activeTopic));
        foreach ($tokens as $tok) {
            if ($this->stem($tok) === $topicStem) {
                return $userMessage;
            }
        }

        $candidateProductType = $this->extractCandidateProductType($tokens);

        // If the message has its own product-type candidate, it's a NEW search
        // — do NOT augment. The updateFocus() call will switch focus after search.
        if ($candidateProductType !== null) {
            return $userMessage;
        }

        // Short follow-up with no new product type → combine.
        if ($wordCount <= self::SHORT_FOLLOWUP_MAX_WORDS) {
            return $activeTopic . ' ' . $trimmed;
        }

        // Long message with no new product type: still combine. The extra
        // context (e.g. adjectives, dimensions) is still about the same topic.
        return $activeTopic . ' ' . $trimmed;
    }

    /**
     * Update focus after a message was processed.
     *
     * @param  array  $detectedIntents  Raw detected intent dicts (from $plan->intents->toArray()).
     */
    public function updateFocus(Conversation $conversation, string $userMessage, array $detectedIntents = []): void
    {
        $currentTurn = (int) ($conversation->messages_count ?? 0);
        $focus = $this->getFocus($conversation);

        // 1. Explicit topic-shift keywords always clear focus immediately.
        if ($this->isTopicShiftSignal($userMessage)) {
            $this->clearFocus($conversation);
            return;
        }

        // 2. TTL expiry check — if existing focus is past its TTL, clear it.
        if ($focus) {
            $ttl = (int) ($focus['ttl_turns'] ?? self::DEFAULT_TTL_TURNS);
            $reinforcedAt = (int) ($focus['reinforced_at_turn'] ?? $focus['set_at_turn'] ?? $currentTurn);
            if ($currentTurn - $reinforcedAt > $ttl) {
                $this->clearFocus($conversation);
                $focus = null;
            }
        }

        // 3. Detect whether this message carries its own product-type candidate.
        $normalized = $this->normalize($userMessage);
        $tokens = $this->tokenize($normalized);
        $candidate = $this->extractCandidateProductType($tokens);

        $isProductSearchIntent = false;
        foreach ($detectedIntents as $intent) {
            $name = is_array($intent) ? ($intent['name'] ?? null) : (is_object($intent) ? ($intent->name ?? null) : null);
            if (in_array($name, ['product_search', 'new_order_intent', 'category_recommendation'], true)) {
                $isProductSearchIntent = true;
                break;
            }
        }

        if ($candidate !== null && $isProductSearchIntent) {
            // New product topic explicitly mentioned → set/replace focus.
            if (!$focus || $this->stem($candidate) !== $this->stem($this->normalize((string) ($focus['active_topic'] ?? '')))) {
                $this->setFocus($conversation, $candidate, $userMessage, $currentTurn);
                return;
            }
            // Same topic mentioned again — reinforce.
            $this->reinforceFocus($conversation, $focus, $currentTurn);
            return;
        }

        // 4. Follow-up with an existing focus and no new product type → reinforce.
        if ($focus && !$candidate) {
            $this->reinforceFocus($conversation, $focus, $currentTurn);
            return;
        }

        // 5. No focus and nothing to set — leave state untouched.
    }

    /**
     * Force-clear the focus from conversation metadata.
     */
    public function clearFocus(Conversation $conversation): void
    {
        $meta = $conversation->metadata ?? [];
        if (!array_key_exists('focus', $meta)) {
            return;
        }
        unset($meta['focus']);
        try {
            $conversation->update(['metadata' => $meta]);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService: clearFocus failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return the current focus data, or null if none.
     *
     * @return array{active_topic:string,set_at_turn:int,reinforced_at_turn:int,ttl_turns:int,raw_last_query:string}|null
     */
    public function getFocus(Conversation $conversation): ?array
    {
        $meta = $conversation->metadata ?? [];
        $focus = $meta['focus'] ?? null;
        if (!is_array($focus) || empty($focus['active_topic'])) {
            return null;
        }
        return $focus;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function setFocus(Conversation $conversation, string $topic, string $rawQuery, int $currentTurn): void
    {
        $meta = $conversation->metadata ?? [];
        $meta['focus'] = [
            'active_topic' => $topic,
            'set_at_turn' => $currentTurn,
            'reinforced_at_turn' => $currentTurn,
            'ttl_turns' => self::DEFAULT_TTL_TURNS,
            'raw_last_query' => $rawQuery,
        ];
        try {
            $conversation->update(['metadata' => $meta]);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService: setFocus failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reinforceFocus(Conversation $conversation, array $focus, int $currentTurn): void
    {
        $meta = $conversation->metadata ?? [];
        if (!isset($meta['focus'])) {
            return;
        }
        $meta['focus']['reinforced_at_turn'] = $currentTurn;
        try {
            $conversation->update(['metadata' => $meta]);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService: reinforceFocus failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Topic shift signals: explicit phrases that the user wants something else.
     */
    private function isTopicShiftSignal(string $message): bool
    {
        // Normalize diacritics so "lasă" matches "lasa" etc.
        $n = $this->normalize($message);

        $patterns = [
            '/\bvreau\s+altceva\b/',
            '/\bhai\s+(sa\s+)?schimb/',
            '/\bschimb(a|am|ati)?\s+(subiectul|tema|discutia)\b/',
            '/\blasa\s+asta\b/',
            '/\blasa-?ma\s+in\s+pace\b/',
            '/\baltceva\s+te\s+rog\b/',
            '/\bnu,?\s+vreau\s+(altceva|altul|alta)\b/',
            '/\b(uita|ignora)\s+(asta|de\s+asta)\b/',
            '/\bde\s+ce\s+(imi\s+)?arat[ia]\b/', // "De ce îmi arăți..." — frustration signal
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $n)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Heuristically extract a candidate product type from tokens.
     * Returns the first substantive non-stopword token of length >= MIN_TOKEN_LEN.
     */
    private function extractCandidateProductType(array $tokens): ?string
    {
        foreach ($tokens as $tok) {
            if (preg_match('/^\d+$/', $tok)) {
                continue;
            }
            // Pure dimensions like "10cm", "5mm", "2m", "500ml" — not product types.
            if (preg_match('/^\d+(cm|mm|m|kg|ml|g|l|mp)$/', $tok)) {
                continue;
            }
            if (mb_strlen($tok) < self::MIN_TOKEN_LEN) {
                continue;
            }
            if (in_array($tok, self::STOPWORDS, true)) {
                continue;
            }
            return $tok;
        }
        return null;
    }

    /**
     * Lowercase + strip diacritics + collapse punctuation/whitespace.
     */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's',
            'ț' => 't', 'ţ' => 't', 'Ă' => 'a', 'Â' => 'a', 'Î' => 'i',
            'Ș' => 's', 'Ş' => 's', 'Ț' => 't', 'Ţ' => 't',
        ]);
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim((string) $s);
    }

    private function tokenize(string $normalized): array
    {
        if ($normalized === '') return [];
        return array_values(array_filter(explode(' ', $normalized), fn($t) => $t !== ''));
    }

    /**
     * Very cheap Romanian stem — strip common plural/case suffixes.
     * Enough to match "polistiren" ~ "polistirenul" or "adezive" ~ "adeziv".
     */
    private function stem(string $token): string
    {
        $suffixes = ['urile', 'ului', 'ilor', 'elor', 'urilor', 'ele', 'ile', 'uri', 'ul', 'ii', 'ea', 'ei', 'ai', 'e', 'i', 'a'];
        foreach ($suffixes as $suf) {
            if (mb_strlen($token) > mb_strlen($suf) + 3 && str_ends_with($token, $suf)) {
                return mb_substr($token, 0, mb_strlen($token) - mb_strlen($suf));
            }
        }
        return $token;
    }
}
