<?php

namespace App\Services;

use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\ConversationOutcome;

class OutcomeEngine
{
    public function deriveOutcomes(Conversation $conversation): array
    {
        $events = ChatEvent::where('conversation_id', $conversation->id)->get();
        $eventNames = $events->pluck('event_name')->toArray();
        $outcomes = [];

        $ctx = [
            'tenant_id' => $conversation->tenant_id,
            'bot_id' => $conversation->bot_id,
            'conversation_id' => $conversation->id,
        ];

        // product_discovered: impression + click
        if (in_array('product_impression', $eventNames) && in_array('product_click', $eventNames)) {
            $outcomes[] = $this->upsertOutcome($ctx, 'product_discovered', 'confirmed');
        }

        // add_to_cart
        if (in_array('add_to_cart_success', $eventNames)) {
            $outcomes[] = $this->upsertOutcome($ctx, 'add_to_cart', 'confirmed');
        }

        // lead_captured
        if (in_array('lead_completed', $eventNames)) {
            $outcomes[] = $this->upsertOutcome($ctx, 'lead_captured', 'confirmed');
        }

        // human_handoff_requested
        if (in_array('handoff_sent', $eventNames)) {
            $outcomes[] = $this->upsertOutcome($ctx, 'human_handoff_requested', 'confirmed');
        }

        // abandoned_after_products: products shown but no click, session ended
        if (in_array('products_returned', $eventNames) && !in_array('product_click', $eventNames) && in_array('session_ended', $eventNames)) {
            $outcomes[] = $this->upsertOutcome($ctx, 'abandoned_after_products', 'confirmed');
        }

        // faq_resolved: knowledge used, no further messages on same topic
        if (in_array('pipeline_executed', $eventNames)) {
            $knowledgePipelines = $events->filter(fn($e) => $e->event_name === 'pipeline_executed' && ($e->properties['pipeline'] ?? '') === 'knowledge');
            if ($knowledgePipelines->count() > 0 && !in_array('fallback_triggered', $eventNames)) {
                $outcomes[] = $this->upsertOutcome($ctx, 'faq_resolved', 'probable');
            }
        }

        // unresolved: session ended with no positive outcome
        if (in_array('session_ended', $eventNames)) {
            $positives = ['product_discovered', 'add_to_cart', 'lead_captured', 'human_handoff_requested', 'faq_resolved'];
            $hasPositive = ConversationOutcome::where('conversation_id', $conversation->id)
                ->whereIn('outcome_type', $positives)->exists();
            if (!$hasPositive) {
                $outcomes[] = $this->upsertOutcome($ctx, 'unresolved', 'probable');
            }
        }

        // ── Calculate opportunity score ──
        $this->calculateOpportunityScore($conversation, $events, $eventNames);

        return array_filter($outcomes);
    }

    /**
     * Calculate if conversation is an "opportunity" (interested but no contact data).
     * Opportunity = intent + engagement, but NO lead captured.
     */
    private function calculateOpportunityScore(Conversation $conversation, $events, array $eventNames): void
    {
        $score = 0;
        $reasons = [];

        // Positive signals
        $impressionCount = collect($eventNames)->filter(fn($e) => $e === 'product_impression')->count();
        $clickCount = collect($eventNames)->filter(fn($e) => $e === 'product_click')->count();

        if (in_array('products_returned', $eventNames)) { $score += 20; $reasons[] = 'products_shown'; }
        if ($clickCount > 0) { $score += 25; $reasons[] = "product_clicks:{$clickCount}"; }
        if (in_array('add_to_cart_success', $eventNames)) { $score += 35; $reasons[] = 'add_to_cart'; }
        if (($conversation->messages_count ?? 0) >= 3) { $score += 10; $reasons[] = 'engaged_conversation'; }
        if ($impressionCount >= 5) { $score += 10; $reasons[] = 'many_impressions'; }

        // Check if has lead (with email OR phone) for THIS conversation
        $hasLead = \App\Models\Lead::where('conversation_id', $conversation->id)
            ->where(function ($q) {
                $q->whereNotNull('email')->orWhereNotNull('phone');
            })
            ->exists();

        $isOpportunity = $score >= 30 && !$hasLead;

        $conversation->update([
            'opportunity_score' => min(100, $score),
            'is_opportunity' => $isOpportunity,
            'opportunity_reasons' => !empty($reasons) ? $reasons : null,
        ]);
    }

    private function upsertOutcome(array $ctx, string $type, string $confidence): ?ConversationOutcome
    {
        return ConversationOutcome::updateOrCreate(
            ['conversation_id' => $ctx['conversation_id'], 'outcome_type' => $type, 'product_id' => null],
            array_merge($ctx, ['confidence' => $confidence])
        );
    }
}
