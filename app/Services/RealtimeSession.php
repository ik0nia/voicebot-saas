<?php

namespace App\Services;

use App\Contracts\TtsOutputStrategy;
use App\Jobs\AnalyzeCallSentiment;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Lead;
use App\Models\Transcript;
use App\Models\CallEvent;
use App\Services\TtsStrategies\OpenAiNativeTts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Manages a single OpenAI Realtime session tied to a phone call.
 *
 * Responsibilities:
 *  - Building the system instructions (including knowledge-base context).
 *  - Dispatching incoming OpenAI Realtime events to the appropriate handler.
 *  - Persisting transcripts and call events as they arrive.
 *  - Cleaning up when the session ends.
 */
class RealtimeSession
{
    private Bot $bot;
    private Call $call;
    private RealtimeClient $client;
    private KnowledgeSearchService $knowledgeService;
    private TtsOutputStrategy $ttsStrategy;

    /** @var string Cached knowledge-base context to avoid redundant updates. */
    private string $conversationContext = '';

    /** @var bool|null Cached check for whether this bot has WooCommerce products. */
    private ?bool $hasProducts = null;

    /** @var array<int, array{role: string, content: string}> Buffer for transcripts not yet flushed. */
    private array $transcriptBuffer = [];

    /** @var bool Whether we're waiting for session.updated before sending the greeting. */
    private bool $pendingGreeting = false;

    /** @var FillingMessageService Filling messages for latency masking. */
    private FillingMessageService $fillingService;

    /** @var ConversationStateService Server-side turn context for follow-ups. */
    private ConversationStateService $conversationState;

    /** @var FillingAudioCacheService Pre-cached filling audio for cloned voices. */
    private FillingAudioCacheService $fillingAudioCache;

    /** @var float|null Timestamp when the last user transcript was received (for latency tracking). */
    private ?float $lastUserTranscriptAt = null;

    /** @var bool Whether a filling message has been sent for the current response cycle. */
    private bool $fillingSentForCurrentResponse = false;

    /** @var int Number of filling messages sent in the current response cycle. */
    private int $fillingCount = 0;

    /** @var string|null The last user transcript text (for context preservation on interrupt). */
    private ?string $pendingUserTranscript = null;

    /** @var string|null Cached context that was being prepared when filling was sent. */
    private ?string $pendingContext = null;

    /** @var string|null Detected intent for current transcript (for smart thresholds). */
    private ?string $currentIntent = null;

    public function __construct(Bot $bot, Call $call, ?TtsOutputStrategy $ttsStrategy = null)
    {
        // Tenant isolation: ensure Bot and Call belong to the same tenant
        if ($bot->tenant_id !== $call->tenant_id) {
            Log::error('RealtimeSession: tenant_id mismatch between Bot and Call', [
                'bot_id' => $bot->id,
                'bot_tenant_id' => $bot->tenant_id,
                'call_id' => $call->id,
                'call_tenant_id' => $call->tenant_id,
            ]);
            throw new \InvalidArgumentException('Bot and Call must belong to the same tenant.');
        }

        $this->bot = $bot;
        $this->call = $call;
        $this->client = new RealtimeClient();
        $this->knowledgeService = new KnowledgeSearchService();
        $this->ttsStrategy = $ttsStrategy ?? new OpenAiNativeTts();
        $this->hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $this->bot->id)->exists();
        $this->fillingService = new FillingMessageService();
        $this->conversationState = new ConversationStateService();
        $this->fillingAudioCache = new FillingAudioCacheService();
    }

    public function getTtsStrategy(): TtsOutputStrategy
    {
        return $this->ttsStrategy;
    }

    /**
     * Build the full session.update payload for this call's bot configuration.
     *
     * @return array The session.update event to send to OpenAI.
     */
    public function getSessionConfig(): array
    {
        $instructions = $this->buildInstructions();
        $settings = $this->bot->settings ?? [];

        return $this->client->buildSessionConfig([
            'instructions' => $instructions,
            'voice' => $this->bot->voice ?? 'alloy',
            'modalities' => $this->ttsStrategy->getModalities(),
            'vad_type' => $settings['vad_type'] ?? 'semantic_vad',
            'vad_eagerness' => $settings['vad_eagerness'] ?? 'low',
            'temperature' => $settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? 1024,
        ]);
    }

    /**
     * Return the WebSocket connection config from the underlying client.
     *
     * @return array{url: string, headers: array<string, string>}
     */
    public function getConnectionConfig(): array
    {
        return $this->client->getConnectionConfig();
    }

    // -----------------------------------------------------------------
    //  Instruction builder
    // -----------------------------------------------------------------

    /**
     * Assemble the system prompt including knowledge-base context and behavioural rules.
     */
    private function buildInstructions(): string
    {
        $base = $this->bot->system_prompt
            ?? 'Ești un asistent vocal prietenos. Răspunzi în limba română.';

        if ($this->hasProducts) {
            $base .= "\n\n=== INSTRUCȚIUNI PRODUSE (PRIORITARE) ==="
                . "\nEști asistentul vocal al unui magazin online cu catalog real de produse."
                . "\nCând primești o listă de 'Produse găsite' în context, acestea sunt REALE din baza de date a magazinului."
                . "\nTREBUIE să le recomanzi clientului cu NUME EXACT și PREȚ."
                . "\nExemplu: 'Da, avem Glet Fino Bello la 22 lei și Glet Xsuper Adeplast la 15 lei. Pe care îl doriți?'"
                . "\nNU refuza să dai prețuri. NU spune că nu ai informații despre produse. Prețurile din context sunt corecte."
                . "\nDacă clientul întreabă de stoc, spune că produsul e disponibil (produsele afișate sunt în stoc)."
                . "\nDacă NU apar produse în context pentru o căutare, atunci spune că nu ai găsit exact acel produs și sugerează să contacteze magazinul."
                . "\n=== SFÂRȘIT INSTRUCȚIUNI PRODUSE ===";
        }

        // Augment with knowledge-base context + product search
        try {
            $query = $this->hasProducts
                ? 'produse populare, categorii principale, informații magazin'
                : 'informații generale despre companie și servicii';

            $knowledgeContext = $this->knowledgeService->buildContext(
                $this->bot->id,
                $query,
                4, // Keep init context lean; mid-call updates fetch more
                3500 // Max 3500 chars for initial voice prompt
            );

            if ($knowledgeContext) {
                $base .= "\n\n" . $knowledgeContext;
            }

            // Add initial popular products for immediate availability
            if ($this->hasProducts) {
                try {
                    $productSearch = app(ProductSearchService::class);
                    $popular = $productSearch->search($this->bot->id, 'produse populare', 5);
                    if (!empty($popular)) {
                        $base .= "\n\nProduse populare din magazin:\n";
                        foreach ($popular as $p) {
                            $base .= "- {$p->name}: {$p->price} {$p->currency}\n";
                        }
                    }
                } catch (\Throwable $e) {
                    // silently skip
                }
            }
        } catch (\Throwable $e) {
            Log::warning("RealtimeSession: failed to load knowledge context for bot {$this->bot->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $language = $this->bot->language ?? 'română';

        // Add category tree context for guided navigation
        if ($this->hasProducts) {
            try {
                $categoryContext = \App\Models\WooCommerceCategory::toChatContext($this->bot->id);
                if ($categoryContext) {
                    $base .= "\n\n=== CATEGORII DISPONIBILE ===\n" . $categoryContext . "\n=== SFÂRȘIT CATEGORII ===";
                }
            } catch (\Throwable $e) {
                // silently skip
            }

            $base .= "\n\n=== NAVIGARE INTELIGENTĂ PRODUSE ==="
                . "\nCând clientul întreabă de un BRAND/PRODUCĂTOR:"
                . "\n- Spune-i ce categorii de produse avem de la acel brand."
                . "\n- Întreabă-l ce categorie îl interesează pentru a-i arăta produsele potrivite."
                . "\n- Exemplu: 'Da, avem produse Ceresit în mai multe categorii: adezivi, chituri și hidroizolații. Ce vă interesează?'"
                . "\n"
                . "\nCând clientul întreabă de o CATEGORIE de produse (ex: polistiren, adeziv, glet):"
                . "\n- Spune-i ce tipuri/subcategorii sunt disponibile."
                . "\n- Menționează brand-urile disponibile dacă sunt relevante."
                . "\n- Întreabă ce tip anume caută sau ce utilizare are în vedere."
                . "\n- Exemplu: 'Avem mai multe tipuri de polistiren: expandat, extrudat și grafitat. Ce grosime și tip vă interesează?'"
                . "\n"
                . "\nPRINCIPIU: Ghidează clientul PAS CU PAS prin opțiuni. Nu încerca să dai tot dintr-o dată."
                . "\nMai bine 2-3 schimburi scurte de replici decât un răspuns lung și confuz."
                . "\n=== SFÂRȘIT NAVIGARE ===";
        }

        $base .= "\n\n=== MESAJE DE AȘTEPTARE ==="
            . "\nUneori vei primi instrucțiuni să spui un mesaj scurt de așteptare (ex: 'O clipă, verific...')."
            . "\nDupă ce spui mesajul de așteptare, vei primi context actualizat cu informațiile căutate."
            . "\nRăspunde IMEDIAT cu informațiile primite, fără să repeți salutul sau mesajul de așteptare."
            . "\nConversația trebuie să curgă natural: așteptare → răspuns cu informații."
            . "\n=== SFÂRȘIT MESAJE AȘTEPTARE ===";

        $base .= "\n\nReguli importante:";
        $base .= "\n- Răspunde natural și concis în limba {$language}.";
        $base .= "\n- Dacă nu știi răspunsul, oferă-te să transferi apelul la un operator uman.";
        $base .= "\n- Fii politicos și profesional în toate interacțiunile.";

        $base .= "\n\n=== ERORI DE TRANSCRIERE (CRITIC) ==="
            . "\nTranscrierea vocală generează uneori texte FALSE din liniște sau zgomot."
            . "\nAceste texte TREBUIE IGNORATE COMPLET — NU RĂSPUNDE la ele:"
            . "\n- 'subscribe', 'like', 'abonați-vă', 'mulțumim pentru vizionare/urmărire'"
            . "\n- 'subtitrare', 'traducere', 'copyright', adrese web"
            . "\n- 'la revedere', 'noapte bună', 'poftă bună', 'la mulți ani' (fără context real)"
            . "\n- Sunete fără sens: 'uh', 'um', 'hm', cuvinte repetate"
            . "\n- Orice pare subtitrare de film/YouTube/podcast"
            . "\nCând primești așa ceva, NU RĂSPUNDE DELOC. Taci și așteaptă un mesaj real."
            . "\n=== SFÂRȘIT ERORI ===";

        // V2: Order lookup instructions
        $base .= "\n\n=== COMENZI ==="
            . "\nDacă clientul întreabă de o comandă (unde e comanda, status comandă, când ajunge, AWB, colet):"
            . "\n- Cere-i numărul comenzii: 'Puteți să îmi spuneți numărul comenzii?'"
            . "\n- Sau emailul: 'Cu ce adresă de email ați comandat?'"
            . "\n- Când primești informațiile despre comandă în context, spune-i clientului statusul clar."
            . "\n- Dacă comanda e livrată/expediată și ai AWB, menționează-l."
            . "\n=== SFÂRȘIT COMENZI ===";

        // V2: Lead capture vocal
        $base .= "\n\n=== CAPTARE DATE CLIENT (FOARTE IMPORTANT) ==="
            . "\nCÂND să propui: DOAR după ce ai oferit informații utile clientului (prețuri, disponibilitate, recomandări)."
            . "\nNU propune la primele replici. Lasă clientul să primească valoare mai întâi."
            . "\nPropune NATURAL, ca o continuare a conversației, de exemplu:"
            . "\n- După ce ai dat prețuri: 'Dacă doriți, pot să las un coleg să vă contacteze cu o ofertă completă.'"
            . "\n- După ce ai recomandat produse: 'Vreți să vă ajutăm să finalizați comanda?'"
            . "\n- După ce ai răspuns la întrebări tehnice: 'Pot să vă pun în legătură cu un specialist pentru detalii?'"
            . "\n"
            . "\nDacă clientul răspunde DA, SIGUR, VREAU, OK, BINE, sau orice confirmare:"
            . "\nTREBUIE să colectezi OBLIGATORIU aceste date, în ordine:"
            . "\n1. NUMELE: 'Cum vă numiți, vă rog?' — Dacă nu răspunde cu un nume clar, întreabă din nou: 'Îmi puteți spune numele complet?'"
            . "\n2. TELEFONUL: 'Și un număr de telefon la care să vă contacteze colegul meu?' — Dacă nu dictează cifre, insistă politicos: 'Am nevoie de un număr de telefon ca să putem reveni.'"
            . "\n3. INTERVAL ORAR: 'Când vă este mai convenabil să vă sunăm? Dimineața, după-amiaza, sau seara?'"
            . "\n4. CONFIRMARE FINALĂ: Repetă datele: 'Deci [nume], la numărul [telefon], vă contactăm [interval]. E corect?'"
            . "\n"
            . "\n⚠️ REGULI STRICTE:"
            . "\n- NU confirma că ai notat PÂNĂ nu ai cel puțin NUMELE și TELEFONUL."
            . "\n- Dacă clientul a dat telefonul dar nu și numele, întreabă: 'Și cum vă numiți, vă rog?'"
            . "\n- Dacă clientul a dat numele dar nu și telefonul, întreabă: 'La ce număr de telefon vă pot contacta?'"
            . "\n- Dacă clientul refuză să dea datele, respectă decizia. Dar oferă alternativa email."
            . "\n- NU spune 'Am notat' sau 'Un coleg vă va contacta' până nu ai NUME + TELEFON confirmate."
            . "\n"
            . "\nNU aștepta ca clientul să ceară singur. TU trebuie să propui."
            . "\nNU cere datele în primele replici — lasă clientul să primească informații utile mai întâi."
            . "\nPropune doar DUPĂ ce ai oferit prețuri, recomandări sau informații tehnice relevante."
            . "\n=== SFÂRȘIT CAPTARE DATE ===";

        // Apply centralized guardrails (voice mode)
        $base = PromptGuardrails::apply($base, isVoice: true);

        return $base;
    }

    /**
     * Rebuild instructions with fresh knowledge context (for mid-call updates).
     */
    private function buildInstructionsWithContext(string $knowledgeContext): string
    {
        $base = $this->bot->system_prompt
            ?? 'Ești un asistent vocal prietenos. Răspunzi în limba română.';

        if ($this->hasProducts) {
            $base .= "\n\n=== INSTRUCȚIUNI PRODUSE (PRIORITARE) ==="
                . "\nEști asistentul vocal al unui magazin online cu catalog real."
                . "\nCând vezi 'Produse găsite' mai jos, acestea sunt REALE din baza de date."
                . "\nTREBUIE să le spui clientului cu NUME EXACT și PREȚ. NU refuza."
                . "\nProdusele listate sunt în stoc. Prețurile sunt corecte."
                . "\nDacă nu apar produse pentru cerere, spune că nu ai găsit și sugerează contactarea magazinului."
                . "\n=== SFÂRȘIT INSTRUCȚIUNI PRODUSE ===";
        }

        $base .= "\n\n" . $knowledgeContext;

        $language = $this->bot->language ?? 'română';

        // Add category navigation instructions for product bots
        if ($this->hasProducts) {
            $base .= "\n\n=== NAVIGARE INTELIGENTĂ PRODUSE ==="
                . "\nCând clientul întreabă de un BRAND: spune categoriile disponibile și întreabă ce îl interesează."
                . "\nCând clientul întreabă de o CATEGORIE: spune tipurile/brand-urile și întreabă ce caută mai exact."
                . "\nGhidează PAS CU PAS. Mai bine 2-3 replici scurte decât un răspuns lung."
                . "\n=== SFÂRȘIT NAVIGARE ===";
        }

        $base .= "\n\n=== MESAJE DE AȘTEPTARE ==="
            . "\nDupă un mesaj de așteptare, răspunde IMEDIAT cu informațiile primite, fără repetiții."
            . "\n=== SFÂRȘIT MESAJE AȘTEPTARE ===";

        $base .= "\n\nRăspunde natural și concis în limba {$language}. Fii politicos și profesional.";

        // V2: Lead capture vocal
        $base .= "\n\n=== CAPTARE DATE CLIENT ==="
            . "\nDupă 2-3 întrebări, PROPUNE PROACTIV: 'Doriți să vă ajutăm cu o comandă sau o ofertă?'"
            . "\nDacă clientul confirmă (DA, SIGUR, OK, orice confirmare):"
            . "\n1. Cere NUMELE: 'Cum vă numiți, vă rog?'"
            . "\n2. Cere TELEFONUL: 'Și un număr de telefon?'"
            . "\n3. Cere INTERVALUL: 'Dimineața, după-amiaza, sau seara?'"
            . "\n4. CONFIRMĂ cu datele: 'Deci [nume], la [telefon], vă sunăm [interval]. E corect?'"
            . "\n⚠️ NU confirma până nu ai NUME + TELEFON. Dacă lipsește unul, insistă politicos."
            . "\n=== SFÂRȘIT CAPTARE DATE ===";

        // Apply centralized guardrails (voice mode)
        $base = PromptGuardrails::apply($base, isVoice: true);

        return $base;
    }

    // -----------------------------------------------------------------
    //  Event dispatcher
    // -----------------------------------------------------------------

    /**
     * Process an incoming OpenAI Realtime event.
     *
     * @param  array  $event  The decoded JSON event from OpenAI.
     * @return array|null  An optional response event to send back to OpenAI.
     */
    public function handleEvent(array $event): ?array
    {
        $type = $event['type'] ?? '';

        try {
            return match (true) {
                str_starts_with($type, 'session.')              => $this->handleSessionEvent($event),
                str_starts_with($type, 'input_audio_buffer.')   => $this->handleInputAudioEvent($event),
                str_starts_with($type, 'conversation.item.')    => $this->handleConversationEvent($event),
                str_starts_with($type, 'response.')             => $this->handleResponseEvent($event),
                $type === 'error'                               => $this->handleError($event),
                default                                         => null,
            };
        } catch (\Throwable $e) {
            Log::error("RealtimeSession: unhandled exception while processing event '{$type}' for call {$this->call->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    // -----------------------------------------------------------------
    //  Event handlers
    // -----------------------------------------------------------------

    /**
     * Handle session.created and session.updated events.
     */
    private function handleSessionEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'session.created') {
            Log::info("Realtime session created for call {$this->call->id}");

            try {
                CallEvent::create([
                    'call_id'     => $this->call->id,
                    'type'        => 'realtime.session_created',
                    'metadata'    => ['session_id' => $event['session']['id'] ?? null],
                    'occurred_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning("RealtimeSession: failed to persist session_created event for call {$this->call->id}", ['error' => $e->getMessage()]);
            }

            // Store greeting for sending after session.updated
            $this->pendingGreeting = true;

            // Return session config to send to OpenAI.
            return $this->getSessionConfig();
        }

        if ($type === 'session.updated' && $this->pendingGreeting) {
            $this->pendingGreeting = false;
            Log::info("Realtime session updated for call {$this->call->id}");

            // Send greeting as first bot response
            $greeting = $this->bot->greeting_message
                ?? 'Bună ziua! Sunt asistentul virtual. Vă pot ajuta cu informații despre produse, prețuri sau comenzi. Cu ce vă pot fi de folos?';

            // Adjust greeting based on time of day
            $hour = (int) now()->format('H');
            $timeGreeting = match(true) {
                $hour >= 5 && $hour < 12 => 'Bună dimineața',
                $hour >= 12 && $hour < 18 => 'Bună ziua',
                default => 'Bună seara',
            };
            $greeting = preg_replace('/^(Bun[aă]\s+(ziua|dimineata|diminea[tț]a|seara)!?|Bun[aă]!?|Salut!?|Hello!?|Hei!?)\s*/iu', $timeGreeting . '! ', $greeting);

            return [
                'type' => 'response.create',
                'response' => [
                    'modalities' => ['text', 'audio'],
                    'instructions' => 'Spune exact urmatorul text, fara sa adaugi sau sa schimbi nimic: "' . str_replace('"', '\\"', $greeting) . '"',
                ],
            ];
        }

        if ($type === 'session.updated') {
            Log::info("Realtime session updated for call {$this->call->id}");
        }

        return null;
    }

    /**
     * Handle input_audio_buffer.* events (speech detection, commit).
     */
    private function handleInputAudioEvent(array $event): ?array
    {
        $type = $event['type'];

        try {
            if ($type === 'input_audio_buffer.speech_started') {
                CallEvent::create([
                    'call_id'     => $this->call->id,
                    'type'        => 'speech.started',
                    'occurred_at' => now(),
                ]);

                // If user interrupts during a filling message, preserve the pending context
                // so the next response still has the information that was being loaded.
                if ($this->fillingSentForCurrentResponse && $this->pendingContext) {
                    Log::debug("RealtimeSession: user interrupted filling for call {$this->call->id}, preserving pending context");
                    // Context will be injected when the interrupted response.done fires
                }

                // Predictive cache warming: pre-load brand/category data while user speaks
                // so that when transcription completes, the search is near-instant.
                if ($this->hasProducts) {
                    try {
                        $navService = app(CategoryNavigationService::class);
                        // These methods cache internally — calling them warms the cache
                        // if it's not already warm, with no wasted work if it is.
                        $navService->detectBrandQuery($this->bot->id, '');
                        $navService->detectCategoryQuery($this->bot->id, '');
                    } catch (\Throwable $e) {
                        // Silent — cache warming is best-effort
                    }
                }
            }

            if ($type === 'input_audio_buffer.committed') {
                CallEvent::create([
                    'call_id'     => $this->call->id,
                    'type'        => 'audio.committed',
                    'occurred_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("RealtimeSession: failed to persist input_audio event for call {$this->call->id}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Handle conversation.item.* events (transcriptions).
     */
    private function handleConversationEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'conversation.item.input_audio_transcription.completed') {
            $transcript = $event['transcript'] ?? '';

            // Filter Whisper hallucinations (phantom transcripts from silence/noise)
            if (WhisperHallucinationFilter::isHallucination($transcript)) {
                Log::debug("RealtimeSession: filtered Whisper hallucination for call {$this->call->id}", [
                    'transcript' => mb_substr($transcript, 0, 100),
                ]);
                return null;
            }

            if ($transcript) {
                try {
                    Transcript::create([
                        'call_id'      => $this->call->id,
                        'role'         => 'user',
                        'content'      => $transcript,
                        'timestamp_ms' => (int) (microtime(true) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("RealtimeSession: failed to persist user transcript for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                // Track timing for filling message decision
                $this->lastUserTranscriptAt = microtime(true);
                $this->fillingSentForCurrentResponse = false;
                $this->fillingCount = 0;
                $this->pendingUserTranscript = $transcript;

                // Detect intent early for smart thresholds
                $this->currentIntent = $this->fillingService->detectIntent($transcript, $this->hasProducts);
                $thresholds = $this->fillingService->getThresholds($this->currentIntent);

                // Check for follow-up from previous turn
                $callId = (string) $this->call->id;
                $followUp = $this->conversationState->detectFollowUp($callId, $transcript);

                // Refresh context based on what the user said.
                // Brand/category navigation first (fast DB lookup), then product search, then knowledge.
                try {
                    $context = '';
                    $foundProducts = false;

                    // Inject follow-up context if detected
                    if ($followUp) {
                        $context .= $this->conversationState->buildFollowUpContext($followUp);
                    }

                    // Brand/category navigation — fast DB lookup, guides user step-by-step
                    if ($this->hasProducts) {
                        try {
                            $navService = app(CategoryNavigationService::class);
                            $navContext = $navService->buildNavigationContext($this->bot->id, $transcript);
                            if ($navContext) {
                                $context .= $navContext;
                            }
                        } catch (\Throwable $e) {
                            Log::debug("RealtimeSession: category navigation failed for call {$this->call->id}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Product search — fast trigram query, no external API calls
                    if ($this->hasProducts) {
                        try {
                            $productSearch = app(ProductSearchService::class);
                            $products = $productSearch->search($this->bot->id, $transcript, 5);

                            if (!empty($products)) {
                                $foundProducts = true;
                                $context .= "\n\nProduse găsite relevant pentru cererea clientului:\n";
                                foreach ($products as $p) {
                                    $line = "- {$p->name}: {$p->price} {$p->currency}";
                                    if ($p->sale_price && $p->regular_price && $p->sale_price < $p->regular_price) {
                                        $line .= " (redus de la {$p->regular_price} {$p->currency})";
                                    }
                                    $context .= $line . "\n";
                                }
                                $context .= "\nRecomandă aceste produse clientului cu nume exact și preț. NU inventa alte produse.\n";
                            }
                        } catch (\Throwable $e) {
                            Log::warning("RealtimeSession: product search failed for call {$this->call->id}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // V2: Order lookup — check if user is asking about an order
                    try {
                        $orderLookup = app(OrderLookupService::class);
                        $orderParams = $orderLookup->detectOrderQuery($transcript);
                        if ($orderParams) {
                            $orderResult = $orderLookup->lookup($this->bot->id, $orderParams);
                            if ($orderResult['found']) {
                                $context .= "\n\n[INFORMAȚII COMANDĂ - răspunde clientului pe baza acestor date]\n";
                                foreach ($orderResult['orders'] as $o) {
                                    $context .= "Comanda #{$o['number']} | Status: {$o['status']} | Data: {$o['date']} | Total: {$o['total']}";
                                    if (!empty($o['shipping_method'])) $context .= " | Livrare: {$o['shipping_method']}";
                                    if (!empty($o['tracking'])) $context .= " | AWB: {$o['tracking']}";
                                    if (!empty($o['tracking_url'])) $context .= " | Tracking: {$o['tracking_url']}";
                                    $context .= " | Produse: " . collect($o['items'])->map(fn($i) => "{$i['name']} x{$i['quantity']}")->implode(', ');
                                    $context .= "\n";
                                }
                            } elseif (empty($orderParams['order_number']) && empty($orderParams['email']) && empty($orderParams['phone'])) {
                                $context .= "\n\n[Clientul întreabă de o comandă. Cere-i numărul comenzii sau emailul cu care a comandat.]\n";
                            } else {
                                $context .= "\n\n[{$orderResult['message']}]\n";
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::debug("RealtimeSession: order lookup failed for call {$this->call->id}", ['error' => $e->getMessage()]);
                    }

                    // Check if we should send a filling message before the slow knowledge search
                    $elapsedMs = (microtime(true) - $this->lastUserTranscriptAt) * 1000;
                    $earlyThreshold = $thresholds['early'];
                    if ($earlyThreshold !== null && $elapsedMs > $earlyThreshold && !$this->fillingSentForCurrentResponse) {
                        // Product search + order lookup already took too long
                        // Knowledge search will add more — send filling now
                        $this->fillingSentForCurrentResponse = true;
                        $this->fillingCount = 1;
                        $this->pendingContext = $context; // Save partial context

                        Log::debug("RealtimeSession: sending filling message for call {$this->call->id}", [
                            'elapsed_ms' => round($elapsedMs),
                            'intent' => $this->currentIntent,
                            'threshold' => $earlyThreshold,
                            'transcript' => mb_substr($transcript, 0, 50),
                        ]);

                        // Record turn in conversation state
                        $this->recordTurnState($callId, $transcript, $context, $foundProducts);

                        return $this->fillingService->buildFillingResponse(
                            $this->bot,
                            $transcript,
                            $callId,
                            $this->hasProducts
                        );
                    }

                    // Knowledge search — uses OpenAI embedding API (slower).
                    // If products were found, use fewer results to keep context lean.
                    $knowledgeLimit = $foundProducts ? 4 : 8;
                    $knowledgeContext = $this->knowledgeService->buildContext(
                        $this->bot->id,
                        $transcript,
                        $knowledgeLimit
                    );
                    if ($knowledgeContext) {
                        $context = $knowledgeContext . $context;
                    }

                    // Check again after knowledge search
                    $totalElapsedMs = (microtime(true) - $this->lastUserTranscriptAt) * 1000;
                    $lateThreshold = $thresholds['late'];
                    if ($lateThreshold !== null && $totalElapsedMs > $lateThreshold && !$this->fillingSentForCurrentResponse) {
                        $this->fillingSentForCurrentResponse = true;
                        $this->fillingCount = 1;

                        Log::debug("RealtimeSession: sending filling message (post-knowledge) for call {$this->call->id}", [
                            'elapsed_ms' => round($totalElapsedMs),
                            'intent' => $this->currentIntent,
                            'threshold' => $lateThreshold,
                        ]);

                        // Store context for injection after filling response completes
                        $this->pendingContext = $context;

                        // Record turn in conversation state
                        $this->recordTurnState($callId, $transcript, $context, $foundProducts);

                        return $this->fillingService->buildFillingResponse(
                            $this->bot,
                            $transcript,
                            $callId,
                            $this->hasProducts
                        );
                    }

                    // Record turn in conversation state (normal path, no filling)
                    $this->recordTurnState($callId, $transcript, $context, $foundProducts);

                    if ($context && $context !== $this->conversationContext) {
                        $this->conversationContext = $context;

                        // Inject updated context into the running session
                        return [
                            'type' => 'session.update',
                            'session' => [
                                'instructions' => $this->buildInstructionsWithContext($context),
                            ],
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning("RealtimeSession: context refresh failed for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Handle response.* events (assistant transcripts, completion).
     */
    private function handleResponseEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'response.audio_transcript.done') {
            $transcript = $event['transcript'] ?? '';

            if ($transcript) {
                try {
                    Transcript::create([
                        'call_id'      => $this->call->id,
                        'role'         => 'assistant',
                        'content'      => $transcript,
                        'timestamp_ms' => (int) (microtime(true) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("RealtimeSession: failed to persist assistant transcript for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($type === 'response.done') {
            try {
                CallEvent::create([
                    'call_id'     => $this->call->id,
                    'type'        => 'response.completed',
                    'metadata'    => [
                        'usage' => $event['response']['usage'] ?? null,
                    ],
                    'occurred_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning("RealtimeSession: failed to persist response.completed event for call {$this->call->id}", ['error' => $e->getMessage()]);
            }

            // Track token usage for cost monitoring
            $this->trackTokenUsage($event);

            // V2: Try to extract lead data from conversation transcripts
            $this->tryExtractVoiceLead();

            // V3: If a filling message just finished and we have pending context,
            // inject the context now so the next AI response has full information.
            if ($this->fillingSentForCurrentResponse && $this->pendingContext) {
                $context = $this->pendingContext;

                // Complete knowledge search if it was skipped during filling
                $knowledgeSearchStart = microtime(true);
                if ($this->pendingUserTranscript) {
                    try {
                        $knowledgeContext = $this->knowledgeService->buildContext(
                            $this->bot->id,
                            $this->pendingUserTranscript,
                            $this->hasProducts ? 4 : 8
                        );
                        if ($knowledgeContext && !str_contains($context, $knowledgeContext)) {
                            $context = $knowledgeContext . $context;
                        }
                    } catch (\Throwable $e) {
                        Log::debug("RealtimeSession: deferred knowledge search failed for call {$this->call->id}");
                    }
                }

                // Escalating filling: if knowledge search took too long and this was
                // only the first filling, send a second "mai o clipă" message
                $knowledgeElapsed = (microtime(true) - $knowledgeSearchStart) * 1000;
                $totalElapsed = $this->lastUserTranscriptAt
                    ? (microtime(true) - $this->lastUserTranscriptAt) * 1000
                    : 0;

                if ($this->fillingCount === 1 && $totalElapsed > 4000) {
                    // Still too slow — send escalation filling before the real answer
                    $this->fillingCount = 2;

                    Log::debug("RealtimeSession: sending escalation filling for call {$this->call->id}", [
                        'total_elapsed_ms' => round($totalElapsed),
                        'knowledge_ms' => round($knowledgeElapsed),
                    ]);

                    // Keep pendingContext for next response.done cycle
                    $this->pendingContext = $context;

                    return $this->fillingService->buildEscalationResponse(
                        $this->bot,
                        (string) $this->call->id
                    );
                }

                $userTranscript = $this->pendingUserTranscript;
                $this->pendingContext = null;
                $this->pendingUserTranscript = null;
                $this->fillingSentForCurrentResponse = false;
                $this->fillingCount = 0;

                if ($context && $context !== $this->conversationContext) {
                    $this->conversationContext = $context;

                    Log::debug("RealtimeSession: triggering response after filling for call {$this->call->id}");

                    // Send response.create with updated context so the AI speaks immediately.
                    // A plain session.update would NOT trigger a response — the user would
                    // hear the filling message and then silence. By using response.create
                    // with the context in instructions, the AI generates the real answer.
                    return [
                        'type' => 'response.create',
                        'response' => [
                            'modalities' => $this->ttsStrategy->getModalities(),
                            'instructions' => $this->buildInstructionsWithContext($context)
                                . "\n\n[CONTEXT ACTUALIZAT - Clientul a întrebat: \"" . str_replace('"', '\\"', $userTranscript ?? '') . "\""
                                . "\nRăspunde ACUM cu informațiile din context. NU repeta mesajul de așteptare. Răspunde direct și concis.]",
                        ],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * V2: Detect and extract lead data (name + phone) from voice transcripts.
     * Runs after each assistant response to check if contact data was exchanged.
     */
    private function tryExtractVoiceLead(): void
    {
        // Atomic lock to prevent duplicate leads from concurrent response.done events
        $lockKey = "voice_lead_extract:{$this->call->id}";
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);
        if (!$lock->get()) return; // Another extraction is in progress

        try {
            // Check if we already have a lead for this call
            $existingLead = Lead::where('tenant_id', $this->call->tenant_id)
                ->where('capture_source', 'voice')
                ->whereJsonContains('custom_fields->call_id', $this->call->id)
                ->first();
            if ($existingLead) return; // Already captured

            // Get all transcripts for this call
            $transcripts = Transcript::where('call_id', $this->call->id)
                ->orderBy('timestamp_ms')
                ->get();

            if ($transcripts->count() < 4) return; // Too early — need at least a few exchanges

            // Combine all user transcripts to search for contact data
            $userText = $transcripts->where('role', 'user')->pluck('content')->implode(' ');
            $assistantText = $transcripts->where('role', 'assistant')->pluck('content')->implode(' ');

            // Extract phone number from user speech
            $phone = null;

            // Step 1: Strip all spaces/dots/dashes from user text to find digit sequences
            // This handles "0 7 4 2 4 9 0 5 8 4" and "07 42 490 584" and "0742-490-584"
            $digitsOnly = preg_replace('/[^\d]/', '', $userText);

            // Find 10-digit Romanian phone number starting with 07
            if (preg_match('/(07\d{8})/', $digitsOnly, $m)) {
                $phone = $m[1];
            }
            // Find with country code +40 / 40
            elseif (preg_match('/(407\d{8})/', $digitsOnly, $m)) {
                $phone = '0' . substr(preg_replace('/\D/', '', $m[1]), 2);
            }

            // Step 2: Also try original text with flexible spacing (07xx xxx xxx pattern)
            if (!$phone && preg_match('/0\s*7[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d/', $userText, $m)) {
                $phone = preg_replace('/[\s.-]/', '', $m[0]);
            }

            // Extract name — look for assistant confirmation like "Perfect, Ion" or "Am notat, Popescu"
            $name = null;
            if (preg_match('/(?:perfect|am notat|mulțumesc|multumesc|bine|în regulă)[,.\s]+([A-ZĂÂÎȘȚ][a-zăâîșț]+(?:\s+[A-ZĂÂÎȘȚ][a-zăâîșț]+)?)/u', $assistantText, $m)) {
                $name = trim($m[1]);
            }
            // Also check user saying "Mă numesc X" or "Sunt X" or "Numele meu e X"
            if (!$name && preg_match('/(?:mă numesc|sunt|numele meu e|ma numesc)\s+([A-ZĂÂÎȘȚ][a-zăâîșț]+(?:\s+[A-ZĂÂÎȘȚ][a-zăâîșț]+)?)/ui', $userText, $m)) {
                $name = trim($m[1]);
            }

            // Only create lead if we have at least phone OR name
            if (!$phone && !$name) return;

            // Check for buying intent — includes direct intent AND confirmation after bot asked
            $buyingSignals = preg_match('/\b(comand|cumpăr|cumpar|vreau|doresc|interesat|intereseaz|ofert[aă]|livrare|livr[aă]m)\b/ui', $userText);

            // Also check if bot asked and user confirmed (da, sigur, ok, bine, etc.)
            $botAskedForHelp = preg_match('/\b(doriți|vreți|ajut[aă]m|comandă|ofertă)\b/ui', $assistantText);
            $userConfirmed = preg_match('/\b(da|sigur|vreau|ok|bine|desigur|fire[sș]te|normal|da vreau|da va rog)\b/ui', $userText);

            if (!$buyingSignals && !($botAskedForHelp && $userConfirmed) && !$phone) return;

            // Extract products discussed — match product names from assistant transcripts against DB
            $productsOfInterest = [];
            try {
                $productSearch = app(ProductSearchService::class);
                // Search for products mentioned in conversation using key phrases from user
                $userPhrases = array_filter(
                    preg_split('/[.!?,]+/', $userText),
                    fn($p) => mb_strlen(trim($p)) > 5
                );
                $seenIds = [];
                foreach (array_slice($userPhrases, 0, 5) as $phrase) {
                    $found = $productSearch->search($this->bot->id, trim($phrase), 2);
                    foreach ($found as $p) {
                        if (isset($seenIds[$p->id])) continue;
                        $seenIds[$p->id] = true;
                        $productsOfInterest[] = [
                            'id' => $p->wc_product_id ?? $p->id,
                            'name' => $p->name,
                            'price' => $p->price,
                            'currency' => $p->currency ?? 'RON',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Silent — products are optional on lead
            }

            $lead = Lead::create([
                'tenant_id' => $this->call->tenant_id,
                'bot_id' => $this->call->bot_id,
                'session_id' => $this->call->metadata['session_id'] ?? null,
                'name' => $name,
                'phone' => $phone,
                'status' => $phone ? 'qualified' : 'partial',
                'qualification_score' => ($phone ? 40 : 0) + ($name ? 20 : 0) + ($buyingSignals ? 20 : 0),
                'capture_source' => 'voice',
                'capture_reason' => 'voice_buying_intent',
                'products_shown' => !empty($productsOfInterest) ? $productsOfInterest : null,
                'custom_fields' => [
                    'call_id' => $this->call->id,
                    'call_duration' => $this->call->duration_seconds,
                ],
            ]);

            Log::info("RealtimeSession: voice lead captured for call {$this->call->id}", [
                'lead_id' => $lead->id,
                'name' => $name,
                'phone' => $phone ? '***' . substr($phone, -4) : null,
            ]);

            // Track event
            app(ConversationEventService::class)->track(
                EventTaxonomy::LEAD_COMPLETED,
                ['lead_id' => $lead->id, 'source' => 'voice', 'has_phone' => (bool) $phone, 'has_name' => (bool) $name],
                [
                    'tenant_id' => $this->call->tenant_id,
                    'bot_id' => $this->call->bot_id,
                    'event_source' => EventTaxonomy::SOURCE_VOICE,
                    'idempotency_key' => "voice_lead:{$this->call->id}",
                ]
            );
        } catch (\Throwable $e) {
            Log::debug("RealtimeSession: voice lead extraction failed for call {$this->call->id}", [
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
    }

    /**
     * Handle error events from the OpenAI Realtime API.
     */
    private function handleError(array $event): ?array
    {
        $error = $event['error'] ?? [];
        Log::error("Realtime error for call {$this->call->id}", $error);

        try {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'realtime.error',
                'metadata'    => $error,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("RealtimeSession: failed to persist error event for call {$this->call->id}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    // -----------------------------------------------------------------
    //  Conversation state helpers
    // -----------------------------------------------------------------

    /**
     * Record the current turn's data in ConversationStateService.
     */
    private function recordTurnState(string $callId, string $transcript, string $context, bool $foundProducts): void
    {
        try {
            $turnData = [
                'intent' => $this->currentIntent,
                'transcript' => $transcript,
            ];

            // Extract product info for follow-up resolution
            if ($foundProducts && preg_match_all('/- (.+?):\s+([\d.,]+)\s+(\w+)/u', $context, $matches, PREG_SET_ORDER)) {
                $turnData['products'] = array_map(fn($m) => [
                    'name' => $m[1],
                    'price' => $m[2],
                    'currency' => $m[3],
                ], array_slice($matches, 0, 10));
            }

            // Extract category from navigation context
            if (preg_match('/Clientul întreabă despre categoria:\s*(.+)/u', $context, $m)) {
                $turnData['category'] = trim($m[1]);
            }

            // Extract brand from navigation context
            if (preg_match('/Clientul întreabă despre brandul:\s*(.+)/u', $context, $m)) {
                $turnData['brand'] = trim($m[1]);
            }

            $this->conversationState->recordUserTurn($callId, $turnData);
        } catch (\Throwable $e) {
            Log::debug("RealtimeSession: failed to record turn state for call {$callId}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------
    //  Token usage tracking
    // -----------------------------------------------------------------

    /**
     * Persist token usage from response.done to AiApiMetric for cost monitoring.
     */
    private function trackTokenUsage(array $event): void
    {
        try {
            $usage = $event['response']['usage'] ?? null;
            if (!$usage) return;

            $inputTokens = (int) ($usage['input_tokens'] ?? $usage['total_tokens'] ?? 0);
            $outputTokens = (int) ($usage['output_tokens'] ?? 0);

            if ($inputTokens === 0 && $outputTokens === 0) return;

            // OpenAI Realtime pricing: ~$0.06/1K input, ~$0.24/1K output (audio tokens)
            $inputCost = $inputTokens * 0.06 / 1000;
            $outputCost = $outputTokens * 0.24 / 1000;
            $costCents = (int) round(($inputCost + $outputCost) * 100);

            \App\Models\AiApiMetric::create([
                'provider' => 'openai',
                'model' => 'gpt-4o-realtime',
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_cents' => $costCents,
                'response_time_ms' => 0,
                'status' => 'success',
                'bot_id' => $this->bot->id,
                'tenant_id' => $this->bot->tenant_id,
            ]);
        } catch (\Throwable $e) {
            Log::debug("RealtimeSession: failed to track token usage for call {$this->call->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------
    //  Session lifecycle
    // -----------------------------------------------------------------

    /**
     * End the session: mark the call as completed and log the event.
     */
    public function endSession(): void
    {
        try {
            $endedAt = now();
            $durationSeconds = $this->call->started_at
                ? (int) $endedAt->diffInSeconds($this->call->started_at)
                : 0;

            $this->call->update([
                'status'           => 'completed',
                'ended_at'         => $endedAt,
                'duration_seconds' => $durationSeconds,
            ]);

            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'session.ended',
                'occurred_at' => now(),
            ]);

            if (!$this->call->sentiment_label) {
                AnalyzeCallSentiment::dispatch($this->call->id)->delay(now()->addSeconds(15));
            }

            // Clean up per-call state
            $callId = (string) $this->call->id;
            $this->fillingService->resetCall($callId);
            $this->conversationState->resetCall($callId);
        } catch (\Throwable $e) {
            Log::error("RealtimeSession: failed to end session for call {$this->call->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
