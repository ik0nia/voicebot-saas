<?php

namespace App\Services;

use App\DTOs\DetectedIntent;
use App\DTOs\OrchestratorPlan;
use App\DTOs\OrchestratorResult;
use App\DTOs\PipelineTask;
use App\Models\Bot;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
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

        // Category browse — only needs category data from DB, skip RAG and product search
        if (in_array('category_browse', $intentNames)) {
            return $decision;
        }

        // Simple queries: skip RAG by default, but still allow product search
        // if the intent explicitly calls for it. Short queries like "sarma" or
        // "ciment pret" are simple but still need product results.
        foreach ($intents as $intent) {
            if (in_array($intent->name, ['product_search', 'category_recommendation'])) {
                $decision['use_products'] = true;
                if ($intent->confidence < 0.7) {
                    $decision['use_rag'] = true;
                    $decision['rag_limit'] = 2;
                }
            }

            if ($intent->name === 'knowledge_query' && $queryComplexity !== 'simple') {
                $decision['use_rag'] = true;
                $decision['rag_limit'] = match ($queryComplexity) {
                    'complex' => 5,
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
        $intents = $this->detectAllIntents($message, $conversation);

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
                'category_browse' => false, // Never skip category browse
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
                    'category_browse' => $this->executeCategoryBrowse($bot->id, $pipeline, $result),
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
    private function detectAllIntents(string $message, ?Conversation $conversation = null): array
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

        // ── Conversational context: follow-up to order-related bot prompts ──
        // Single query for recent bot message, then check new order vs order lookup.
        if (!($detected['is_new_order_intent'] ?? false) && !($detected['is_order_query'] ?? false) && $conversation) {
            $recentBotMessage = \App\Models\Message::where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->orderByDesc('id')
                ->value('content');

            if ($recentBotMessage) {
                $botMsgLower = mb_strtolower($recentBotMessage);

                // NEW ORDER context has priority — bot asked "doriți să comandați?" etc.
                $isNewOrderPrompt = (
                    str_contains($botMsgLower, 'doriți să comand') ||
                    str_contains($botMsgLower, 'doriti sa comand') ||
                    str_contains($botMsgLower, 'plasați o comandă') ||
                    str_contains($botMsgLower, 'plasati o comanda') ||
                    str_contains($botMsgLower, 'vreți să comandați') ||
                    str_contains($botMsgLower, 'vreti sa comandati') ||
                    str_contains($botMsgLower, 'doriți să plasați') ||
                    str_contains($botMsgLower, 'doriti sa plasati')
                );

                if ($isNewOrderPrompt) {
                    $affirmative = preg_match('/^(da|sigur|ok|bine|desigur|exact|hai|vreau|mhm|aha)\b/u', mb_strtolower(trim($message)));
                    if ($affirmative) {
                        $detected['is_new_order_intent'] = true;
                    }
                }

                // ORDER LOOKUP context — bot asked for order number/email for verification
                if (!($detected['is_new_order_intent'] ?? false) && (
                    str_contains($botMsgLower, 'numărul comenzii') ||
                    str_contains($botMsgLower, 'numarul comenzii') ||
                    str_contains($botMsgLower, 'număr de comandă') ||
                    str_contains($botMsgLower, 'nr. comenzii') ||
                    str_contains($botMsgLower, 'verifica statusul')
                )) {
                    $orderLookup = app(\App\Services\OrderLookupService::class);
                    $orderParams = $orderLookup->extractOrderParams($message);
                    if (!empty($orderParams)) {
                        $detected['is_order_query'] = true;
                    }
                }
            }
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
            // Explicit product intent from IntentDetectionService always triggers product search.
            // Otherwise, require ≥2 words as a heuristic signal.
            // Single substantive words (≥4 chars, not a stopword) also qualify — users commonly
            // type just "sarma", "ciment", "adeziv" to browse products.
            $isExplicitProduct = $detected['is_product_search'] ?? false;
            $hasSingleSubstantiveWord = count($words) === 1 && mb_strlen($words[0]) >= 4;
            $hasProductSignal = $isExplicitProduct || $hasSingleSubstantiveWord || (count($words) >= 2 && !($detected['is_category_recommendation'] ?? false));
            if ($hasProductSignal) {
                // Don't add duplicate product_search if already added via new_order
                $hasProductIntent = !empty(array_filter($intents, fn($i) => $i->name === 'product_search'));
                if (!$hasProductIntent) {
                    $intents[] = new DetectedIntent('product_search', 0.7, ['query' => $message], 20);
                }
            }
        }

        // Category browse — user asks what types/categories of products exist
        if ($detected['is_category_browse'] ?? false) {
            $intents[] = new DetectedIntent('category_browse', 0.9, ['query' => $message], 5);
            // Remove any product_search intent — category browse replaces it
            $intents = array_values(array_filter($intents, fn($i) => $i->name !== 'product_search'));
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
                'category_browse' => $pipelines[] = new PipelineTask('category_browse', $intent, $intent->entities),
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
        $lastProductCards = null;
        if ($conversation) {
            $meta = $conversation->metadata ?? [];
            $lastProduct = $meta['last_product_context'] ?? null;
            $lastProductCards = $meta['last_product_cards'] ?? null;
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
            . "\n- Pentru finalizare comandă, cere datele clientului ÎN ACEASTĂ ORDINE:"
            . "\n  1. Numele complet"
            . "\n  2. Numărul de telefon"
            . "\n  3. Adresa de email (opțional — întreabă dacă dorește confirmare pe email)"
            . "\n- NU cere toate datele deodată — cere-le pe rând, natural, în conversație."
            . "\n- După ce ai numele și telefonul, confirmă comanda și spune că un coleg va contacta clientul."
            . "\n- Dacă botul e e-commerce, poți sugera și: 'Poți comanda și direct de pe site.' + link]";

        $result->orderContext = $orderGuide;

        // Pre-populate products from conversation history so product_search pipeline
        // doesn't override with irrelevant results from searching "vreau sa comand"
        if (!empty($lastProductCards)) {
            $result->products = $lastProductCards;
            $result->productContext = "\n\n[" . count($lastProductCards) . " produse discutate anterior afișate ca carduri. Acestea sunt produsele despre care clientul vorbea.]";
        }

        $task->resultsCount = 1;
    }

    private function executeOrderLookup(int $botId, PipelineTask $task, OrchestratorResult $result, string $userMessage = ''): void
    {
        $query = $userMessage ?: ($task->params['query'] ?? '');
        $params = $this->orderLookup->detectOrderQuery($query);

        // Fallback: if no order keywords detected, try extracting raw identifiers
        // (handles follow-up messages like just "62043" or "email@test.com")
        if (!$params) {
            $params = $this->orderLookup->extractOrderParams($query);
        }

        if ($params) {
            $lookup = $this->orderLookup->lookup($botId, $params);
            if ($lookup['found']) {
                $result->orderContext = "\n\n[INFORMAȚII COMANDĂ]\n";
                foreach ($lookup['orders'] as $o) {
                    $result->orderContext .= "Comanda #{$o['number']} | Status: {$o['status']} | Total: {$o['total']}\n";
                }
            } else {
                $result->orderContext = "\n\n[{$lookup['message']}]";
            }
        }
        $task->resultsCount = !empty($result->orderContext) ? 1 : 0;
    }

    private function executeProductSearch(int $botId, PipelineTask $task, OrchestratorResult $result): void
    {
        // Skip if products already populated (e.g. from new_order using conversation history)
        if (!empty($result->products)) {
            $task->resultsCount = count($result->products);
            return;
        }

        $query = $task->params['query'] ?? '';
        $products = $this->productSearch->search($botId, $query, 4);
        if (!empty($products)) {
            $result->products = array_map(fn($r) => $this->productSearch->toCardArray($r), $products);
            $result->productContext = "\n\n[CARDURI PRODUSE: " . count($result->products) . " produse se afișează automat ca carduri vizuale. Spune SCURT: 'Iată ce am găsit:' — fără a enumera produsele în text.]";
        } else {
            $result->productContext = "\n\n[NU s-au găsit produse relevante. NU spune că ai găsit produse.]";
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

    /**
     * Execute category browse — uses WooCommerce category hierarchy from DB.
     * Works for any store type (construction, fashion, electronics, etc.)
     */
    private function executeCategoryBrowse(int $botId, PipelineTask $task, OrchestratorResult $result): void
    {
        $cacheKey = "category_browse:{$botId}";
        $categoryContext = Cache::remember($cacheKey, 3600, function () use ($botId) {
            // Try structured categories table first (synced from WooCommerce)
            $tree = \App\Models\WooCommerceCategory::tree($botId);

            if ($tree->isNotEmpty()) {
                return \App\Models\WooCommerceCategory::toChatContext($botId);
            }

            // Fallback: extract from product JSON (for stores not yet re-synced)
            return $this->categoryBrowseFallback($botId);
        });

        if ($categoryContext) {
            $result->productContext = "\n\n[CATEGORII PRODUSE — Clientul a întrebat ce tipuri de produse sunt disponibile."
                . "\n\nCategorii disponibile:\n{$categoryContext}"
                . "\n\nINSTRUCȚIUNE: Prezintă categoriile într-un mod natural și conversațional."
                . "\nDacă sunt categorii parent cu subcategorii, grupează-le: 'Avem X cu Y, Z, ...'"
                . "\nDacă sunt multe categorii de nivel superior, enumeră-le pe cele principale."
                . "\nÎntreabă clientul ce categorie îl interesează pentru a-i arăta produse specifice."
                . "\nFii concis — nu enumera TOATE subcategoriile, doar câteva exemple per categorie.]";
        } else {
            $result->productContext = "\n\n[Nu s-au găsit categorii de produse. Răspunde general despre tipul de magazin.]";
        }

        $task->resultsCount = $categoryContext ? 1 : 0;
    }

    /**
     * Fallback category extraction from product JSON (pre-sync compatibility).
     */
    private function categoryBrowseFallback(int $botId): ?string
    {
        $rows = \App\Models\WooCommerceProduct::where('bot_id', $botId)
            ->where('stock_status', 'instock')
            ->whereNotNull('categories')
            ->pluck('categories');

        $counts = [];
        foreach ($rows as $cats) {
            if (!is_array($cats)) continue;
            foreach ($cats as $cat) {
                $cat = trim($cat);
                $lower = mb_strtolower($cat);
                if ($cat === '' || $lower === 'uncategorized' || $lower === 'necategorisite') continue;
                $counts[$cat] = ($counts[$cat] ?? 0) + 1;
            }
        }

        if (empty($counts)) return null;

        arsort($counts);
        // Show top 20 categories
        $top = array_slice($counts, 0, 20, true);
        $lines = [];
        foreach ($top as $name => $count) {
            $lines[] = "• {$name}";
        }

        return implode("\n", $lines);
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
