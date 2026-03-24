<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));
        $this->model = 'gpt-4o';
    }

    /**
     * Send a chat message and get AI response.
     */
    public function chat(Bot $bot, array $conversationHistory, string $userMessage, string $extraContext = ''): string
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'sk-your')) {
            return $this->mockResponse($bot, $userMessage);
        }

        try {
            $messages = $this->buildMessages($bot, $conversationHistory, $userMessage, $extraContext);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 500,
                'temperature' => (float) PlatformSetting::get('openai_temperature', 0.7),
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content', 'Nu am putut genera un răspuns.');
            }

            Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
            return $this->mockResponse($bot, $userMessage);
        } catch (\Exception $e) {
            Log::error('OpenAI service error', ['error' => $e->getMessage()]);
            return $this->mockResponse($bot, $userMessage);
        }
    }

    /**
     * Generate speech audio from text using OpenAI TTS.
     */
    public function textToSpeech(string $text, string $voice = 'nova'): ?string
    {
        if (empty($this->apiKey) || str_starts_with($this->apiKey, 'sk-your')) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/audio/speech', [
                'model' => 'tts-1',
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]);

            if ($response->successful()) {
                return base64_encode($response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI TTS error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildMessages(Bot $bot, array $conversationHistory, string $userMessage, string $extraContext = ''): array
    {
        $messages = [];

        $systemPrompt = $bot->system_prompt ?? 'Ești un asistent AI util. Răspunde în limba română.';

        // Use vector search for relevant knowledge (not brut dump)
        $hasProducts = \App\Models\WooCommerceProduct::where('bot_id', $bot->id)->exists();
        try {
            $searchService = app(KnowledgeSearchService::class);
            $knowledgeContext = $searchService->buildContext($bot->id, $userMessage, $hasProducts ? 8 : 5);

            if ($hasProducts) {
                $systemPrompt .= "\n\nEști asistentul vocal al unui magazin online."
                    . "\n- Recomandă produse cu NUMELE EXACT și PREȚUL din context."
                    . "\n- Fii scurt și natural, ca într-o conversație telefonică."
                    . "\n- NU inventa produse sau prețuri."
                    . "\n- Când spui prețuri, spune clar: 'costă douăzeci și cinci de lei'.";
            }

            if ($knowledgeContext) {
                $systemPrompt .= "\n\n" . $knowledgeContext;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Voice knowledge search failed', ['error' => $e->getMessage()]);
        }

        if ($extraContext) {
            $systemPrompt .= $extraContext;
        }

        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // Conversation history (last 20 messages)
        foreach (array_slice($conversationHistory, -20) as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? ($msg['direction'] === 'inbound' ? 'user' : 'assistant'),
                'content' => $msg['content'] ?? $msg['text'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function mockResponse(Bot $bot, string $userMessage): string
    {
        $botName = $bot->name ?? 'Sambla';
        $lower = mb_strtolower($userMessage);

        if (str_contains($lower, 'salut') || str_contains($lower, 'bună') || str_contains($lower, 'hello')) {
            return "Bună! Sunt {$botName}, asistentul tău AI. Cu ce te pot ajuta astăzi?";
        }
        if (str_contains($lower, 'preț') || str_contains($lower, 'cost') || str_contains($lower, 'cât')) {
            return "Planurile noastre încep de la 99€/lună. Vrei să îți spun mai multe detalii despre ce include fiecare plan?";
        }
        if (str_contains($lower, 'program') || str_contains($lower, 'orar')) {
            return "Sunt disponibil 24/7! Poți vorbi cu mine oricând, zi sau noapte.";
        }
        if (str_contains($lower, 'mulțumesc') || str_contains($lower, 'mersi')) {
            return "Cu plăcere! Dacă mai ai întrebări, sunt aici. O zi frumoasă!";
        }

        return "Mulțumesc pentru mesaj. Sunt {$botName} și sunt aici să te ajut. Poți să-mi spui mai multe despre ce ai nevoie?";
    }
}
