<?php

namespace App\Services;

use App\Models\Conversation;

class ComplexityScoringService
{
    public function score(string $message, array $intents, ?Conversation $conversation): int
    {
        $s = 0;
        $wordCount = str_word_count($message);

        // Intent count
        $intentCount = count($intents);
        if ($intentCount >= 3) $s += 30;
        elseif ($intentCount >= 2) $s += 15;

        // Message length
        if ($wordCount > 40) $s += 20;
        elseif ($wordCount > 20) $s += 10;

        // Follow-up dependency
        if ($conversation && ($conversation->messages_count ?? 0) > 4 && $wordCount < 8) $s += 5;

        // Comparison/complex patterns
        $msg = mb_strtolower($message);
        if (preg_match('/\b(compar|diferent|versus|sau|ori|mai bun|recomand)\b/u', $msg)) $s += 15;
        if (preg_match('/\b(calcul|suprafat|metru|cantitate|consum)\b/u', $msg)) $s += 10;
        if (preg_match('/\b(reclam|nemultum|problem|defect|supar)\b/u', $msg)) $s += 20;
        if (preg_match('/\b(urgent|repede|graba|azi|acum)\b/u', $msg)) $s += 5;

        // Knowledge + product orchestration
        $hasProduct = false; $hasKnowledge = false;
        foreach ($intents as $i) {
            $name = is_array($i) ? ($i['name'] ?? '') : ($i->name ?? '');
            if (in_array($name, ['product_search', 'category_recommendation'])) $hasProduct = true;
            if ($name === 'knowledge_query') $hasKnowledge = true;
        }
        if ($hasProduct && $hasKnowledge) $s += 10;

        return max(0, min(100, $s));
    }
}
