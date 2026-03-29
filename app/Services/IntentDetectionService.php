<?php

namespace App\Services;

class IntentDetectionService
{
    /**
     * Detect intents from a user message and return intent flags.
     *
     * @return array{is_order_query: bool, is_product_search: bool, is_greeting: bool, is_followup: bool, is_complaint: bool}
     */
    public function detect(string $message): array
    {
        $msg = mb_strtolower(trim($message));

        return [
            'is_order_query' => $this->isOrderQuery($msg),
            'is_product_search' => $this->isProductSearch($msg),
            'is_category_recommendation' => $this->isCategoryRecommendation($msg),
            'is_greeting' => $this->isGreeting($msg),
            'is_followup' => $this->isFollowup($msg),
            'is_complaint' => $this->isComplaint($msg),
            'is_thanks' => $this->isThanks($msg),
        ];
    }

    /**
     * Check if message should skip knowledge search (greetings, thanks, simple followups).
     */
    public function shouldSkipKnowledge(string $message): bool
    {
        $intents = $this->detect($message);
        return $intents['is_greeting'] || $intents['is_thanks'] || $intents['is_followup'];
    }

    private function isOrderQuery(string $msg): bool
    {
        $patterns = ['/comand[aă]/u', '/livr[aă]r/u', '/colet/u', '/tracking/i', '/awb/i', '/cand.*vine/u', '/status.*comand/u'];
        foreach ($patterns as $p) {
            if (preg_match($p, $msg)) return true;
        }
        return false;
    }

    private function isProductSearch(string $msg): bool
    {
        $patterns = ['/cat.*cost/u', '/pret/u', '/caut/u', '/vreau.*sa.*cumpar/u', '/aveti/u', '/stoc/u'];
        foreach ($patterns as $p) {
            if (preg_match($p, $msg)) return true;
        }
        return false;
    }

    private function isGreeting(string $msg): bool
    {
        $greetings = ['salut', 'buna', 'hey', 'hello', 'hi', 'buna ziua', 'buna dimineata', 'buna seara'];
        $words = preg_split('/[\s,!.]+/', $msg);
        return count($words) <= 4 && count(array_intersect($words, $greetings)) > 0;
    }

    private function isFollowup(string $msg): bool
    {
        $followups = ['da', 'nu', 'ok', 'bine', 'multumesc', 'mersi', 'inteleg', 'am inteles', 'perfect', 'super', 'sigur'];
        $trimmed = trim($msg, ' !.,?');
        return in_array($trimmed, $followups) || str_word_count($msg) <= 3;
    }

    /**
     * Detect recommendation/category queries — user asks WHAT they need, not for a specific product.
     * Examples: "ce imi recomanzi pentru zugravit", "ce imi trebuie pentru baie", "vreau sa renovez"
     */
    private function isCategoryRecommendation(string $msg): bool
    {
        // Already a specific product search → not a recommendation
        if ($this->isProductSearch($msg)) return false;

        $patterns = [
            '/ce\s+(imi|îmi|ne)\s+(recoman|trebui|sugere)/u',
            '/ce\s+(produse|materiale|articole)\s+(am|aveti|aveți|imi|îmi)/u',
            '/ce\s+(am|as)\s+nevoie/u',
            '/recoman.*pentru/u',
            '/trebui.*pentru/u',
            '/vreau\s+sa\s+(renovez|zugrav|vopsesc|placari|fac|montez|repar|izol)/u',
            '/cum\s+sa\s+(zugrav|vopsesc|placari|fac|montez|repar|izol)/u',
            '/ce\s+materiale/u',
            '/lista.*materiale/u',
            '/ce.*necesit/u',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $msg)) return true;
        }

        return false;
    }

    /**
     * Extract the activity/concept from a recommendation query.
     * Returns null if not a recommendation query.
     */
    public function extractRecommendationConcept(string $message): ?string
    {
        $msg = mb_strtolower(trim($message));
        $msg = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț'],
            ['a', 'a', 'i', 's', 't'],
            $msg
        );

        // "pentru X" pattern — extract X
        if (preg_match('/pentru\s+(.+?)(?:\?|$|\.)/u', $msg, $m)) {
            return trim($m[1]);
        }

        // "sa renovez/zugravesc/etc." pattern — extract activity
        if (preg_match('/sa\s+(renovez|zugrav\w*|vopsesc|montez|repar\w*|izol\w*|placari\w*|fac\w*)/u', $msg, $m)) {
            return trim($m[1]);
        }

        // Generic "recomanzi X" / "trebuie X"
        if (preg_match('/(?:recoman\w*|trebui\w*|necesit\w*)\s+(.+?)(?:\?|$|\.)/u', $msg, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function isComplaint(string $msg): bool
    {
        $patterns = ['/reclam/u', '/nemultu/u', '/problema/u', '/nu.*functioneaz/u', '/defect/u', '/prost/u'];
        foreach ($patterns as $p) {
            if (preg_match($p, $msg)) return true;
        }
        return false;
    }

    private function isThanks(string $msg): bool
    {
        $thanks = ['multumesc', 'mersi', 'merci', 'thank', 'thanks', 'multumim'];
        foreach ($thanks as $t) {
            if (str_contains($msg, $t)) return true;
        }
        return false;
    }
}
