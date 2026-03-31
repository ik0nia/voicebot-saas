<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\ConversationPolicy;
use App\Models\WooCommerceProduct;

/**
 * Decides conversation strategy based on:
 * - conversation stage (early/mid/late)
 * - user engagement signals
 * - business goals (configured via ConversationPolicy)
 * - detected intents
 *
 * Returns actionable directives that PromptBuilder can inject.
 */
class ConversationStrategyEngine
{
    /**
     * Analyze conversation state and return strategy directives.
     *
     * @return array{stage: string, directives: array, cta: ?string, should_capture_lead: bool, prompt_modifier: string}
     */
    public function decide(
        Conversation $conversation,
        Bot $bot,
        string $currentMessage,
        array $queryIntelligence,
        array $frustration
    ): array {
        $messageCount = $conversation->messages_count ?? 0;
        $stage = $this->determineStage($messageCount, $conversation);
        $engagement = $this->measureEngagement($conversation);
        $policy = ConversationPolicy::where('bot_id', $bot->id)->where('is_active', true)->first();
        $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
        $queryType = $queryIntelligence['type'] ?? 'informational';
        $frustrationLevel = $frustration['level'] ?? 'low';

        $directives = [];
        $cta = null;
        $shouldCaptureLead = false;
        $promptLines = [];

        // ── RULE 1: Never sell during frustration ──
        if ($frustrationLevel === 'high') {
            $directives[] = 'no_selling';
            $directives[] = 'offer_escalation';
            $promptLines[] = 'NU sugera produse sau acțiuni comerciale. Concentrează-te pe rezolvarea problemei.';
        }

        // ── RULE 2: Never sell during complaints ──
        if ($queryType === 'complaint') {
            $directives[] = 'no_selling';
            $directives[] = 'empathy_first';
        }

        // ── RULE 3: Stage-based CTA strategy ──
        if (!in_array('no_selling', $directives)) {
            $ctaLevel = (int) ($policy->cta_aggressiveness ?? 3);

            switch ($stage) {
                case 'early': // Messages 1-3
                    if ($ctaLevel >= 4) {
                        $promptLines[] = 'Ghidează conversația spre un obiectiv: ce caută clientul?';
                    }
                    // Early stage: focus on understanding, not selling
                    $directives[] = 'understand_intent';
                    break;

                case 'mid': // Messages 4-8
                    if ($queryType === 'transactional') {
                        $cta = 'order';
                        $directives[] = 'guide_to_action';
                        $promptLines[] = 'Clientul pare gata să acționeze. Ghidează-l spre finalizare.';
                    } elseif ($queryType === 'exploratory' && $hasProducts) {
                        $cta = 'recommend';
                        $directives[] = 'suggest_products';
                        if ($ctaLevel >= 3) {
                            $promptLines[] = 'Sugerează produse/servicii specifice bazate pe ce a discutat clientul.';
                        }
                    } elseif ($engagement['depth'] >= 3) {
                        $directives[] = 'deepen_engagement';
                        if ($ctaLevel >= 4) {
                            $promptLines[] = 'Conversația e substanțială. Propune un pas următor concret.';
                        }
                    }
                    break;

                case 'late': // Messages 9+
                    if (!$engagement['has_lead']) {
                        $shouldCaptureLead = true;
                        $directives[] = 'capture_lead';
                    }
                    if ($queryType !== 'complaint' && $ctaLevel >= 3) {
                        $cta = $hasProducts ? 'order_or_contact' : 'contact';
                        $promptLines[] = 'Conversația e lungă. Propune un pas următor concret (comandă, programare, contact).';
                    }
                    break;
            }
        }

        // ── RULE 4: Lead capture timing ──
        $leadLevel = (int) ($policy->lead_aggressiveness ?? 3);
        if (!$shouldCaptureLead) {
            $shouldCaptureLead = match(true) {
                $leadLevel >= 5 && $messageCount >= 2 => true,
                $leadLevel >= 4 && $messageCount >= 4 => true,
                $leadLevel >= 3 && $messageCount >= 6 => true,
                $leadLevel >= 2 && $messageCount >= 8 => true,
                $leadLevel <= 1 => false,
                default => false,
            };

            // Override: always capture on transactional intent mid/late
            if ($queryType === 'transactional' && $stage !== 'early' && $leadLevel >= 2) {
                $shouldCaptureLead = true;
            }
        }

        // Already has lead? Don't capture again
        if ($shouldCaptureLead && $engagement['has_lead']) {
            $shouldCaptureLead = false;
        }

        // ── RULE 5: Clarification vs recommendation ──
        if ($queryType === 'vague' && $stage === 'early') {
            $directives[] = 'ask_clarification';
            $promptLines[] = 'Întrebarea nu e clară. Pune o întrebare de clarificare SPECIFICĂ înainte de a răspunde.';
        }

        // ── RULE 6: Comparison guidance ──
        if ($queryType === 'comparison') {
            $directives[] = 'structured_comparison';
            $promptLines[] = 'Oferă o comparație structurată. La final, adaugă o recomandare clară.';
        }

        // ── RULE 7: Conversation depth guard ──
        if ($messageCount >= 15 && $frustrationLevel !== 'low') {
            $directives[] = 'offer_escalation';
            $promptLines[] = 'Conversația e foarte lungă. Oferă opțiunea de a vorbi cu un om.';
        }

        $promptModifier = !empty($promptLines)
            ? "\nSTRATEGIE CONVERSAȚIE:\n- " . implode("\n- ", $promptLines)
            : '';

        return [
            'stage' => $stage,
            'directives' => array_unique($directives),
            'cta' => $cta,
            'should_capture_lead' => $shouldCaptureLead,
            'prompt_modifier' => $promptModifier,
            'engagement' => $engagement,
        ];
    }

    private function determineStage(int $messageCount, Conversation $conversation): string
    {
        if ($messageCount <= 3) return 'early';
        if ($messageCount <= 8) return 'mid';
        return 'late';
    }

    private function measureEngagement(Conversation $conversation): array
    {
        $msgCount = $conversation->messages_count ?? 0;

        // Check if lead already exists
        $hasLead = \App\Models\Lead::where('conversation_id', $conversation->id)->exists();

        // Count substantive inbound messages (>10 chars, not just "da"/"ok")
        $substantiveMessages = $conversation->messages()
            ->where('direction', 'inbound')
            ->whereRaw("LENGTH(content) > 10")
            ->count();

        // Depth: how many real exchanges happened
        $depth = min(10, $substantiveMessages);

        return [
            'message_count' => $msgCount,
            'substantive_count' => $substantiveMessages,
            'depth' => $depth,
            'has_lead' => $hasLead,
            'is_engaged' => $depth >= 3,
        ];
    }
}
