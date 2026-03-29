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
     * Decide which data sources to use based on detected intents.
     * Avoids unnecessary RAG/product calls for trivial or clear-intent messages.
     *
     * @return array{use_rag: bool, use_products: bool, use_tools: bool, rag_limit: int}
     */
    public function decideDataSources(array $intents, ?string $queryComplexity = null): array
    {
        $decision = [
            'use_rag' => false,
            'use_products' => false,
            'use_tools' => false,
            'rag_limit' => 3,
            'complexity' => $queryComplexity ?? 'medium',
        ];

        // Greeting/thanks — no data sources needed
        $intentNames = array_map(fn($i) => $i->name, $intents);
        if (in_array('greeting', $intentNames) || in_array('thanks', $intentNames)) {
            return $decision;
        }

        // Handoff — no data sources, just handoff context
        if (in_array('handoff_intent', $intentNames)) {
            return $decision;
        }

        // Simple queries (from QueryComplexityService) — skip RAG
        if ($queryComplexity === 'simple') {
            return $decision;
        }

        foreach ($intents as $intent) {
            if (in_array($intent->name, ['product_search', 'category_recommendation'])) {
                $decision['use_products'] = true;
                if ($intent->confidence < 0.7) {
                    $decision['use_rag'] = true;
                    $decision['rag_limit'] = 2;
                }
            }

            if ($intent->name === 'knowledge_query') {
                $decision['use_rag'] = true;
                // Complex queries get more chunks
                $decision['rag_limit'] = match ($queryComplexity) {
                    'complex' => 5,
                    'simple' => 0,
                    default => $intent->confidence >= 0.7 ? 4 : 2,
                };
            }

            if ($intent->name === 'existing_order_lookup') {
                $decision['use_tools'] = true;
            }

            if ($intent->name === 'new_order_intent') {
                // New order needs product context, NOT order lookup
                $decision['use_products'] = true;
            }
        }

        return $decision;
    }

    /**
     * Analyze message and build orchestration plan with all detected intents.
     */
    public function plan(string $message, Conversation $conversation, Bot $bot): OrchestratorPlan
    {
        $intents = $this->detectAllIntents($message);

        // Decision logging
        $decisionLogger = app(DecisionLoggerService::class);
        $decisionLogger->startRequest($bot->id, $message);
        $decisionLogger->logIntents($intents);

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

        // Query complexity drives orchestration decisions
        $complexityService = app(QueryComplexityService::class);
        $complexity = $complexityService->classify($userMessage);
        $dataSources = $this->decideDataSources($plan->intents, $complexity);

        // Log data source decisions
        $decisionLogger = app(DecisionLoggerService::class);
        $decisionLogger->logDataSources($dataSources);

        foreach ($plan->pipelines as $pipeline) {
            // Skip pipelines not needed by decision logic
            $skip = match ($pipeline->name) {
                'knowledge' => !$dataSources['use_rag'],
                'product_search', 'recommendation' => !$dataSources['use_products'],
                'new_order', 'order_lookup' => false, // Never skip order-related pipelines
                default => false,
            };

            if ($skip) {
                $pipeline->durationMs = 0;
                $result->intentsExecuted[] = array_merge($pipeline->toArray(), ['skipped' => true]);
                continue;
            }

            $start = microtime(true);

            try {
                match ($pipeline->name) {
                    'order_lookup' => $this->executeOrderLookup($bot->id, $pipeline, $result, $userMessage),
                    'new_order' => $this->executeNewOrder($pipeline, $result, $conversation),
                    'product_search' => $this->executeProductSearch($bot->id, $pipeline, $result),
                    'recommendation' => $this->executeRecommendation($bot->id, $pipeline, $result),
                    'knowledge' => $this->executeKnowledge($bot->id, $pipeline, $result, $dataSources['rag_limit']),
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

        $detected = $this->intentDetector->detect($message);

        // Greeting / thanks — early return, skip everything else
        if ($detected['is_greeting'] ?? false) {
            return [new DetectedIntent('greeting', 0.95, [], 1)];
        }
        if ($detected['is_thanks'] ?? false) {
            return [new DetectedIntent('thanks', 0.95, [], 1)];
        }

        // NEW ORDER intent (purchase flow) — high priority, must NOT trigger order lookup
        if ($detected['is_new_order_intent'] ?? false) {
            $intents[] = new DetectedIntent('new_order_intent', 0.9, ['query' => $message], 5);
            // Also search products so we have context for the order
            $intents[] = new DetectedIntent('product_search', 0.8, ['query' => $message], 20);
        }

        // EXISTING order lookup (support flow) — only if NOT new order
        if (($detected['is_order_query'] ?? false) && !($detected['is_new_order_intent'] ?? false)) {
            $intents[] = new DetectedIntent('existing_order_lookup', 0.9, [], 10);
        }

        // Product search — score based on specificity
        if (!($detected['is_greeting'] ?? false) && !($detected['is_thanks'] ?? false) && !($detected['is_new_order_intent'] ?? false)) {
            $words = preg_split('/\s+/', $msg);
            $hasProductSignal = count($words) >= 2 && !($detected['is_category_recommendation'] ?? false);
            if ($hasProductSignal) {
                // Don't add duplicate product_search if already added via new_order
                $hasProductIntent = !empty(array_filter($intents, fn($i) => $i->name === 'product_search'));
                if (!$hasProductIntent) {
                    $intents[] = new DetectedIntent('product_search', 0.7, ['query' => $message], 20);
                }
            }
        }

        // Category recommendation
        if ($detected['is_category_recommendation'] ?? false) {
            $concept = $this->intentDetector->extractRecommendationConcept($message);
            $intents[] = new DetectedIntent('category_recommendation', 0.85, ['concept' => $concept], 20);
        }

        // Knowledge query — almost always (unless greeting/thanks/followup)
        if (!($detected['is_greeting'] ?? false) && !($detected['is_thanks'] ?? false) &&
            !($detected['is_followup'] ?? false)) {
            $intents[] = new DetectedIntent('knowledge_query', 0.5, ['query' => $message], 30);
        }

        // Handoff intent
        if (preg_match('/\b(operator|om|persoana|agent|ajutor real|vorbi cu cineva)\b/u', $msg)) {
            $intents[] = new DetectedIntent('handoff_intent', 0.9, [], 5);
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
                'existing_order_lookup' => $pipelines[] = new PipelineTask('order_lookup', $intent, $intent->entities),
                'new_order_intent' => $pipelines[] = new PipelineTask('new_order', $intent, $intent->entities),
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

    /**
     * Handle new order intent — inject purchase guidance context.
     * Uses last_product_context from conversation if user references a previous product.
     */
    private function executeNewOrder(PipelineTask $task, OrchestratorResult $result, ?Conversation $conversation): void
    {
        $lastProduct = null;
        if ($conversation) {
            $meta = $conversation->metadata ?? [];
            $lastProduct = $meta['last_product_context'] ?? null;
        }

        $orderGuide = "\n\n[INTENȚIE: COMANDĂ NOUĂ — Clientul vrea să PLASEZE o comandă."
            . "\nREGULI STRICTE:"
            . "\n- NU cere număr de comandă existentă."
            . "\n- NU cere email pentru verificare comandă."
            . "\n- NU trata ca suport/verificare."
            . "\n- Ajută clientul să finalizeze comanda.";

        if ($lastProduct) {
            $orderGuide .= "\n\nProdusul discutat anterior: {$lastProduct['name']}"
                . " — {$lastProduct['price']} {$lastProduct['currency']}"
                . "\nFolosește ACEST produs ca referință implicită.";
        }

        $orderGuide .= "\n\nGhidare:"
            . "\n- Confirmă produsul dorit (sau folosește cel discutat)."
            . "\n- Întreabă cantitatea dacă nu e specificată."
            . "\n- Oferă link-ul de comandă sau explică procesul de comandă."
            . "\n- Dacă botul e e-commerce, sugerează: 'Poți comanda direct de pe site.' + link]";

        $result->orderContext = $orderGuide;
        $task->resultsCount = 1;
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

    private function executeKnowledge(int $botId, PipelineTask $task, OrchestratorResult $result, int $limit = 5): void
    {
        $context = $this->knowledge->buildContext($botId, $task->params['query'] ?? '', $limit);
        if ($context) {
            $result->knowledgeContext = $context;
        }
        $task->resultsCount = !empty($context) ? 1 : 0;
    }
}
