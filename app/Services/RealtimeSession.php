<?php

namespace App\Services;

use App\Contracts\TtsOutputStrategy;
use App\Jobs\AnalyzeCallSentiment;
use App\Models\Bot;
use App\Models\Call;
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
                $this->hasProducts ? 5 : 5
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
        }

        return null;
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
