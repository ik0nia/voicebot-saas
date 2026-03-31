<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Classifies user queries into semantic types and returns adaptive strategies
 * for retrieval, reranking, prompting, and response formatting.
 *
 * Types: informational, transactional, comparison, complaint, exploratory, vague, greeting
 *
 * This replaces simple complexity classification with intent-aware intelligence
 * that adapts the ENTIRE pipeline per query type.
 */
class QueryIntelligenceService
{
    /**
     * Classify a query and return full strategy.
     *
     * @return array{type: string, confidence: float, strategy: array}
     */
    public function classify(string $query, ?array $conversationContext = null): array
    {
        $q = mb_strtolower(trim($query));
        $wordCount = str_word_count($query);

        // Run classifiers in priority order
        $classifications = [
            $this->checkGreeting($q, $wordCount),
            $this->checkComplaint($q),
            $this->checkTransactional($q),
            $this->checkComparison($q),
            $this->checkInformational($q),
            $this->checkExploratory($q, $wordCount),
            $this->checkVague($q, $wordCount),
        ];

        // Pick highest confidence classification
        $best = ['type' => 'informational', 'confidence' => 0.3];
        foreach ($classifications as $c) {
            if ($c !== null && $c['confidence'] > $best['confidence']) {
                $best = $c;
            }
        }

        // Apply conversation context boosting
        if ($conversationContext) {
            $best = $this->applyContextBoost($best, $conversationContext);
        }

        $best['strategy'] = $this->getStrategy($best['type']);

        return $best;
    }

    /**
     * Get the full adaptive strategy for a query type.
     */
    public function getStrategy(string $type): array
    {
        return match($type) {
            'greeting' => [
                'rag_enabled' => false,
                'rag_limit' => 0,
                'products_enabled' => false,
                'reranking' => false,
                'query_rewrite' => false,
                'response_style' => 'brief',
                'max_response_tokens' => 60,
                'prompt_modifier' => '',
                'complexity' => 'simple',
            ],
            'transactional' => [
                'rag_enabled' => true,
                'rag_limit' => 4,
                'products_enabled' => true,
                'reranking' => true,
                'query_rewrite' => true,
                'response_style' => 'action_oriented',
                'max_response_tokens' => 200,
                'prompt_modifier' => implode("\n", [
                    'INTENT TRANZACȚIONAL detectat:',
                    '- Clientul vrea să ACȚIONEZE (comande, cumpere, programeze).',
                    '- Oferă pașii CONCREȚI pentru a finaliza acțiunea.',
                    '- Menționează prețul, disponibilitatea, și pasul următor.',
                    '- NU oferi informații excessive — concentrează-te pe a-l ajuta să finalizeze.',
                ]),
                'complexity' => 'medium',
            ],
            'comparison' => [
                'rag_enabled' => true,
                'rag_limit' => 10,
                'products_enabled' => true,
                'reranking' => true,
                'query_rewrite' => true,
                'response_style' => 'structured_comparison',
                'max_response_tokens' => 400,
                'prompt_modifier' => implode("\n", [
                    'INTENT COMPARATIV detectat:',
                    '- Clientul compară opțiuni sau cere recomandări.',
                    '- Structurează răspunsul ca o COMPARAȚIE clară.',
                    '- Evidențiază diferențele cheie (preț, caracteristici, avantaje).',
                    '- Oferă o RECOMANDARE clară la final pe baza nevoilor clientului.',
                    '- Folosește format tabel sau lista cu puncte dacă sunt 3+ opțiuni.',
                ]),
                'complexity' => 'complex',
            ],
            'complaint' => [
                'rag_enabled' => true,
                'rag_limit' => 3,
                'products_enabled' => false,
                'reranking' => false,
                'query_rewrite' => false,
                'response_style' => 'empathetic',
                'max_response_tokens' => 200,
                'prompt_modifier' => implode("\n", [
                    'RECLAMAȚIE detectată:',
                    '- Recunoaște PRIMA DATĂ problema clientului.',
                    '- NU încerca să vinzi nimic.',
                    '- Oferă soluții concrete sau pași de rezolvare.',
                    '- Dacă nu poți rezolva, oferă escaladare la operator.',
                    '- Fii empatic dar profesionist. NU fi defensiv.',
                ]),
                'complexity' => 'medium',
            ],
            'exploratory' => [
                'rag_enabled' => true,
                'rag_limit' => 8,
                'products_enabled' => true,
                'reranking' => true,
                'query_rewrite' => true,
                'response_style' => 'consultative',
                'max_response_tokens' => 350,
                'prompt_modifier' => implode("\n", [
                    'INTENT EXPLORATIV detectat:',
                    '- Clientul explorează opțiuni, nu a decis încă.',
                    '- Oferă o prezentare generală a opțiunilor disponibile.',
                    '- Pune 1-2 întrebări de clarificare pentru a restrânge opțiunile.',
                    '- NU împinge spre o decizie — ghidează cu răbdare.',
                ]),
                'complexity' => 'medium',
            ],
            'vague' => [
                'rag_enabled' => true,
                'rag_limit' => 3,
                'products_enabled' => false,
                'reranking' => false,
                'query_rewrite' => true,
                'response_style' => 'clarifying',
                'max_response_tokens' => 120,
                'prompt_modifier' => implode("\n", [
                    'QUERY VAG detectat:',
                    '- Întrebarea clientului nu este clară.',
                    '- Pune O SINGURĂ întrebare specifică de clarificare.',
                    '- Oferă 2-3 opțiuni concrete din care să aleagă.',
                    '- Exemplu: "Vrei informații despre produse, despre o comandă existentă, sau altceva?"',
                ]),
                'complexity' => 'simple',
            ],
            // informational = default
            default => [
                'rag_enabled' => true,
                'rag_limit' => 6,
                'products_enabled' => false,
                'reranking' => true,
                'query_rewrite' => true,
                'response_style' => 'informative',
                'max_response_tokens' => 250,
                'prompt_modifier' => '',
                'complexity' => 'medium',
            ],
        };
    }

    private function checkGreeting(string $q, int $wordCount): ?array
    {
        if ($wordCount > 8) return null;

        $greetings = ['salut', 'buna', 'bună', 'hey', 'hello', 'hi', 'hei', 'alo',
                       'buna ziua', 'buna dimineata', 'buna seara', 'ciao'];
        $thanks = ['multumesc', 'mulțumesc', 'mersi', 'merci', 'ms'];
        $followups = ['da', 'nu', 'ok', 'bine', 'perfect', 'super', 'sigur', 'exact',
                      'corect', 'desigur', 'aha', 'mhm', 'inteleg', 'am inteles'];

        $trimmed = trim($q, ' !.,?');
        if (in_array($trimmed, $followups)) {
            return ['type' => 'greeting', 'confidence' => 0.95];
        }

        $words = preg_split('/[\s,!.]+/', $q);
        foreach ($words as $w) {
            if (in_array($w, $greetings) || in_array($w, $thanks)) {
                return ['type' => 'greeting', 'confidence' => $wordCount <= 4 ? 0.95 : 0.7];
            }
        }

        return null;
    }

    private function checkComplaint(string $q): ?array
    {
        $strongPatterns = [
            '/reclam[aă]ți/u', '/reclam[aă]/u', '/nemulțumit/u', '/nemultumit/u',
            '/dezam[aă]gi/u', '/supărat/u', '/suparat/u', '/scandalos/u', '/inadmisibil/u',
        ];
        $mildPatterns = [
            '/nu funcțion/u', '/nu function/u', '/nu merge/u', '/defect/u',
            '/stricat/u', '/problema/u', '/prost/u', '/rău/u',
            '/nu.*primit/u', '/nu.*ajuns/u', '/nu.*livrat/u',
        ];

        foreach ($strongPatterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'complaint', 'confidence' => 0.9];
        }
        foreach ($mildPatterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'complaint', 'confidence' => 0.7];
        }

        return null;
    }

    private function checkTransactional(string $q): ?array
    {
        $strongPatterns = [
            '/vreau\s+s[aă]\s+(comand|cumpar|cump[aă]r|achizit|plasez)/u',
            '/a[sșş]\s+(dori|vrea)\s+s[aă]/u',
            '/[iî]l\s+(comand|cump[aă]r|vreau)/u',
            '/adaug[aă]?\s+(in|în)\s+co[sș]/u',
            '/doresc\s+s[aă]/u',
            '/pot\s+s[aă]\s+(comand|cumpar)/u',
            '/vreau\s+s[aă]\s+programez/u',
            '/rezerv/u',
        ];
        $mildPatterns = [
            '/c[aâ]t\s+cost[aă]/u',
            '/pre[tț]/u',
            '/pret/u',
            '/stoc/u',
            '/disponibil/u',
        ];

        foreach ($strongPatterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'transactional', 'confidence' => 0.9];
        }
        foreach ($mildPatterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'transactional', 'confidence' => 0.6];
        }

        return null;
    }

    private function checkComparison(string $q): ?array
    {
        $patterns = [
            '/compar/u', '/diferen[tț]/u', '/versus/u', '/\bvs\.?\b/u',
            '/care\s+e\s+mai\s+(bun|ieftin|rapid|mare|mic)/u',
            '/ce\s+recoman/u', '/ce\s+e\s+mai\s+bun/u',
            '/avantaj/u', '/dezavantaj/u',
            '/\bsau\b.*\bsau\b/u', // multiple "sau" = comparing options
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'comparison', 'confidence' => 0.85];
        }

        return null;
    }

    private function checkInformational(string $q): ?array
    {
        $patterns = [
            '/cum\s+(funcțion|function|se\s+face|pot|sa)/u',
            '/ce\s+(este|înseamnă|inseamna|conține|contine)/u',
            '/unde\s+(se|pot|este|e\s+)/u',
            '/cand\s+(se|pot|vine|este)/u',
            '/de\s+ce\b/u',
            '/explic[aă]/u',
            '/care\s+sunt/u',
            '/ce\s+(progr|orar|adres)/u',
            '/informa[tț]ii/u',
            '/detalii\s+despre/u',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'informational', 'confidence' => 0.75];
        }

        return null;
    }

    private function checkExploratory(string $q, int $wordCount): ?array
    {
        $patterns = [
            '/ce\s+(ave[tț]i|aveți|produse|servicii|oferit)/u',
            '/ce\s+tip/u',
            '/ce\s+categor/u',
            '/ce\s+op[tț]iuni/u',
            '/caut\b/u',
            '/m-ar\s+interesa/u',
            '/vreau\s+s[aă]\s+(v[aă]d|explo|aflu|caut)/u',
            '/ce\s+am\s+nevoie/u',
            '/ce\s+material/u',
            '/ce\s+trebuie/u',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $q)) return ['type' => 'exploratory', 'confidence' => 0.75];
        }

        return null;
    }

    private function checkVague(string $q, int $wordCount): ?array
    {
        // Very short without clear intent
        if ($wordCount <= 2 && !preg_match('/\b\w{5,}\b/u', $q)) {
            return ['type' => 'vague', 'confidence' => 0.8];
        }

        // Single word (not a greeting or followup)
        if ($wordCount === 1 && mb_strlen($q) >= 3) {
            return ['type' => 'vague', 'confidence' => 0.6];
        }

        return null;
    }

    /**
     * Boost classification confidence based on conversation history.
     * E.g., after product search, a short "da" might be transactional, not just a greeting.
     */
    private function applyContextBoost(array $classification, array $context): array
    {
        $lastIntent = $context['last_intent'] ?? null;
        $messageCount = $context['message_count'] ?? 0;

        // If classified as greeting but there's conversation history,
        // it might be a follow-up to a previous intent
        if ($classification['type'] === 'greeting' && $messageCount >= 2 && $lastIntent) {
            // "da"/"ok" after product search = likely transactional confirmation
            if (in_array($lastIntent, ['product_search', 'transactional', 'comparison'])) {
                $classification['type'] = 'transactional';
                $classification['confidence'] = 0.7;
                $classification['context_boosted'] = true;
            }
        }

        return $classification;
    }
}
