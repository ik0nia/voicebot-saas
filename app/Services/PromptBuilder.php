<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\WooCommerceProduct;

/**
 * Centralized prompt assembly for chat and voice channels.
 *
 * Usage:
 *   $prompt = PromptBuilder::for($bot)
 *       ->withKnowledge($query)
 *       ->withProducts($userMessage)
 *       ->withOrderContext($orderContext)
 *       ->forVoice()
 *       ->build();
 */
class PromptBuilder
{
    private string $base;
    private string $knowledgeContext = '';
    private string $productContext = '';
    private string $extraContext = '';
    private bool $isVoice = false;
    private Bot $bot;

    private function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->base = $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
    }

    public static function for(Bot $bot): self
    {
        return new self($bot);
    }

    /**
     * Set a custom base prompt (e.g., from prompt versioning).
     */
    public function withBase(string $prompt): self
    {
        $this->base = $prompt;
        return $this;
    }

    /**
     * Add knowledge base context for a given query.
     */
    public function withKnowledge(string $query, int $limit = 5, ?int $maxChars = null): self
    {
        $service = app(KnowledgeSearchService::class);
        $this->knowledgeContext = $service->buildContext($this->bot->id, $query, $limit, $maxChars);
        return $this;
    }

    /**
     * Add product search context if bot has products.
     */
    public function withProducts(string $query, int $limit = 5): self
    {
        if (!WooCommerceProduct::where('bot_id', $this->bot->id)->exists()) {
            return $this;
        }

        $service = app(ProductSearchService::class);
        $products = $service->search($this->bot->id, $query, $limit);

        if (!empty($products)) {
            $this->productContext = "Produse relevante găsite:\n";
            foreach ($products as $p) {
                $this->productContext .= "- {$p->name}: {$p->price} {$p->currency}";
                if ($p->sale_price && $p->regular_price && $p->sale_price < $p->regular_price) {
                    $this->productContext .= " (reducere de la {$p->regular_price})";
                }
                $this->productContext .= "\n";
            }
        }

        return $this;
    }

    /**
     * Add arbitrary extra context (order info, product count, etc.).
     */
    public function withExtra(string $context): self
    {
        $this->extraContext .= $context;
        return $this;
    }

    /**
     * Mark this prompt as voice-specific (adds voice guardrails).
     */
    public function forVoice(): self
    {
        $this->isVoice = true;
        return $this;
    }

    /**
     * Assemble the final prompt string.
     * Order: base → knowledge → products → extra → guardrails
     */
    public function build(): string
    {
        $prompt = $this->base;

        if (!empty($this->knowledgeContext)) {
            $prompt .= "\n\n" . $this->knowledgeContext;
        }

        if (!empty($this->productContext)) {
            $prompt .= "\n\n" . $this->productContext;
        }

        if (!empty($this->extraContext)) {
            $prompt .= "\n\n" . $this->extraContext;
        }

        // Guardrails always last (highest priority for LLM)
        $prompt = PromptGuardrails::apply($prompt, $this->isVoice);

        return $prompt;
    }
}
