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
            'vad_threshold' => $settings['vad_threshold'] ?? 0.5,
            'silence_duration_ms' => $settings['silence_duration_ms'] ?? 500,
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
                3, // Keep init context lean; mid-call updates fetch more
                3000 // Max 3000 chars for initial voice prompt
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

        $base .= "\n\nReguli importante:";
        $base .= "\n- Răspunde natural și concis în limba {$language}.";
        $base .= "\n- Dacă nu știi răspunsul, oferă-te să transferi apelul la un operator uman.";
        $base .= "\n- Fii politicos și profesional în toate interacțiunile.";

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
            . "\nDupă ce ai răspuns la 2-3 întrebări ale clientului despre produse sau servicii, TREBUIE să îl întrebi PROACTIV:"
            . "\n'Doriți să vă ajutăm cu o comandă sau o ofertă personalizată?'"
            . "\nSau: 'Vreți să vă pun în legătură cu un coleg care să vă ajute mai departe?'"
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
            . "\nNU cere datele în primele 2 replici — lasă-l mai întâi să pună întrebări."
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

            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'realtime.session_created',
                'metadata'    => ['session_id' => $event['session']['id'] ?? null],
                'occurred_at' => now(),
            ]);

            // Return session config to send to OpenAI.
            return $this->getSessionConfig();
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

        if ($type === 'input_audio_buffer.speech_started') {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'speech.started',
                'occurred_at' => now(),
            ]);
        }

        if ($type === 'input_audio_buffer.committed') {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'audio.committed',
                'occurred_at' => now(),
            ]);
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

                // Refresh context based on what the user said.
                // Product search first (fast, DB-only), then knowledge search if needed.
                try {
                    $context = '';
                    $foundProducts = false;

                    // Product search FIRST — fast trigram query, no external API calls
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

                    // Knowledge search — uses OpenAI embedding API (slower).
                    // If products were found, use fewer results to keep context lean.
                    $knowledgeLimit = $foundProducts ? 3 : 5;
                    $knowledgeContext = $this->knowledgeService->buildContext(
                        $this->bot->id,
                        $transcript,
                        $knowledgeLimit
                    );
                    if ($knowledgeContext) {
                        $context = $knowledgeContext . $context;
                    }

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
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'response.completed',
                'metadata'    => [
                    'usage' => $event['response']['usage'] ?? null,
                ],
                'occurred_at' => now(),
            ]);

            // V2: Try to extract lead data from conversation transcripts
            $this->tryExtractVoiceLead();
        }

        return null;
    }

    /**
     * V2: Detect and extract lead data (name + phone) from voice transcripts.
     * Runs after each assistant response to check if contact data was exchanged.
     */
    private function tryExtractVoiceLead(): void
    {
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
            elseif (preg_match('/(40\s?7\d{8})/', $digitsOnly, $m)) {
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

            // Get products discussed
            $productsShown = [];
            foreach ($transcripts->where('role', 'assistant') as $t) {
                if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:lei|RON)/i', $t->content, $priceMatches)) {
                    // Has prices mentioned — products were discussed
                }
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
        }
    }

    /**
     * Handle error events from the OpenAI Realtime API.
     */
    private function handleError(array $event): ?array
    {
        $error = $event['error'] ?? [];
        Log::error("Realtime error for call {$this->call->id}", $error);

        CallEvent::create([
            'call_id'     => $this->call->id,
            'type'        => 'realtime.error',
            'metadata'    => $error,
            'occurred_at' => now(),
        ]);

        return null;
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
            $this->call->update([
                'status'   => 'completed',
                'ended_at' => now(),
            ]);

            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'session.ended',
                'occurred_at' => now(),
            ]);

            if (!$this->call->sentiment_label) {
                AnalyzeCallSentiment::dispatch($this->call->id)->delay(now()->addSeconds(15));
            }
        } catch (\Throwable $e) {
            Log::error("RealtimeSession: failed to end session for call {$this->call->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
