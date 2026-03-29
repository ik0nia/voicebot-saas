<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeCallSentiment;
use App\Jobs\GenerateCallSummary;
use App\Jobs\SendCallEndedWebhook;
use App\Models\Bot;
use App\Models\Call;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\Transcript;
use App\Services\ElevenLabsService;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealtimeSessionController extends Controller
{
    /**
     * Create an ephemeral OpenAI Realtime session token + Call record.
     */
    public function create(Request $request, Bot $bot): JsonResponse
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);

        // Check voice minute limits
        $tenant = Tenant::find($bot->tenant_id);
        if ($tenant) {
            $limitCheck = app(PlanLimitService::class)->canStartVoiceCall($tenant);
            if (!$limitCheck->allowed) {
                return response()->json([
                    'error' => $limitCheck->message,
                    'limit_reached' => true,
                ], 429);
            }
        }

        $apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));

        if (empty($apiKey) || str_starts_with($apiKey, 'sk-your')) {
            return response()->json(['error' => 'OpenAI API key not configured'], 500);
        }

        // Configurable voice mapping from config/voicebot.php or DB
        $defaultVoiceMap = [
            'masculin' => 'ash',
            'feminin' => 'coral',
            'nova' => 'coral',
            'alloy' => 'alloy',
            'echo' => 'echo',
            'fable' => 'sage',
            'onyx' => 'ash',
            'shimmer' => 'shimmer',
            'ash' => 'ash',
            'ballad' => 'ballad',
            'coral' => 'coral',
            'sage' => 'sage',
            'verse' => 'verse',
            'marin' => 'marin',
            'cedar' => 'cedar',
        ];
        $voiceMap = config('voicebot.voice_map', $defaultVoiceMap);
        $voice = $voiceMap[$bot->voice ?? 'feminin'] ?? 'coral';

        // Check for cloned voice
        $useClonedVoice = false;
        $elevenLabsVoiceId = null;
        $bot->load('clonedVoice');
        if ($bot->usesClonedVoice()) {
            $useClonedVoice = true;
            $elevenLabsVoiceId = $bot->clonedVoice->elevenlabs_voice_id;
        }

        // Build system instructions with knowledge context
        $botPrompt = $bot->system_prompt ?? 'Ești un asistent AI util.';

        // Inject knowledge base context + lightweight product info (NOT full catalog)
        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();
        try {
            if ($hasProducts) {
                $totalProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->count();

                // Get category summary (names only, no products)
                $categories = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)
                    ->whereNotNull('categories')
                    ->pluck('categories')
                    ->flatten()
                    ->unique()
                    ->sort()
                    ->values()
                    ->implode(', ');

                // Only top 10 popular products for initial context (not full catalog)
                // The search_products tool handles specific queries dynamically
                $topProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)
                    ->whereIn('stock_status', ['instock', 'onbackorder'])
                    ->orderByDesc('sales_count')
                    ->take(10)
                    ->get(['name', 'price', 'currency']);

                $productList = '';
                foreach ($topProducts as $p) {
                    $productList .= "  - {$p->name}: {$p->price} {$p->currency}\n";
                }

                $botPrompt .= "\n\n=== INSTRUCȚIUNI PRODUSE ==="
                    . "\nEști asistentul vocal al unui magazin cu {$totalProducts} produse."
                    . "\nCategorii disponibile: {$categories}."
                    . "\n\nCELE MAI POPULARE PRODUSE:\n{$productList}"
                    . "\nREGULI:"
                    . "\n- Când clientul întreabă de un produs specific, folosește tool-ul search_products pentru a căuta în catalog."
                    . "\n- Produsele returnate de search_products sunt REALE cu prețuri CORECTE. Spune-le clientului."
                    . "\n- Dacă produsul nu e găsit, sugerează să contacteze magazinul sau să verifice pe site."
                    . "\n- Transcrierea vocii poate conține erori (ex: 'CRSID' = 'Ceresit'). Interpretează sensul."
                    . "\n=== SFÂRȘIT INSTRUCȚIUNI PRODUSE ==="
                    . "\n\nCOMENZI:"
                    . "\n- Dacă clientul întreabă de o comandă, cere-i numărul comenzii și emailul."
                    . "\n- Notează datele și spune: 'Am notat. Un coleg vă va contacta cu detalii.'";
            }

            // General knowledge context — focused query for initial session
            $searchService = app(\App\Services\KnowledgeSearchService::class);
            $query = $hasProducts
                ? 'informații magazin, livrare, plată, contact, program'
                : 'informații generale despre companie și servicii';
            $knowledgeContext = $searchService->buildContext($bot->id, $query, 3, 3000);

            if ($knowledgeContext) {
                $botPrompt .= "\n\n" . $knowledgeContext;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to load knowledge for voice bot', ['bot_id' => $bot->id, 'error' => $e->getMessage()]);
        }

        // Apply centralized guardrails (voice mode)
        $botPrompt = \App\Services\PromptGuardrails::apply($botPrompt, isVoice: true);

        // Add voice-specific instructions
        $instructions = $botPrompt . "\n\n" .
            "REGULI PENTRU CONVERSAȚIE VOCALĂ:\n" .
            "- Aceasta este o conversație telefonică. Vorbește NATURAL ca un vânzător experimentat într-un magazin.\n" .
            "- Vorbește EXCLUSIV în limba română, cu accent și formulări naturale românești.\n" .
            "- Folosește expresii românești autentice: 'sigur că da', 'cu drag', 'desigur', 'fără problemă', 'bineînțeles'.\n" .
            "- Răspunde SCURT — maxim 1-2 propoziții. Propoziții simple și directe.\n" .
            "- Când spui prețuri, spune clar: 'costă douăzeci și cinci de lei' nu '25 RON'.\n" .
            "- Ton PROFESIONAL dar cald. Vorbești ca un coleg de încredere.\n" .
            "- INTERZIS: 'hmm', 'păi', 'deci', 'aș putea', 'cred că', 'probabil', 'oarecum'. Doar afirmații clare.\n" .
            "- Termină fiecare răspuns cu o întrebare scurtă și directă.\n" .
            "- Transcrierea vocii poate conține erori. Interpretează SENSUL, nu cuvintele exacte.\n" .
            "- Mesaje false gen 'subscribe', 'mulțumim pentru vizionare' sunt ERORI DE TRANSCRIERE. Ignoră-le.\n" .
            "- Dacă mesajul nu are sens, ignoră-l. NU încheia conversația decât dacă clientul cere explicit.";

        $model = PlatformSetting::get('openai_realtime_model', 'gpt-4o-realtime-preview');

        try {
            // Request ephemeral token from OpenAI
            $sessionPayload = [
                'model' => $model,
                'voice' => $voice,
                'modalities' => ['text', 'audio'],
                'instructions' => $instructions,
                'input_audio_transcription' => [
                    'model' => 'whisper-1',
                    'language' => 'ro',
                ],
                'turn_detection' => [
                    'type' => 'server_vad',
                    'threshold' => 0.5,
                    'prefix_padding_ms' => 200,
                    'silence_duration_ms' => 500,
                ],
            ];

            // Add product search tool if bot has products
            if ($hasProducts) {
                $sessionPayload['tools'] = [
                    [
                        'type' => 'function',
                        'name' => 'search_products',
                        'description' => 'Caută produse în catalogul magazinului după nume, categorie sau cod produs. Folosește ÎNTOTDEAUNA acest tool când clientul întreabă de un produs, preț, stoc sau disponibilitate. Returnează lista de produse cu nume, preț și stoc.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'query' => [
                                    'type' => 'string',
                                    'description' => 'Termenul de căutare: nume produs, categorie, brand sau cod (ex: "glet", "parchet VOX", "CM11", "polistiren 5cm")',
                                ],
                            ],
                            'required' => ['query'],
                        ],
                    ],
                ];
                $sessionPayload['tool_choice'] = 'auto';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://api.openai.com/v1/realtime/sessions', $sessionPayload);

            if (!$response->successful()) {
                Log::error('OpenAI Realtime session error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['error' => 'Failed to create realtime session'], 502);
            }

            $sessionData = $response->json();

            // Create a Call record for logging
            $call = Call::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'caller_number' => 'web-test',
                'direction' => 'inbound',
                'status' => Call::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'metadata' => [
                    'source' => $request->has('demo') ? 'demo' : 'test-vocal',
                    'session_id' => $sessionData['id'] ?? null,
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            $bot->incrementCallsCount();

            // Dynamic greeting based on time of day
            $timezone = $bot->settings['timezone'] ?? 'Europe/Bucharest';
            $hour = (int) now($timezone)->format('H');
            $timeGreeting = match(true) {
                $hour >= 5 && $hour < 12 => 'Bună dimineața',
                $hour >= 12 && $hour < 18 => 'Bună ziua',
                default => 'Bună seara',
            };

            $greetingMessage = $bot->greeting_message;
            if ($greetingMessage) {
                $greetingMessage = preg_replace('/^(Bun[aă]!?|Salut!?|Hello!?|Hei!?)\s*/iu', $timeGreeting . '! ', $greetingMessage);
            }

            $maxDuration = (int) ($bot->settings['max_call_duration'] ?? 1800);

            $responseData = [
                'token' => $sessionData['client_secret']['value'] ?? null,
                'call_id' => $call->id,
                'bot_id' => $bot->id,
                'voice' => $voice,
                'bot_name' => $bot->name,
                'use_cloned_voice' => $useClonedVoice,
                'greeting_message' => $greetingMessage,
                'has_products' => $hasProducts,
                'max_duration_seconds' => $maxDuration,
                'warning_at_seconds' => max(0, $maxDuration - 300),
            ];

            if ($useClonedVoice) {
                $responseData['elevenlabs_voice_id'] = $elevenLabsVoiceId;
                // SECURITY: API key is NOT sent to frontend. TTS is handled server-side
                // via the /api/v1/bots/{bot}/synthesize endpoint.
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Realtime session creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    /**
     * Save a transcript line (called from frontend during/after call).
     */
    public function saveTranscript(Request $request, Call $call): JsonResponse
    {
        $call = Call::withoutGlobalScopes()->findOrFail($call->id);

        $request->validate([
            'role' => 'required|in:user,assistant',
            'content' => 'required|string|max:5000',
            'timestamp_ms' => 'nullable|integer',
        ]);

        Transcript::create([
            'call_id' => $call->id,
            'role' => $request->input('role'),
            'content' => $request->input('content'),
            'timestamp_ms' => $request->input('timestamp_ms', 0),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Synthesize text to speech using the bot's cloned voice.
     */
    public function synthesize(Request $request, Bot $bot): \Illuminate\Http\Response|JsonResponse
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);
        $bot->load('clonedVoice');

        if (!$bot->usesClonedVoice()) {
            return response()->json(['error' => 'No cloned voice active'], 400);
        }

        $text = $request->input('text', '');
        if (empty($text)) {
            return response()->json(['error' => 'No text provided'], 400);
        }

        $elevenLabs = app(ElevenLabsService::class);
        $audioBase64 = $elevenLabs->synthesize(
            $bot->clonedVoice->elevenlabs_voice_id,
            $text,
            'mp3_44100_128' // MP3 for browser playback
        );

        if (!$audioBase64) {
            return response()->json(['error' => 'TTS synthesis failed'], 500);
        }

        return response(base64_decode($audioBase64))
            ->header('Content-Type', 'audio/mpeg')
            ->header('Cache-Control', 'no-cache');
    }

    /**
     * Search products for voice bot function calling.
     * Called by frontend when OpenAI Realtime triggers the search_products tool.
     */
    public function searchProducts(Request $request, Bot $bot): JsonResponse
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);

        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();
        if (!$hasProducts) {
            return response()->json(['products' => [], 'message' => 'Acest bot nu are catalog de produse.']);
        }

        $productSearch = app(\App\Services\ProductSearchService::class);
        $results = $productSearch->search($bot->id, $request->input('query'), 5);

        $products = array_map(function ($r) {
            return [
                'name' => $r->name,
                'price' => $r->price,
                'currency' => $r->currency ?? 'RON',
                'stock_status' => $r->stock_status === 'instock' ? 'În stoc' : 'Pe comandă',
                'short_description' => mb_substr($r->short_description ?? '', 0, 150),
            ];
        }, $results);

        $message = empty($products)
            ? 'Nu am găsit produse pentru această căutare. Sugerează clientului să reformuleze sau să contacteze magazinul.'
            : count($products) . ' produse găsite. Spune clientului numele și prețul fiecăruia.';

        return response()->json(['products' => $products, 'message' => $message]);
    }

    /**
     * End a call session (update status + duration).
     */
    public function endCall(Request $request, Call $call): JsonResponse
    {
        $call = Call::withoutGlobalScopes()->findOrFail($call->id);

        if ($call->status === Call::STATUS_IN_PROGRESS) {
            // Prefer frontend-reported duration (accurate timer), fallback to server calculation
            $frontendDuration = abs((int) $request->input('duration', $request->query('duration', 0)));
            $serverDuration = $call->started_at
                ? abs((int) now()->diffInSeconds($call->started_at))
                : 0;

            $duration = $frontendDuration > 0 ? $frontendDuration : $serverDuration;

            // Per-second cost calculation based on real OpenAI billing data:
            // Real measured: $13.45 for 94.3 minutes = $0.14/min OpenAI
            // ElevenLabs: $5.52 for 43.2 min cloned = $0.128/min ElevenLabs
            //
            // Native voice (OpenAI only):  ~$0.14/min
            // Cloned voice (OpenAI + EL):  ~$0.14 + $0.13 = ~$0.27/min
            $openaiCostPerMinute = config('voicebot.cost.openai_realtime_per_minute', 0.14);
            $elevenlabsCostPerMinute = config('voicebot.cost.elevenlabs_per_minute', 0.13);

            $call->load('bot.clonedVoice');
            $costPerMinuteDollars = $openaiCostPerMinute;
            if ($call->bot && $call->bot->usesClonedVoice()) {
                $costPerMinuteDollars += $elevenlabsCostPerMinute;
            }

            // Convert: $/min → cents/sec
            $costCents = max(1, (int) round($duration * $costPerMinuteDollars * 100 / 60));

            $call->update([
                'status' => Call::STATUS_COMPLETED,
                'ended_at' => now(),
                'duration_seconds' => $duration,
                'cost_cents' => $costCents,
            ]);

            // Record voice minutes usage
            $callTenant = Tenant::find($call->tenant_id);
            if ($callTenant && $duration > 0) {
                $minutesUsed = (int) ceil($duration / 60); // round up to nearest minute
                app(PlanLimitService::class)->recordVoiceMinutes($callTenant, $minutesUsed);
            }

            AnalyzeCallSentiment::dispatch($call->id)->delay(now()->addSeconds(15));

            // Generate call summary via GPT-4o-mini
            GenerateCallSummary::dispatch($call->id)->delay(now()->addSeconds(20));

            // Notify tenant webhook if configured
            $tenant = $call->bot?->tenant;
            if ($tenant && !empty($tenant->webhook_url)) {
                SendCallEndedWebhook::dispatch($call->id, $tenant->webhook_url, $tenant->webhook_secret)
                    ->delay(now()->addSeconds(25));
            }
        }

        return response()->json(['ok' => true, 'duration' => $call->duration_seconds]);
    }
}
