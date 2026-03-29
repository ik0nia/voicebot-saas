<?php

namespace App\Services;

use App\DTOs\LeadScore;
use App\Models\Conversation;

class LeadOpportunityScorer
{
    public function score(Conversation $conversation, array $intents, array $events): LeadScore
    {
        $score = 0;
        $signals = [];
        $triggerReason = null;

        $msgCount = $conversation->messages_count ?? 0;
        $eventNames = array_column($events, 'event_name');

        // ── Explicit request signals (high value) ──
        foreach ($intents as $intent) {
            $name = is_array($intent) ? ($intent['name'] ?? '') : ($intent->name ?? '');
            if (in_array($name, ['quote_intent', 'handoff_intent'])) {
                $score += 30; $signals[] = 'explicit_request'; $triggerReason = $name;
            }
        }

        // ── Engagement signals ──
        if ($msgCount >= 3) { $score += 10; $signals[] = 'engaged_3_msgs'; }
        if ($msgCount >= 6) { $score += 10; $signals[] = 'engaged_6_msgs'; }
        if ($msgCount >= 10) { $score += 10; $signals[] = 'deep_conversation'; }

        // ── Product interaction signals ──
        $clickCount = count(array_filter($eventNames, fn($e) => $e === 'product_click'));
        $impressionCount = count(array_filter($eventNames, fn($e) => $e === 'product_impression'));

        if ($impressionCount > 0) { $score += 5; $signals[] = 'saw_products'; }
        if ($clickCount > 0) { $score += 10; $signals[] = 'clicked_product'; }
        if ($clickCount >= 3) { $score += 10; $signals[] = 'multi_product_interest'; }
        if ($impressionCount >= 5) { $score += 5; $signals[] = 'many_impressions'; }

        // ── Intent signals ──
        foreach ($intents as $intent) {
            $name = is_array($intent) ? ($intent['name'] ?? '') : ($intent->name ?? '');
            if ($name === 'product_search') { $score += 5; $signals[] = 'product_search_intent'; }
            if ($name === 'category_recommendation') { $score += 10; $signals[] = 'recommendation_intent'; }
            if ($name === 'knowledge_query') { $score += 5; $signals[] = 'info_seeking'; }
        }

        // ── Dead end signals ──
        if (in_array('no_results', $eventNames)) { $score += 10; $signals[] = 'dead_end_search'; }
        if (in_array('fallback_triggered', $eventNames)) { $score += 5; $signals[] = 'fallback_hit'; }

        // ── Negative signals ──
        if (in_array('add_to_cart_success', $eventNames)) { $score -= 15; $signals[] = 'already_in_cart'; }
        if ($msgCount < 3) { $score -= 20; $signals[] = 'too_short'; }

        // Threshold: 30 = moderate engagement sufficient for soft lead prompt
        $threshold = 30;

        if (!$triggerReason && $score >= $threshold) {
            $triggerReason = $msgCount >= 6 ? 'engaged_conversation' : 'product_interest';
        }

        return new LeadScore(max(0, min(100, $score)), $threshold, $triggerReason, $signals);
    }
}
