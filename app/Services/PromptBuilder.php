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
    private string $summaryContext = '';
    private ?array $lastProductContext = null;
    private string $confidence = 'high';
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
     * Set pre-built knowledge context directly.
     */
    public function withKnowledgeContext(string $context): self
    {
        $this->knowledgeContext = $context;
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
                $this->productContext .= " | Stoc: " . ($p->stock_status === 'instock' ? 'Da' : 'Pe comandă');
                $this->productContext .= "\n";
            }
        }

        return $this;
    }

    /**
     * Add conversation summary context.
     */
    public function withSummary(string $summary): self
    {
        $this->summaryContext = $summary;
        return $this;
    }

    /**
     * Add arbitrary extra context (order info, policy, etc.).
     */
    public function withExtra(string $context): self
    {
        $this->extraContext .= $context;
        return $this;
    }

    /**
     * Set the last discussed product for contextual reference.
     * When user says "pe ăla vreau" or "îl comand", this product is used.
     */
    public function withLastProduct(?array $product): self
    {
        $this->lastProductContext = $product;
        return $this;
    }

    /**
     * Set the confidence level (from ConfidenceService).
     * Affects prompt instructions for low/medium confidence.
     */
    public function withConfidence(string $level): self
    {
        $this->confidence = $level;
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
     * Assemble the final prompt string with context budget enforcement.
     * Priority order: products > knowledge > summary > extra
     * Guardrails always last (highest priority for LLM).
     */
    public function build(): string
    {
        $channel = $this->isVoice ? 'voice' : 'chat';

        // Apply budget to variable-length context blocks
        $budgetService = app(ContextBudgetService::class);
        $blocks = $budgetService->fit([
            'products'  => $this->productContext,
            'knowledge' => $this->knowledgeContext,
            'summary'   => $this->summaryContext,
            'history'   => '', // history is managed separately by ConversationSummaryService
        ], $channel);

        $prompt = $this->base;

        // Log context token distribution for debugging
        $tokenizer = app(TokenizerService::class);
        $contextTokens = [
            'products' => !empty($blocks['products']) ? $tokenizer->count($blocks['products']) : 0,
            'knowledge' => !empty($blocks['knowledge']) ? $tokenizer->count($blocks['knowledge']) : 0,
            'summary' => !empty($blocks['summary']) ? $tokenizer->count($blocks['summary']) : 0,
            'history' => 0,
        ];

        try {
            app(DecisionLoggerService::class)->logContextTokens($contextTokens);
        } catch (\Throwable $e) {
            // DecisionLogger may not be initialized (e.g., during eval)
        }

        if (!empty($blocks['products'])) {
            $prompt .= "\n\n" . $blocks['products'];
        }

        if (!empty($blocks['knowledge'])) {
            $prompt .= "\n\n" . $blocks['knowledge'];
        }

        if (!empty($blocks['summary'])) {
            $prompt .= "\n\nRezumatul conversației anterioare:\n" . $blocks['summary'];
        }

        if (!empty($this->extraContext)) {
            $prompt .= "\n\n" . $this->extraContext;
        }

        // Inject last product memory for contextual references
        if ($this->lastProductContext) {
            $p = $this->lastProductContext;
            $prompt .= "\n\nPRODUS DISCUTAT ANTERIOR: {$p['name']} — {$p['price']} {$p['currency']}"
                . "\nDacă clientul face referire la \"ăla\", \"acela\", \"produsul\", sau vrea să comande fără a specifica — folosește ACEST produs.";
        }

        // Order intent handling rules
        $prompt .= implode("\n", [
            '',
            '',
            'REGULI COMENZI:',
            '- Dacă clientul vrea să PLASEZE o comandă nouă: ajută-l să comande. NU cere număr de comandă. NU cere email pentru verificare.',
            '- Dacă clientul vrea să VERIFICE o comandă existentă: cere-i numărul comenzii sau emailul.',
            '- "Vreau să comand" = comandă NOUĂ. "Unde e comanda mea" = verificare comandă EXISTENTĂ.',
            '- Dacă există un produs discutat anterior, folosește-l ca referință implicită pentru comanda nouă.',
        ]);

        // Response quality instructions (before guardrails, after context)
        $prompt .= self::responseQualityInstructions($this->isVoice);

        // Confidence-based prompt modifier
        if ($this->confidence !== 'high') {
            $confidenceService = app(ConfidenceService::class);
            $prompt .= $confidenceService->getPromptModifier($this->confidence);
        }

        // Fallback behavior instructions
        $hasContext = !empty($blocks['products']) || !empty($blocks['knowledge']);
        if (!$hasContext) {
            $prompt .= self::fallbackInstructions($this->bot);
        }

        // Guardrails always last (highest priority for LLM)
        $prompt = PromptGuardrails::apply($prompt, $this->isVoice);

        return $prompt;
    }

    /**
     * Context-aware fallback instructions when no relevant data was found.
     */
    private static function fallbackInstructions(Bot $bot): string
    {
        $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();

        $base = "\n\nCÂND NU AI INFORMAȚII SUFICIENTE:";
        $base .= "\n- NU spune doar 'Nu știu' sau 'Nu am informații'.";
        $base .= "\n- Explică pe scurt ce ai verificat și de ce nu ai găsit.";

        if ($hasProducts) {
            // E-commerce bot
            $base .= "\n- Sugerează produse similare sau categorii relevante.";
            $base .= "\n- Întreabă ce anume caută clientul mai specific (brand, dimensiune, buget).";
            $base .= "\n- Propune: 'Pot căuta și alte variante dacă îmi spui mai multe detalii.'";
        } else {
            // Service/general bot
            $base .= "\n- Sugerează contactul direct: telefon, email, sau programare.";
            $base .= "\n- Întreabă o întrebare de clarificare specifică.";
            $base .= "\n- Propune: 'Un coleg te poate ajuta cu mai multe detalii. Vrei să te pun în legătură?'";
        }

        return $base;
    }

    /**
     * Professional response formatting instructions.
     */
    private static function responseQualityInstructions(bool $isVoice): string
    {
        if ($isVoice) {
            return implode("\n", [
                '',
                'STIL RĂSPUNS:',
                '- Maxim 2 propoziții scurte și directe.',
                '- NU enumera liste. Dacă sunt mai multe opțiuni, menționează cea mai relevantă și întreabă dacă vrea altele.',
                '- Dacă întrebarea e complexă, pune o întrebare de clarificare în loc să dai un răspuns lung.',
                '- Termină cu o întrebare scurtă care ghidează conversația.',
            ]);
        }

        return implode("\n", [
            '',
            'STIL RĂSPUNS:',
            '- Răspunsuri clare, structurate și concise.',
            '- Când recomanzi produse, evidențiază beneficiile specifice, nu descrieri generice.',
            '- Dacă nu ești sigur ce caută clientul, pune o întrebare de clarificare.',
            '- Ghidează clientul către pasul următor (comandă, contact, vizită magazin).',
            '- NU repeta informații deja afișate în carduri de produse.',
        ]);
    }
}
