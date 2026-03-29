<?php

namespace App\Services;

use App\DTOs\DetectedIntent;
use App\DTOs\OrchestratorPlan;
use App\DTOs\OrchestratorResult;
use App\DTOs\PipelineTask;
use App\Models\Bot;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

/**
 * Multi-intent orchestration engine.
 *
 * Replaces the first-match-wins pipeline in ChatbotApiController
 * with scored multi-intent detection and composed pipeline execution.
 */
class IntentOrchestratorService
{
    public function __construct(
        private IntentDetectionService $intentDetector,
        private ProductSearchService $productSearch,
        private RecommendationService $recommendations,
        private KnowledgeSearchService $knowledge,
        private OrderLookupService $orderLookup,
        private ConversationEventService $events,
        private LeadOpportunityScorer $leadScorer,
        private HandoffService $handoffService,
    ) {}

    /**
     * Analyze message and build orchestration plan with all detected intents.
     */
    public function plan(string $message, Conversation $conversation, Bot $bot): OrchestratorPlan
    {
        $intents = $this->detectAllIntents($message);

        // Track detected intents
        foreach ($intents as $intent) {
            $this->events->track(EventTaxonomy::INTENT_DETECTED, $intent->toArray(), [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'event_source' => EventTaxonomy::SOURCE_BACKEND,
            ]);
        }

        $pipelines = $this->buildPipelines($intents);

        return new OrchestratorPlan(
            intents: $intents,
            pipelines: $pipelines,
            needsClarification: false,
            clarificationQuestion: null,
            complexityScore: count($intents) > 1 ? 60.0 : 30.0,
        );
    }

    /**
     * Execute all pipelines in the plan and merge results.
     */
    public function execute(OrchestratorPlan $plan, Bot $bot, string $userMessage = '', ?Conversation $conversation = null): OrchestratorResult
    {
        $result = new OrchestratorResult();

        foreach ($plan->pipelines as $pipeline) {
            $start = microtime(true);

            try {
                match ($pipeline->name) {
                    'order_lookup' => $this->executeOrderLookup($bot->id, $pipeline, $result, $userMessage),
                    'product_search' => $this->executeProductSearch($bot->id, $pipeline, $result),
                    'recommendation' => $this->executeRecommendation($bot->id, $pipeline, $result),
                    'knowledge' => $this->executeKnowledge($bot->id, $pipeline, $result),
                    default => null,
                };
            } catch (\Throwable $e) {
                Log::warning("IntentOrchestrator: pipeline {$pipeline->name} failed", ['error' => $e->getMessage()]);
            }

            $pipeline->durationMs = (int) ((microtime(true) - $start) * 1000);
            $result->intentsExecuted[] = $pipeline->toArray();
        }

        // ── Post-pipeline: Lead scoring ──
        if ($conversation) {
            try {
                $sessionEvents = $this->events->getConversationEvents($conversation->id)->toArray();
                $leadScore = $this->leadScorer->score($conversation, $plan->intents, $sessionEvents);

                if ($leadScore->shouldCapture()) {
                    $result->leadContext = "\n\n[INSTRUCȚIUNE LEAD CAPTURE — OBLIGATORIU în acest răspuns:"
                        . "\nClientul este interesat (scor interes: {$leadScore->value}/100). "
                        . "\nTREBUIE să incluzi în răspunsul tău o propunere naturală de a obține datele de contact."
                        . "\nCe să ceri: email SAU telefon (nu ambele deodată)."
                        . "\nExemple de formulări naturale:"
                        . "\n- 'Dacă vrei, îți pot trimite o ofertă detaliată pe email. Care e adresa ta?'"
                        . "\n- 'Pot să te ajut mai bine dacă îmi lași un email sau telefon. Vrei?'"
                        . "\n- 'Vrei să îți trimit selecția pe email ca să o ai la îndemână?'"
                        . "\nNU fi agresiv. Propune natural, ca parte din conversație."
                        . "\nMotiv: {$leadScore->triggerReason}]";

                    $this->events->track(EventTaxonomy::LEAD_PROMPT_SHOWN, [
                        'score' => $leadScore->value,
                        'trigger' => $leadScore->triggerReason,
                    ], [
                        'tenant_id' => $bot->tenant_id,
                        'bot_id' => $bot->id,
                        'conversation_id' => $conversation->id,
                        'event_source' => EventTaxonomy::SOURCE_BACKEND,
                        'idempotency_key' => "lead_prompt:{$conversation->id}:{$conversation->messages_count}",
                    ]);

                    // Update conversation lead score
                    $conversation->update(['lead_score' => $leadScore->value]);
                }
            } catch (\Throwable $e) {
                Log::debug('Lead scoring failed', ['error' => $e->getMessage()]);
            }
        }

        // ── Post-pipeline: Handoff check ──
        if ($plan->hasIntent('handoff_intent') && $conversation) {
            try {
                $handoff = $this->handoffService->createHandoff(
                    $conversation,
                    'user_requested',
                    $plan->intents
                );

                $result->handoffContext = "\n\n[HANDOFF: Clientul a cerut să vorbească cu un operator. "
                    . "Spune-i că ai notat cererea și un coleg îl va contacta în cel mai scurt timp. "
                    . "Întreabă dacă dorește să fie contactat pe email sau telefon.]";

                $this->events->track(EventTaxonomy::HANDOFF_OFFERED, [
                    'handoff_id' => $handoff->id,
                    'trigger' => 'user_requested',
                ], [
                    'tenant_id' => $bot->tenant_id,
                    'bot_id' => $bot->id,
                    'conversation_id' => $conversation->id,
                    'event_source' => EventTaxonomy::SOURCE_BACKEND,
                    'idempotency_key' => "handoff:{$conversation->id}:{$handoff->id}",
                ]);
            } catch (\Throwable $e) {
                Log::debug('Handoff creation failed', ['error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /**
     * Detect ALL intents with confidence scores — not first-match.
     */
    private function detectAllIntents(string $message): array
    {
        $msg = mb_strtolower(trim($message));
        $intents = [];

        // Order lookup (high priority)
        $oldIntents = $this->intentDetector->detect($message);
        if ($oldIntents['is_order_query'] ?? false) {
            $intents[] = new DetectedIntent('order_lookup', 0.9, [], 10);
        }

        // Product search — score based on specificity
        if (!($oldIntents['is_greeting'] ?? false) && !($oldIntents['is_thanks'] ?? false)) {
            $words = preg_split('/\s+/', $msg);
            $hasProductSignal = count($words) >= 2 && !($oldIntents['is_category_recommendation'] ?? false);
            if ($hasProductSignal) {
                $intents[] = new DetectedIntent('product_search', 0.7, ['query' => $message], 20);
            }
        }

        // Category recommendation
        if ($oldIntents['is_category_recommendation'] ?? false) {
            $concept = $this->intentDetector->extractRecommendationConcept($message);
            $intents[] = new DetectedIntent('category_recommendation', 0.85, ['concept' => $concept], 20);
        }

        // Knowledge query — almost always (unless greeting/thanks)
        if (!($oldIntents['is_greeting'] ?? false) && !($oldIntents['is_thanks'] ?? false) &&
            !($oldIntents['is_followup'] ?? false)) {
            $intents[] = new DetectedIntent('knowledge_query', 0.5, ['query' => $message], 30);
        }

        // Handoff intent
        if (preg_match('/\b(operator|om|persoana|agent|ajutor real|vorbi cu cineva)\b/u', $msg)) {
            $intents[] = new DetectedIntent('handoff_intent', 0.9, [], 5);
        }

        // Greeting / thanks — low priority, skip other pipelines
        if ($oldIntents['is_greeting'] ?? false) {
            return [new DetectedIntent('greeting', 0.95, [], 1)];
        }
        if ($oldIntents['is_thanks'] ?? false) {
            return [new DetectedIntent('thanks', 0.95, [], 1)];
        }

        // Sort by priority
        usort($intents, fn($a, $b) => $a->priority <=> $b->priority);

        return $intents;
    }

    private function buildPipelines(array $intents): array
    {
        $pipelines = [];
        $hasProductPipeline = false;

        foreach ($intents as $intent) {
            match ($intent->name) {
                'order_lookup' => $pipelines[] = new PipelineTask('order_lookup', $intent, $intent->entities),
                'product_search' => (function() use (&$pipelines, &$hasProductPipeline, $intent) {
                    if (!$hasProductPipeline) {
                        $pipelines[] = new PipelineTask('product_search', $intent, $intent->entities);
                        $hasProductPipeline = true;
                    }
                })(),
                'category_recommendation' => (function() use (&$pipelines, &$hasProductPipeline, $intent) {
                    if (!$hasProductPipeline) {
                        $pipelines[] = new PipelineTask('recommendation', $intent, $intent->entities);
                        $hasProductPipeline = true;
                    }
                })(),
                'knowledge_query' => $pipelines[] = new PipelineTask('knowledge', $intent, ['query' => $intent->entities['query'] ?? '']),
                default => null,
            };
        }

        return $pipelines;
    }

    private function executeOrderLookup(int $botId, PipelineTask $task, OrchestratorResult $result, string $userMessage = ''): void
    {
        $params = $this->orderLookup->detectOrderQuery($userMessage ?: ($task->params['query'] ?? ''));
        if ($params) {
            $lookup = $this->orderLookup->lookup($botId, $params);
            if ($lookup['found']) {
                $result->orderContext = "\n\n[INFORMAȚII COMANDĂ]\n";
                foreach ($lookup['orders'] as $o) {
                    $result->orderContext .= "Comanda #{$o['number']} | Status: {$o['status']} | Total: {$o['total']}\n";
                }
            }
        }
        $task->resultsCount = !empty($result->orderContext) ? 1 : 0;
    }

    private function executeProductSearch(int $botId, PipelineTask $task, OrchestratorResult $result): void
    {
        $query = $task->params['query'] ?? '';
        $products = $this->productSearch->search($botId, $query, 4);
        if (!empty($products)) {
            $result->products = array_map(fn($r) => $this->productSearch->toCardArray($r), $products);
            $result->productContext = "\n\n[Am găsit " . count($result->products) . " produse relevante care se afișează ca carduri. NU le enumera în text.]";
        }
        $task->resultsCount = count($products);
    }

    private function executeRecommendation(int $botId, PipelineTask $task, OrchestratorResult $result): void
    {
        $concept = $task->params['concept'] ?? '';
        if ($concept && $this->recommendations->hasConcept($concept)) {
            $rec = $this->recommendations->recommend($botId, $concept, 2);
            if (!empty($rec['products'])) {
                $result->products = array_map(fn($r) => $this->productSearch->toCardArray($r), $rec['products']);
                $subQueries = implode(', ', $rec['sub_queries']);
                $result->productContext = "\n\n[Recomandări pentru \"{$concept}\": " . count($result->products) . " produse din categoriile: {$subQueries}. Explică pe scurt DE CE sunt necesare.]";
            }
        }
        $task->resultsCount = count($result->products);
    }

    private function executeKnowledge(int $botId, PipelineTask $task, OrchestratorResult $result): void
    {
        // Skip if greeting/thanks (no knowledge needed)
        $context = $this->knowledge->buildContext($botId, $task->params['query'] ?? '', 5);
        if ($context) {
            $result->knowledgeContext = $context;
        }
        $task->resultsCount = !empty($context) ? 1 : 0;
    }
}
