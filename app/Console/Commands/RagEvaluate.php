<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\ChatCompletionService;
use App\Services\ChatModelRouter;
use App\Services\CostControlService;
use App\Services\IntentDetectionService;
use App\Services\KnowledgeSearchService;
use App\Services\PromptBuilder;
use App\Services\PromptGuardrails;
use Illuminate\Console\Command;

class RagEvaluate extends Command
{
    protected $signature = 'rag:evaluate
        {--bot= : Bot ID to evaluate (required)}
        {--type= : Filter by type (product, knowledge, service, greeting)}
        {--query= : Run a single query instead of the full dataset}
        {--verbose : Show full LLM responses}';

    protected $description = 'Run RAG + LLM evaluation against test cases. Measures response quality, cost, and pipeline decisions.';

    public function handle(): int
    {
        $botId = (int) $this->option('bot');
        if (!$botId) {
            $this->error('--bot is required. Example: php artisan rag:evaluate --bot=67');
            return self::FAILURE;
        }

        $bot = Bot::withoutGlobalScopes()->find($botId);
        if (!$bot) {
            $this->error("Bot {$botId} not found.");
            return self::FAILURE;
        }

        $this->info("Evaluating bot: {$bot->name} (ID: {$botId})");
        $this->newLine();

        // Single query mode
        if ($singleQuery = $this->option('query')) {
            $result = $this->evaluateQuery($bot, $singleQuery, ['must_contain' => [], 'type' => 'manual']);
            $this->renderResult($result, true);
            return self::SUCCESS;
        }

        // Load test cases
        $cases = config('evaluation', []);
        if (empty($cases)) {
            $this->error('No evaluation cases found in config/evaluation.php');
            return self::FAILURE;
        }

        // Filter by type
        if ($typeFilter = $this->option('type')) {
            $cases = array_filter($cases, fn($c) => ($c['type'] ?? '') === $typeFilter);
            $this->info("Filtering by type: {$typeFilter}");
        }

        $pass = 0;
        $fail = 0;
        $totalCost = 0;
        $totalTokens = 0;
        $totalLatency = 0;
        $failures = [];

        foreach ($cases as $case) {
            $result = $this->evaluateQuery($bot, $case['query'], $case);

            $totalCost += $result['cost_cents'];
            $totalTokens += $result['tokens'];
            $totalLatency += $result['latency_ms'];

            if ($result['passed']) {
                $pass++;
                $this->line("  <fg=green>✓</> [{$result['type']}] <comment>{$case['query']}</comment>");
            } else {
                $fail++;
                $failures[] = $result;
                $this->line("  <fg=red>✗</> [{$result['type']}] <comment>{$case['query']}</comment>");
                $this->line("      Missing: <fg=red>" . implode(', ', $result['missing_keywords']) . "</>");
            }

            if ($this->option('verbose')) {
                $this->renderResult($result, false);
            }

            // Reset cost control for each query
            app(CostControlService::class)->reset();
        }

        $total = $pass + $fail;
        $score = $total > 0 ? round(($pass / $total) * 100) : 0;

        $this->newLine();
        $this->info("══════════════════════════════════════");
        $this->info("  RESULTS: {$pass}/{$total} PASS ({$score}%)");
        $this->info("  Cost:    " . round($totalCost, 2) . " cents");
        $this->info("  Tokens:  {$totalTokens}");
        $this->info("  Avg latency: " . ($total > 0 ? round($totalLatency / $total) : 0) . "ms");
        $this->info("══════════════════════════════════════");

        if (!empty($failures)) {
            $this->newLine();
            $this->warn('Failed cases:');
            foreach ($failures as $f) {
                $this->line("  <fg=red>✗</> {$f['query']}");
                $this->line("    Missing: " . implode(', ', $f['missing_keywords']));
                $snippet = mb_substr($f['response'], 0, 120);
                $this->line("    Response: <fg=gray>{$snippet}...</>");
            }
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function evaluateQuery(Bot $bot, string $query, array $case): array
    {
        $start = microtime(true);
        $mustContain = $case['must_contain'] ?? [];
        $type = $case['type'] ?? 'unknown';

        // Run intent detection
        $intentService = app(IntentDetectionService::class);
        $intents = $intentService->detect($query);
        $skipKnowledge = $intentService->shouldSkipKnowledge($query);

        // Run RAG search
        $ragContext = '';
        $ragResultsCount = 0;
        if (!$skipKnowledge) {
            $searchService = app(KnowledgeSearchService::class);
            $ragContext = $searchService->buildContext($bot->id, $query, 5);
            $ragResultsCount = !empty($ragContext) ? substr_count($ragContext, '---') / 2 : 0;
        }

        // Build prompt
        $systemPrompt = $bot->system_prompt ?? 'Ești un asistent virtual util.';
        if (!empty($ragContext)) {
            $systemPrompt .= "\n\n" . $ragContext;
        }
        $systemPrompt = PromptGuardrails::apply($systemPrompt);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query],
        ];

        // Call LLM
        $response = '';
        $tokens = 0;
        $costCents = 0.0;
        $model = '';
        $usedTools = false;

        try {
            $router = app(ChatModelRouter::class);
            $modelConfig = $router->route($query, 0, 0);
            $chatService = app(ChatCompletionService::class);
            $result = $chatService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);

            $response = $result['content'] ?? '';
            $tokens = ($result['input_tokens'] ?? 0) + ($result['output_tokens'] ?? 0);
            $costCents = $result['cost_cents'] ?? 0;
            $model = $result['model'] ?? '';
            $usedTools = !empty($result['tool_calls']);
        } catch (\Throwable $e) {
            $response = '[ERROR] ' . $e->getMessage();
        }

        $latencyMs = (int) ((microtime(true) - $start) * 1000);

        // Check keywords
        $responseLower = mb_strtolower($response);
        $missingKeywords = [];
        foreach ($mustContain as $keyword) {
            $keywordLower = mb_strtolower($keyword);
            if (!str_contains($responseLower, $keywordLower)) {
                $missingKeywords[] = $keyword;
            }
        }

        $passed = empty($missingKeywords);

        return [
            'query' => $query,
            'type' => $type,
            'response' => $response,
            'passed' => $passed,
            'missing_keywords' => $missingKeywords,
            'used_rag' => !empty($ragContext),
            'rag_results_count' => (int) $ragResultsCount,
            'used_tools' => $usedTools,
            'tokens' => $tokens,
            'cost_cents' => $costCents,
            'latency_ms' => $latencyMs,
            'model' => $model,
            'skip_knowledge' => $skipKnowledge,
            'intents' => $intents,
        ];
    }

    private function renderResult(array $result, bool $full): void
    {
        $this->line("    RAG: " . ($result['used_rag'] ? "<fg=green>yes</> ({$result['rag_results_count']} chunks)" : '<fg=yellow>no</>'));
        $this->line("    Tools: " . ($result['used_tools'] ? '<fg=green>yes</>' : 'no'));
        $this->line("    Model: {$result['model']} | Tokens: {$result['tokens']} | Cost: {$result['cost_cents']}c | Latency: {$result['latency_ms']}ms");

        if ($full) {
            $this->newLine();
            $this->line("    <fg=cyan>Response:</>");
            foreach (explode("\n", wordwrap($result['response'], 100)) as $line) {
                $this->line("    " . $line);
            }
        }
        $this->newLine();
    }
}
