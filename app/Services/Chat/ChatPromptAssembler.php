<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\BotPromptVersion;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\WooCommerceProduct;
use App\Services\ConversationPolicyService;
use App\Services\IntentDetectionService;
use App\Services\KnowledgeSearchService;
use App\Services\PromptGuardrails;
use App\Services\StructuredPromptBuilder;
use App\Services\Widget\UserStateResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Composes the final system prompt sent to the LLM for a single chat
 * turn. Centralises logic that previously lived inline in
 * ChatbotApiController::generateAIResponse and ::buildPromptForStream —
 * two near-duplicate blocks that repeatedly drifted apart (see
 * commit 05e2138 for the last time they silently diverged).
 *
 * Composition order is load-bearing; the LLM has been tuned against
 * exactly this layering and moving a block changes tone and grounding:
 *
 *   1. Base prompt (cached per bot for 10 min — structured or freeform)
 *   2. Product-card contract (appended into the cache when the bot has
 *      any WooCommerceProduct rows)
 *   3. State steering (only structured-prompt bots, not cached —
 *      prepended per turn)
 *   4. Knowledge context (hybrid RAG, skipped for trivial intents)
 *   5. Extra context supplied by the caller (product cards, page
 *      context, order lookup results, etc.)
 *   6. Last-product memory (five-turn TTL, topic-reset aware)
 *   7. Order-intent rules (always)
 *   8. Conversation policy instructions (opt-in via bot.settings.v2_policies)
 *   9. Anti-hallucination guardrails (always last — highest priority)
 */
final class ChatPromptAssembler
{
    private const BASE_PROMPT_TTL_MINUTES = 10;
    private const HAS_PRODUCTS_TTL_SECONDS = 3600;
    private const DEFAULT_FALLBACK_PROMPT = 'Ești un asistent virtual. Răspunde scurt și util.';

    public function __construct(
        private readonly StructuredPromptBuilder $structuredPromptBuilder,
        private readonly UserStateResolver $userStateResolver,
        private readonly IntentDetectionService $intentService,
        private readonly KnowledgeSearchService $knowledgeSearch,
        private readonly ConversationPolicyService $policyService,
    ) {}

    /**
     * Build the system prompt plus telemetry for a single turn.
     *
     * @param string           $extraContext Caller-provided context (product cards,
     *                                       page context block, order lookup
     *                                       text). Appended verbatim after RAG.
     * @param array|null       $lastProduct  Resolved last-product memory — the
     *                                       caller owns the TTL / topic-reset
     *                                       logic so pre-processing and
     *                                       assembly share the same view.
     */
    public function assemble(
        Bot $bot,
        Conversation $conversation,
        string $userMessage,
        string $extraContext = '',
        ?Channel $channel = null,
        ?array $lastProduct = null,
    ): AssembledPrompt {
        $promptVersion = BotPromptVersion::selectForBot($bot->id);
        $structuredOn = $bot->usesStructuredPrompt();

        $systemPrompt = $this->cachedBasePrompt($bot, $promptVersion, $structuredOn);
        $systemPrompt = $this->applyStateSteering($systemPrompt, $structuredOn, $conversation, $userMessage);

        $intents = $this->intentService->detect($userMessage);
        $skipKnowledge = $this->intentService->shouldSkipKnowledge($userMessage);

        // Audit latență 2026-06-22: dacă orchestratorul deja a populat KB
        // în extraContext (marker generat de KnowledgeSearchService::buildContext),
        // sărim peste al doilea apel RAG identic. Economie: 150-400ms / turn.
        $orchestratorAlreadyDidRag = $extraContext !== ''
            && str_contains($extraContext, 'Informații relevante din baza de cunoștințe');

        $knowledgeContext = $orchestratorAlreadyDidRag
            ? ''
            : $this->resolveKnowledgeContext($bot, $userMessage, $skipKnowledge);
        if ($knowledgeContext !== '') {
            $systemPrompt .= "\n\n" . $knowledgeContext;
        }

        if ($extraContext !== '') {
            $systemPrompt .= $extraContext;
        }

        if ($lastProduct !== null) {
            $systemPrompt .= "\n\nPRODUS DISCUTAT ANTERIOR: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}"
                . "\nDacă clientul face referire la \"ăla\", \"acela\", \"produsul\", sau vrea să comande fără a specifica — folosește ACEST produs.";
        }

        $systemPrompt .= "\n\nREGULI COMENZI:"
            . "\n- Dacă clientul vrea să PLASEZE o comandă nouă: ajută-l. NU cere număr de comandă. NU cere email pentru verificare."
            . "\n- Dacă clientul vrea să VERIFICE o comandă existentă: cere-i numărul comenzii sau emailul."
            . "\n- \"Vreau să comand\" = comandă NOUĂ. \"Unde e comanda mea\" = verificare EXISTENTĂ.";

        // Reguli generice împotriva pattern-urilor observate în prod:
        //  - "ofertă personalizată" ca dead-end: nu există flow real, blochează conversia
        //  - promisiuni de acțiune (anulez, contactez, voi trimite) fără tool care le execută
        //  - repetare mesaj aproape identic când utilizatorul insistă (loop)
        $systemPrompt .= "\n\nREGULI DE CONVERSAȚIE:"
            . "\n- NU sugera 'ofertă personalizată', 'discount' sau 'preț special' decât dacă clientul cere explicit ('aveți reducere?')."
            . " Dacă cantitatea pare mare, întreabă o singură dată dacă e nevoie de o ofertă pentru cantități en-gros; dacă răspunde că vrea să cumpere normal, mergi mai departe la lead capture sau coș."
            . "\n- NU promite o acțiune (anulez, contactez echipa, voi trimite email, voi verifica) decât dacă ai un instrument disponibil care o execută cu adevărat."
            . " Dacă nu poți face acțiunea direct, spune clar: 'nu pot face asta direct din chat, dar un coleg te contactează în maxim 24h' și escaladează la operator."
            . "\n- NU repeta același mesaj de două ori la rând. Dacă utilizatorul reformulează cererea, schimbă unghiul: cere o clarificare specifică, dă o alternativă concretă, sau escaladează."
            . "\n- Când clientul spune 'vreau să comand', 'mă ajuți să comand', 'ghidează-mă' sau echivalent: treci direct la lead capture (nume, telefon, adresă) — fără a mai propune oferte alternative.";

        // Topic opt-out — bot nu răspunde pe anumite subiecte (compliance).
        $optOut = $bot->topicOptOut();
        if (!empty($optOut)) {
            $systemPrompt .= "\n\nSUBIECTE INTERZISE: nu răspunde și nu da sfaturi pe: "
                . implode(', ', $optOut)
                . ". Dacă utilizatorul aduce aceste subiecte, spune: 'Pentru întrebări pe acest subiect, te rugăm să consulți un specialist autorizat' și redirecționează spre subiectul principal al business-ului.";
        }

        [$systemPrompt, $policyApplied, $policyTone] = $this->applyConversationPolicy($systemPrompt, $bot, $channel);

        $systemPrompt = PromptGuardrails::apply($systemPrompt);

        return new AssembledPrompt(
            systemPrompt: $systemPrompt,
            intents: $intents,
            skipKnowledge: $skipKnowledge,
            knowledgeChars: mb_strlen($knowledgeContext),
            policyApplied: $policyApplied,
            policyTone: $policyTone,
        );
    }

    private function cachedBasePrompt(Bot $bot, ?BotPromptVersion $promptVersion, bool $structuredOn): string
    {
        $cacheKey = "bot_system_prompt_{$bot->id}_" . ($structuredOn ? 'structured' : 'legacy');

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::BASE_PROMPT_TTL_MINUTES),
            function () use ($bot, $promptVersion, $structuredOn): string {
                if ($structuredOn) {
                    $prompt = $this->structuredPromptBuilder->build($bot);
                    if (trim($prompt) === '') {
                        $prompt = $promptVersion?->system_prompt
                            ?? $bot->system_prompt
                            ?? self::DEFAULT_FALLBACK_PROMPT;
                    }
                } else {
                    $prompt = $promptVersion?->system_prompt
                        ?? $bot->system_prompt
                        ?? self::DEFAULT_FALLBACK_PROMPT;
                }

                if ($this->botHasProducts($bot)) {
                    $prompt .= "\n\n"
                        . "Ești asistentul unui magazin online cu carduri vizuale."
                        . "\n"
                        . "\nCÂND CONTEXTUL CONȚINE 'PRODUSELE AFIȘATE CLIENTULUI' (sau '[CARDURI PRODUSE'):"
                        . "\nLista de produse de acolo este AFIȘATĂ AUTOMAT ca CARDURI vizuale sub mesajul tău — clientul le vede direct, cu imagine, nume și preț. Te folosești de listă ca SURSĂ DE ADEVĂR pentru ce spui, dar NU O REPETI."
                        . "\n"
                        . "\nFORMAT răspuns pentru căutare de produse:"
                        . "\n  1) O propoziție de intro care menționează tipul/categoria (30-100 caractere)."
                        . "\n  2) Opțional o întrebare scurtă de follow-up (ex: 'Ce grosime cauți?')."
                        . "\n  3) STOP. Nu mai scrie nimic."
                        . "\nExemple bune: 'Am găsit câteva variante de polistiren pentru tine. Ce grosime cauți?' / 'Uite 4 opțiuni de vopsele lavabile disponibile.'"
                        . "\nExemple REFUZATE: orice răspuns cu listă numerotată sau bullet-uri care repetă numele produselor (cardurile le arată deja)."
                        . "\n"
                        . "\nFORMAT răspuns pentru întrebări de comparație/preț:"
                        . "\nRăspunde la întrebare folosind nume și prețuri EXACTE din lista din context (1-3 propoziții). Ex: 'Cel mai ieftin este Polistiren Eps 80 5 Cm la 63.50 RON.'"
                        . "\n"
                        . "\nGROUNDING CRITIC: dacă produsele din context sunt complet nepotrivite pentru întrebare (client a întrebat X, lista arată Y din altă categorie), SPUNE EXPLICIT 'N-am găsit exact ce cauți' și cere clarificare. Răspunsul tău nu trebuie să se contrazică cu lista."
                        . "\n"
                        . "\nAlte reguli:"
                        . "\n- Dacă contextul conține '[NU s-au găsit produse relevante]', poți spune că nu ai găsit."
                        . "\n- Dacă întrebarea NU e despre produse (livrare, retur, contact), răspunde la întrebare fără a menționa produse."
                        . "\n- NU inventa produse, prețuri sau specificații. Răspunde doar din datele furnizate."
                        . "\n- Fii natural, concis și util.";
                }

                return $prompt;
            }
        );
    }

    private function botHasProducts(Bot $bot): bool
    {
        try {
            return Cache::remember(
                "bot_{$bot->id}_has_products",
                self::HAS_PRODUCTS_TTL_SECONDS,
                fn (): bool => WooCommerceProduct::where('bot_id', $bot->id)->exists()
            );
        } catch (\Throwable) {
            // Cache flake — still answer correctly, just without caching.
            return WooCommerceProduct::where('bot_id', $bot->id)->exists();
        }
    }

    private function applyStateSteering(
        string $systemPrompt,
        bool $structuredOn,
        Conversation $conversation,
        string $userMessage,
    ): string {
        if (!$structuredOn) {
            return $systemPrompt;
        }

        try {
            $stateInfo = $this->userStateResolver->resolve($conversation, $userMessage, []);
            $state = $stateInfo['state'] ?? '';
            if ($state === '' || $state === 'browsing') {
                return $systemPrompt;
            }

            $steerMap = [
                'high_intent'     => "=== STARE CLIENT: HIGH INTENT ===\nClientul e gata să acționeze. Răspunsuri SCURTE (max 2-3 propoziții), orientate pe pași concreți. Confirmă acțiunea, nu pune întrebări de discovery.",
                'stuck'           => "=== STARE CLIENT: STUCK / CONFUZ ===\nClientul e blocat. Răspunsuri SIMPLE, un singur pas. Evită jargon. Propune TU o direcție în loc să ceri mai multe detalii.",
                'price_sensitive' => "=== STARE CLIENT: PRICE SENSITIVE ===\nClientul caută cea mai bună valoare. Prioritizează opțiuni ieftine de calitate, menționează promoții. Evită premium dacă nu e cerut.",
                'comparing'       => "=== STARE CLIENT: COMPARĂ ===\nClientul cântărește alternative. Dă o comparație structurată scurtă și recomandă clar câștigătorul la final.",
            ];

            $steer = $steerMap[$state] ?? '';
            if ($steer === '') {
                return $systemPrompt;
            }

            return $steer . "\n\n" . $systemPrompt;
        } catch (\Throwable $e) {
            Log::debug('state steering skipped', ['err' => $e->getMessage()]);
            return $systemPrompt;
        }
    }

    private function resolveKnowledgeContext(Bot $bot, string $userMessage, bool $skipKnowledge): string
    {
        if ($skipKnowledge) {
            return '';
        }

        $searchLimit = $bot->knowledge_search_limit ?? ($this->botHasProducts($bot) ? 8 : 5);

        try {
            return $this->knowledgeSearch->buildContext($bot->id, $userMessage, $searchLimit);
        } catch (\Throwable $e) {
            Log::warning('Knowledge search failed for chatbot', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * @return array{0: string, 1: bool, 2: ?string}
     */
    private function applyConversationPolicy(string $systemPrompt, Bot $bot, ?Channel $channel): array
    {
        if (empty($bot->settings['v2_policies'])) {
            return [$systemPrompt, false, null];
        }

        try {
            $policy = $this->policyService->getPolicy($bot, $channel);
            $policyInstructions = $this->policyService->toPromptInstructions($policy);

            if (empty($policyInstructions)) {
                return [$systemPrompt, false, null];
            }

            return [
                $systemPrompt . "\n\n" . $policyInstructions,
                true,
                $policy['tone'] ?? 'default',
            ];
        } catch (\Throwable $e) {
            Log::warning('ConversationPolicy injection failed, skipping', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            return [$systemPrompt, false, null];
        }
    }
}
