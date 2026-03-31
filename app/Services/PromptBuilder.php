<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\ConversationPolicy;
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
    private string $frustrationLevel = 'low';
    private string $queryTypeModifier = '';
    private string $strategyModifier = '';
    private bool $isVoice = false;
    private Bot $bot;
    private ?ConversationPolicy $policy = null;

    private function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->base = $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
        $this->policy = ConversationPolicy::where('bot_id', $bot->id)
            ->where('is_active', true)
            ->first();
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
    public function withKnowledge(string $query, int $limit = 8, ?int $maxChars = null): self
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
     * Set the frustration level (from FrustrationDetectorService).
     * Adapts tone and behavior when user is frustrated.
     */
    public function withFrustration(string $level): self
    {
        $this->frustrationLevel = $level;
        return $this;
    }

    /**
     * Set query intelligence modifier (from QueryIntelligenceService).
     * Adapts response style based on query type.
     */
    public function withQueryIntelligence(array $queryIntelligence): self
    {
        $this->queryTypeModifier = $queryIntelligence['strategy']['prompt_modifier'] ?? '';
        return $this;
    }

    /**
     * Set conversation strategy modifier (from ConversationStrategyEngine).
     * Adapts CTA timing, lead capture, and conversation flow.
     */
    public function withStrategy(array $strategy): self
    {
        $this->strategyModifier = $strategy['prompt_modifier'] ?? '';
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

        // Inject tenant conversation policy (tone, verbosity, CTA, etc.)
        $policyLayer = $this->buildPolicyLayer();
        if ($policyLayer !== '') {
            $prompt .= "\n\n" . $policyLayer;
        }

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
            '- Pentru comandă nouă cere: NUME, apoi TELEFON, apoi opțional EMAIL. Cere-le pe rând, nu toate deodată.',
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

        // Query intelligence modifier (adapts response style per query type)
        if (!empty($this->queryTypeModifier)) {
            $prompt .= "\n\n" . $this->queryTypeModifier;
        }

        // Conversation strategy modifier (CTA timing, lead capture, flow)
        if (!empty($this->strategyModifier)) {
            $prompt .= "\n" . $this->strategyModifier;
        }

        // Frustration-aware prompt modifier
        if ($this->frustrationLevel !== 'low') {
            $frustrationService = app(FrustrationDetectorService::class);
            $prompt .= $frustrationService->getPromptModifier($this->frustrationLevel);
        }

        // Fallback behavior instructions
        $hasContext = !empty($blocks['products']) || !empty($blocks['knowledge']);
        if (!$hasContext) {
            if ($this->policy && !empty($this->policy->fallback_message)) {
                $prompt .= "\n\nCÂND NU AI INFORMAȚII EXACTE:\n- " . $this->policy->fallback_message;
            } else {
                $prompt .= self::fallbackInstructions($this->bot);
            }
        }

        // Escalation message from policy
        if ($this->policy && !empty($this->policy->escalation_message)) {
            $prompt .= "\n\nCÂND SITUAȚIA NECESITĂ ESCALADARE:\n- " . $this->policy->escalation_message;
        }

        // Guardrails always last (highest priority for LLM)
        $prompt = PromptGuardrails::apply($prompt, $this->isVoice);

        return $prompt;
    }

    /**
     * Build prompt instructions from the tenant's ConversationPolicy.
     * Returns empty string if no active policy exists.
     */
    private function buildPolicyLayer(): string
    {
        if (!$this->policy) {
            return '';
        }

        $lines = ['POLITICA CONVERSAȚIEI:'];

        // Tone
        $toneMap = [
            'friendly'     => 'Folosește un ton prietenos și cald.',
            'formal'       => 'Folosește un ton formal și profesionist.',
            'casual'       => 'Fii casual și relaxat, ca un prieten.',
            'professional' => 'Fii profesionist dar accesibil.',
        ];
        if (!empty($this->policy->tone) && isset($toneMap[$this->policy->tone])) {
            $lines[] = $toneMap[$this->policy->tone];
        }

        // Verbosity (1-5)
        $verbosityMap = [
            1 => 'Răspunde ULTRA-SCURT: maxim 1-2 propoziții.',
            2 => 'Răspunde concis: 2-3 propoziții.',
            3 => 'Răspunde cu detalii moderate.',
            4 => 'Oferă răspunsuri detaliate cu explicații.',
            5 => 'Oferă răspunsuri comprehensive și detaliate.',
        ];
        $verbosity = (int) ($this->policy->verbosity ?? 0);
        if ($verbosity >= 1 && $verbosity <= 5) {
            $lines[] = $verbosityMap[$verbosity];
        }

        // Emoji
        if ($this->policy->emoji_allowed) {
            $lines[] = 'Poți folosi emoji-uri moderate (1-2 per mesaj). 😊';
        }

        // CTA aggressiveness (1-5)
        $ctaMap = [
            1 => 'NU împinge vânzarea. Lasă clientul să decidă singur.',
            2 => 'Menționează produse/servicii doar dacă clientul întreabă.',
            3 => 'Sugerează produse/servicii când e relevant.',
            4 => 'Recomandă activ produse/servicii potrivite.',
            5 => 'Ghidează ACTIV clientul spre comandă/programare. Fiecare răspuns trebuie să includă un pas următor clar.',
        ];
        $cta = (int) ($this->policy->cta_aggressiveness ?? 0);
        if ($cta >= 1 && $cta <= 5) {
            $lines[] = $ctaMap[$cta];
        }

        // Lead aggressiveness (1-5)
        $leadMap = [
            1 => 'NU cere date de contact decât dacă clientul oferă voluntar.',
            2 => 'Cere date de contact doar dacă clientul pare interesat.',
            3 => 'Cere date de contact când conversația e substanțială.',
            4 => 'Cere date de contact după ce oferi valoare inițială.',
            5 => 'Cere DEVREME numele și telefonul clientului.',
        ];
        $lead = (int) ($this->policy->lead_aggressiveness ?? 0);
        if ($lead >= 1 && $lead <= 5) {
            $lines[] = $leadMap[$lead];
        }

        // Prohibited phrases
        $prohibited = $this->policy->prohibited_phrases ?? [];
        if (!empty($prohibited)) {
            $lines[] = 'NU folosi NICIODATĂ aceste expresii: ' . implode(', ', $prohibited);
        }

        // Required phrases
        $required = $this->policy->required_phrases ?? [];
        if (!empty($required)) {
            $lines[] = 'Incluzi OBLIGATORIU în răspunsuri aceste expresii când e relevant: ' . implode(', ', $required);
        }

        // Brand vocabulary
        $brandVocab = $this->policy->brand_vocabulary ?? [];
        if (!empty($brandVocab)) {
            $lines[] = 'Folosește acești termeni specifici brandului: ' . implode(', ', $brandVocab);
        }

        // Custom handoff message
        if (!empty($this->policy->custom_handoff_message)) {
            $lines[] = 'Când transferi la operator, folosește: ' . $this->policy->custom_handoff_message;
        }

        // Custom lead prompt
        if (!empty($this->policy->custom_lead_prompt)) {
            $lines[] = 'Când ceri date de contact, folosește: ' . $this->policy->custom_lead_prompt;
        }

        // Custom out of stock message
        if (!empty($this->policy->custom_out_of_stock)) {
            $lines[] = 'Când un produs nu e în stoc, spune: ' . $this->policy->custom_out_of_stock;
        }

        // Business rules
        $rules = $this->policy->business_rules ?? [];
        if (!empty($rules)) {
            $lines[] = '';
            $lines[] = 'REGULI DE BUSINESS:';
            foreach ($rules as $rule) {
                $lines[] = '- ' . $rule;
            }
        }

        // Snippets (predefined Q&A)
        $snippets = $this->policy->snippets ?? [];
        if (!empty($snippets)) {
            $lines[] = '';
            $lines[] = 'RĂSPUNSURI PREDEFINITE (folosește-le când se potrivesc):';
            foreach ($snippets as $snippet) {
                $q = $snippet['question'] ?? $snippet['q'] ?? '';
                $a = $snippet['answer'] ?? $snippet['a'] ?? '';
                if ($q && $a) {
                    $lines[] = "Întrebare: {$q} → Răspuns: {$a}";
                }
            }
        }

        // Only return if we have more than just the header
        if (count($lines) <= 1) {
            return '';
        }

        return implode("\n", $lines);
    }

    /**
     * Context-aware fallback instructions when no relevant data was found.
     */
    private static function fallbackInstructions(Bot $bot): string
    {
        $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();

        $base = "\n\nCÂND NU AI INFORMAȚII EXACTE:";
        $base .= "\n- NU spune NICIODATĂ 'Nu știu' sau 'Nu am informații' — sună neprofesionist.";
        $base .= "\n- În schimb, arată că ai înțeles întrebarea și redirecționează elegant.";

        if ($hasProducts) {
            // E-commerce bot
            $base .= "\n- Exemplu bun: 'Din păcate nu am găsit exact ce cauți. Poți să-mi dai mai multe detalii sau să contactezi echipa noastră direct.'";
            $base .= "\n- NU spune că ai găsit alternative dacă nu ai. Întreabă ce caută mai specific.";
            $base .= "\n- Oferă un pas următor concret: reformulare, contact, sau categorie mai largă.";
        } else {
            // Service/general bot
            $base .= "\n- Exemplu bun: 'Bună întrebare! Pentru acest subiect specific, cel mai bine e să vorbești direct cu echipa noastră.'";
            $base .= "\n- Oferă contact concret (telefon, email) sau propune programare.";
            $base .= "\n- Dacă poți răspunde parțial, fă-o, și menționează că un coleg poate completa detaliile.";
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
            '- Răspunde natural, ca un consultant uman experimentat — nu ca un chatbot.',
            '- Prima propoziție = răspunsul direct. Apoi detalii dacă sunt necesare.',
            '- Dacă clientul pune o întrebare simplă, răspunde SCURT (1-2 propoziții). Nu forța detalii.',
            '- Dacă clientul explorează opțiuni, oferă o comparație structurată concisă.',
            '- Dacă clientul e frustrat sau nemulțumit, fii empatic ÎNAINTE de orice altceva.',
            '- NU repeta informații din cardurile de produse (clientul le vede deja).',
            '- NU folosi formule robotice: "Sigur!", "Cu plăcere!", "Desigur!" la fiecare mesaj.',
            '- Variază deschiderile: uneori răspunde direct, alteori pune o întrebare de clarificare.',
            '- Termină cu UN SINGUR call-to-action natural (nu mai mult de unul).',
            '- Emoji: poți folosi 1 emoji subtil pe mesaj când e potrivit (✅ ℹ️ 📦 🔍). NU folosi emoji la fiecare mesaj. NU folosi emoji în situații serioase (reclamații, probleme).',
            '- Lungime ideală: 2-4 propoziții pentru răspunsuri normale. Mai scurt pentru confirmări. Mai lung doar pentru comparații complexe.',
        ]);
    }
}
