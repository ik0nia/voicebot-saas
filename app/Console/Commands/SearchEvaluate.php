<?php

namespace App\Console\Commands;

use App\Services\ProductSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SearchEvaluate extends Command
{
    protected $signature = 'search:evaluate
        {--bot= : Bot ID to evaluate against (required)}
        {--dataset= : Path to eval JSON (default: storage/app/search_eval_queries.json)}
        {--group= : Run only a specific group (e.g., brand, product_type)}
        {--query= : Run a single query instead of dataset}
        {--verbose-scores : Show detailed scoring for each result}
        {--limit=5 : Max results per query}';

    protected $description = 'Evaluate product search relevance against a test dataset. General-purpose, works for any tenant.';

    public function handle(): int
    {
        $botId = (int) $this->option('bot');
        if (!$botId) {
            $this->error('--bot is required. Example: php artisan search:evaluate --bot=67');
            return self::FAILURE;
        }

        // Check bot has products
        $productCount = \DB::table('woocommerce_products')->where('bot_id', $botId)->count();
        if ($productCount === 0) {
            $this->error("Bot {$botId} has no products.");
            return self::FAILURE;
        }
        $this->info("Bot {$botId}: {$productCount} products in catalog");
        $this->newLine();

        // Clear cache for clean evaluation
        Cache::flush();

        $limit = (int) $this->option('limit');
        $service = app(ProductSearchService::class);

        // Single query mode
        if ($singleQuery = $this->option('query')) {
            $this->evaluateSingleQuery($service, $botId, $singleQuery, $limit);
            return self::SUCCESS;
        }

        // Dataset mode
        $datasetPath = $this->option('dataset') ?? storage_path('app/search_eval_queries.json');
        if (!file_exists($datasetPath)) {
            $this->error("Dataset not found: {$datasetPath}");
            return self::FAILURE;
        }

        $queries = json_decode(file_get_contents($datasetPath), true);
        if (empty($queries)) {
            $this->error('Empty or invalid dataset.');
            return self::FAILURE;
        }

        // Filter by group
        if ($group = $this->option('group')) {
            $queries = array_filter($queries, fn($q) => ($q['group'] ?? '') === $group);
            $this->info("Filtering by group: {$group}");
        }

        $pass = 0;
        $fail = 0;
        $skip = 0;
        $failures = [];

        foreach ($queries as $testCase) {
            $result = $this->evaluateCase($service, $botId, $testCase, $limit);

            if ($result['verdict'] === 'PASS') {
                $pass++;
                $this->line("  <fg=green>✓</> <comment>{$testCase['query']}</comment> → {$result['count']} results");
            } elseif ($result['verdict'] === 'SKIP') {
                $skip++;
                $this->line("  <fg=yellow>○</> <comment>{$testCase['query']}</comment> → SKIP ({$result['reason']})");
            } else {
                $fail++;
                $failures[] = $result;
                $this->line("  <fg=red>✗</> <comment>{$testCase['query']}</comment> → FAIL: {$result['reason']}");
                if ($this->option('verbose-scores') && !empty($result['results'])) {
                    foreach (array_slice($result['results'], 0, 3) as $r) {
                        $this->line("      {$r}");
                    }
                }
            }
        }

        $this->newLine();
        $total = $pass + $fail;
        $pct = $total > 0 ? round(($pass / $total) * 100) : 0;
        $this->info("=== RESULTS: {$pass}/{$total} PASS ({$pct}%), {$fail} FAIL, {$skip} SKIP ===");

        if (!empty($failures)) {
            $this->newLine();
            $this->warn('Failed queries:');
            foreach ($failures as $f) {
                $this->line("  <fg=red>✗</> {$f['query']}: {$f['reason']}");
            }
        }

        $this->newLine();
        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function evaluateCase(ProductSearchService $service, int $botId, array $testCase, int $limit): array
    {
        $query = $testCase['query'];
        $mustContain = $testCase['must_contain_any'] ?? [];
        $mustNotContain = $testCase['must_not_contain_any'] ?? [];
        $expectEmpty = $testCase['expect_empty'] ?? false;

        $results = $service->search($botId, $query, $limit);
        $names = array_map(fn($r) => mb_strtolower($r->name ?? ''), $results);
        $resultStrings = array_map(fn($r) => mb_substr($r->name, 0, 50) . ' | ' . ($r->price ?? '?'), $results);

        // Check: expect empty
        if ($expectEmpty) {
            if (empty($results)) {
                return ['verdict' => 'PASS', 'query' => $query, 'count' => 0, 'reason' => ''];
            }
            return [
                'verdict' => 'FAIL',
                'query' => $query,
                'count' => count($results),
                'reason' => 'Expected 0 results but got ' . count($results) . ': ' . ($names[0] ?? ''),
                'results' => $resultStrings,
            ];
        }

        // If no constraints, skip (ambiguous queries)
        if (empty($mustContain) && empty($mustNotContain)) {
            return ['verdict' => 'SKIP', 'query' => $query, 'count' => count($results), 'reason' => 'No constraints defined'];
        }

        // Check: must_contain_any — at least one result must contain one of these
        if (!empty($mustContain) && !empty($results)) {
            $found = false;
            foreach ($names as $name) {
                foreach ($mustContain as $keyword) {
                    if (str_contains($name, mb_strtolower($keyword))) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                return [
                    'verdict' => 'FAIL',
                    'query' => $query,
                    'count' => count($results),
                    'reason' => 'No result contains any of: ' . implode(', ', $mustContain),
                    'results' => $resultStrings,
                ];
            }
        }

        // Check: must_contain_any — if results empty but expected some
        if (!empty($mustContain) && empty($results)) {
            return [
                'verdict' => 'FAIL',
                'query' => $query,
                'count' => 0,
                'reason' => 'Expected results containing [' . implode(', ', $mustContain) . '] but got 0',
                'results' => [],
            ];
        }

        // Check: must_not_contain — no result should contain these
        foreach ($names as $name) {
            foreach ($mustNotContain as $forbidden) {
                if (str_contains($name, mb_strtolower($forbidden))) {
                    return [
                        'verdict' => 'FAIL',
                        'query' => $query,
                        'count' => count($results),
                        'reason' => "Result contains forbidden term '{$forbidden}': {$name}",
                        'results' => $resultStrings,
                    ];
                }
            }
        }

        return ['verdict' => 'PASS', 'query' => $query, 'count' => count($results), 'reason' => ''];
    }

    private function evaluateSingleQuery(ProductSearchService $service, int $botId, string $query, int $limit): void
    {
        $this->info("Query: \"{$query}\"");
        $this->newLine();

        // Show intent parsing
        $intentMethod = new \ReflectionMethod($service, 'parseQueryIntent');
        $intentMethod->setAccessible(true);
        $intent = $intentMethod->invoke($service, $query);

        $this->line("Normalized:   <info>{$intent['normalized']}</info>");
        $this->line("Tokens:       <info>" . implode(', ', $intent['tokens']) . "</info>");
        $this->line("Product type: <info>" . ($intent['product_type'] ?? '(none)') . "</info>");
        if (!empty($intent['dimensions'])) {
            $dims = array_map(fn($d) => $d['value'] . ($d['unit'] ? $d['unit'] : ''), $intent['dimensions']);
            $this->line("Dimensions:   <info>" . implode(', ', $dims) . "</info>");
        }
        if (!empty($intent['context'])) {
            $this->line("Context:      <info>" . implode(', ', $intent['context']) . "</info>");
        }
        $this->newLine();

        $results = $service->search($botId, $query, $limit);

        if (empty($results)) {
            $this->warn('No results found.');
            return;
        }

        $this->info(count($results) . ' results:');
        $rows = [];
        foreach ($results as $r) {
            $rows[] = [
                mb_substr($r->name, 0, 50),
                $r->price ?? '?',
                round($r->trgm_sim ?? 0, 3),
            ];
        }
        $this->table(['Name', 'Price', 'Trigram'], $rows);
    }
}
