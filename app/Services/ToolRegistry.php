<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\Log;

/**
 * Registry of available tools for agentic chatbot interactions.
 *
 * This is the foundation for evolving from a RAG chatbot to an agent-based system.
 * Each tool is a callable that takes structured input and returns structured output.
 *
 * Current tools:
 * - knowledge_search: Search the bot's knowledge base
 * - product_search: Search the product catalog
 * - order_lookup: Look up customer orders
 *
 * Future tools can be registered dynamically per bot or globally.
 */
class ToolRegistry
{
    /** @var array<string, array{description: string, handler: callable, parameters: array}> */
    private array $tools = [];

    public function __construct(
        private KnowledgeSearchService $knowledgeSearch,
        private ProductSearchService $productSearch,
        private OrderLookupService $orderLookup,
    ) {
        $this->registerDefaults();
    }

    private function registerDefaults(): void
    {
        $this->register('knowledge_search', [
            'description' => 'Caută informații în baza de cunoștințe a botului',
            'parameters' => [
                'query' => ['type' => 'string', 'required' => true],
                'limit' => ['type' => 'integer', 'default' => 5],
            ],
            'handler' => fn(int $botId, array $params) => [
                'context' => $this->knowledgeSearch->buildContext(
                    $botId,
                    $params['query'],
                    $params['limit'] ?? 5
                ),
            ],
        ]);

        $this->register('product_search', [
            'description' => 'Caută produse în catalogul magazinului',
            'parameters' => [
                'query' => ['type' => 'string', 'required' => true],
                'limit' => ['type' => 'integer', 'default' => 5],
            ],
            'handler' => fn(int $botId, array $params) => [
                'products' => $this->productSearch->search(
                    $botId,
                    $params['query'],
                    $params['limit'] ?? 5
                ),
            ],
        ]);

        $this->register('order_lookup', [
            'description' => 'Caută informații despre comanda unui client',
            'parameters' => [
                'order_number' => ['type' => 'string', 'required' => false],
                'email' => ['type' => 'string', 'required' => false],
                'phone' => ['type' => 'string', 'required' => false],
            ],
            'handler' => fn(int $botId, array $params) => $this->orderLookup->lookup($botId, $params),
        ]);
    }

    /**
     * Register a new tool.
     */
    public function register(string $name, array $config): void
    {
        $this->tools[$name] = $config;
    }

    /**
     * Get available tools for a specific bot (for OpenAI function calling format).
     */
    public function getToolDefinitions(int $botId): array
    {
        $definitions = [];
        foreach ($this->tools as $name => $config) {
            $properties = [];
            $required = [];
            foreach ($config['parameters'] as $paramName => $paramConfig) {
                $properties[$paramName] = [
                    'type' => $paramConfig['type'],
                    'description' => $paramConfig['description'] ?? $paramName,
                ];
                if ($paramConfig['required'] ?? false) {
                    $required[] = $paramName;
                }
            }

            $definitions[] = [
                'type' => 'function',
                'name' => $name,
                'description' => $config['description'],
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ];
        }

        return $definitions;
    }

    /**
     * Execute a tool by name.
     */
    public function execute(string $name, int $botId, array $params): array
    {
        if (!isset($this->tools[$name])) {
            Log::warning("ToolRegistry: unknown tool '{$name}'", ['bot_id' => $botId]);
            return ['error' => "Tool '{$name}' not found"];
        }

        try {
            $handler = $this->tools[$name]['handler'];
            return $handler($botId, $params);
        } catch (\Throwable $e) {
            Log::error("ToolRegistry: tool '{$name}' failed", [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * List registered tool names.
     */
    public function available(): array
    {
        return array_keys($this->tools);
    }
}
