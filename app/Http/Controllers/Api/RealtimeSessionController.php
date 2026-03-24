<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeCallSentiment;
use App\Models\Bot;
use App\Models\Call;
use App\Models\PlatformSetting;
use App\Models\Transcript;
use App\Services\ElevenLabsService;
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

        $apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));

        if (empty($apiKey) || str_starts_with($apiKey, 'sk-your')) {
            return response()->json(['error' => 'OpenAI API key not configured'], 500);
        }

        // Build voice mapping (OpenAI Realtime valid: alloy, ash, ballad, coral, echo, sage, shimmer, verse, marin, cedar)
        $voiceMap = [
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

        // Inject knowledge base context + product catalog
        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();
        try {
            if ($hasProducts) {
                $totalProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->count();

                // Get ALL categories
                $categories = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)
                    ->whereNotNull('categories')
                    ->pluck('categories')
                    ->flatten()
                    ->unique()
                    ->sort()
                    ->values()
                    ->implode(', ');

                // Get products from ALL categories (5 per category, up to 25 categories)
                $majorCategories = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)
                    ->whereNotNull('categories')
                    ->pluck('categories')
                    ->flatten()
                    ->countBy()
                    ->sortDesc()
                    ->take(25)
                    ->keys();

                $catalog = '';
                foreach ($majorCategories as $cat) {
                    $products = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)
                        ->whereJsonContains('categories', $cat)
                        ->whereIn('stock_status', ['instock', 'onbackorder'])
                        ->orderBy('price', 'desc')
                        ->take(5)
                        ->get(['name', 'price', 'currency', 'sku']);
                    if ($products->isEmpty()) continue;
                    $catalog .= "\n[{$cat}]\n";
                    foreach ($products as $p) {
                        $catalog .= "  - {$p->name}: {$p->price} {$p->currency}" . ($p->sku ? " (cod: {$p->sku})" : '') . "\n";
                    }
                }

                $botPrompt .= "\n\n=== INSTRUCȚIUNI PRODUSE (OBLIGATORIU) ==="
                    . "\nEști asistentul vocal al unui magazin de materiale de construcții și amenajări."
                    . "\nMagazinul are {$totalProducts} produse. Categorii: {$categories}."
                    . "\n\nCATALOG PRODUSE CU PREȚURI REALE:{$catalog}"
                    . "\n\nREGULI STRICTE:"
                    . "\n- Prețurile din catalogul de mai sus sunt REALE și CORECTE. TREBUIE să le spui clientului."
                    . "\n- Când clientul întreabă de un produs, CAUTĂ în catalogul de mai sus și dă-i NUMELE EXACT și PREȚUL."
                    . "\n- Exemplu BUN: 'Da, avem Glet Fino Bello la cincizeci și doi de lei sacul de douăzeci și doi de kilograme.'"
                    . "\n- Exemplu RĂU: 'Nu am informații despre acest produs' (INTERZIS dacă produsul e în catalog)"
                    . "\n- Dacă produsul NU este în catalog, spune: 'Nu am acest produs în lista mea, dar s-ar putea să îl avem. Vă recomand să verificați pe site-ul nostru sau să sunați la magazin.'"
                    . "\n- NU spune NICIODATĂ 'nu am acces', 'nu pot verifica', 'nu am informații'. Ai catalogul complet mai sus."
                    . "\n- Transcrierea vocii poate conține erori (ex: 'CRSID' = 'Ceresit', 'baumeito' = 'Baumit'). Interpretează sensul."
                    . "\n- Dacă clientul cere un BRAND (ex: VOX, Austrotherm, Baumit), caută în catalog produse de la acel brand."
                    . "\n=== SFÂRȘIT INSTRUCȚIUNI PRODUSE ==="
                    . "\n\nCOMENZI:"
                    . "\n- Dacă clientul întreabă de o comandă, cere-i numărul comenzii și emailul cu care a comandat."
                    . "\n- Notează datele și spune-i: 'Am notat. Un coleg vă va contacta în cel mai scurt timp cu detalii despre comandă.'"
                    . "\n- NU spune că 'nu poți verifica'. Spune că notezi și rezolvați rapid.";
            }

            // General knowledge context
            $searchService = app(\App\Services\KnowledgeSearchService::class);
            $query = $hasProducts
                ? 'informații magazin, livrare, plată, contact'
                : 'informații generale despre companie și servicii';
            $knowledgeContext = $searchService->buildContext($bot->id, $query, 5);

            if ($knowledgeContext) {
                $botPrompt .= "\n\n" . $knowledgeContext;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to load knowledge for voice bot', ['bot_id' => $bot->id, 'error' => $e->getMessage()]);
        }

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

            $responseData = [
                'token' => $sessionData['client_secret']['value'] ?? null,
                'call_id' => $call->id,
                'bot_id' => $bot->id,
                'voice' => $voice,
                'bot_name' => $bot->name,
                'use_cloned_voice' => $useClonedVoice,
                'greeting_message' => $bot->greeting_message,
                'has_products' => $hasProducts,
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

            // Cost estimate per minute:
            // Without cloned voice: OpenAI audio in ($0.06) + audio out ($0.24) = ~$0.20/min avg
            // With cloned voice: OpenAI audio in ($0.06) + text out ($0.01) + ElevenLabs ($0.20) = ~$0.27/min avg
            $costPerMinuteCents = 20;
            $call->load('bot.clonedVoice');
            if ($call->bot && $call->bot->usesClonedVoice()) {
                $costPerMinuteCents = 27; // No OpenAI audio output + ElevenLabs TTS
            }
            $costCents = max(1, (int) ceil($duration / 60) * $costPerMinuteCents);

            $call->update([
                'status' => Call::STATUS_COMPLETED,
                'ended_at' => now(),
                'duration_seconds' => $duration,
                'cost_cents' => $costCents,
            ]);

            AnalyzeCallSentiment::dispatch($call->id)->delay(now()->addSeconds(15));
        }

        return response()->json(['ok' => true, 'duration' => $call->duration_seconds]);
    }
}
