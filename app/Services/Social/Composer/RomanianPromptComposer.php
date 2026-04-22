<?php

namespace App\Services\Social\Composer;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Polishes Romanian copy for image prompts via OpenAI GPT-5.4.
 * Given structured inputs (niche, pattern, key message, attribution), returns
 * a map of native-sounding RO strings keyed by the pattern's required_copy fields.
 */
final class RomanianPromptComposer
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));
        $this->model = config('services.image_generation.composer_model', 'gpt-5.4');
    }

    /**
     * @param array $spec [
     *   'niche' => string,
     *   'pattern' => string,
     *   'key_message' => string,      // headline idea the copy should reinforce
     *   'needs' => array<string>,     // keys from PatternCatalog::requiredCopyFields()
     *   'attribution' => ['name' => string, 'role' => string]|null,
     * ]
     * @return array<string,string>|null Map of ro copy by needed key, or null on failure.
     */
    public function compose(array $spec): ?array
    {
        if (!$this->apiKey || empty($spec['needs'])) {
            return null;
        }

        $cacheKey = 'ro_composer:' . sha1(json_encode($spec));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($spec) {
            return $this->callApi($spec);
        });
    }

    private function callApi(array $spec): ?array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($spec)],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.4,
                ]);

            if (!$response->ok()) {
                Log::warning('RomanianPromptComposer: API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode((string) $content, true);

            if (!is_array($parsed)) {
                Log::warning('RomanianPromptComposer: invalid JSON response', ['content' => $content]);
                return null;
            }

            return array_intersect_key($parsed, array_flip($spec['needs']));
        } catch (\Throwable $e) {
            Log::error('RomanianPromptComposer exception', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Ești un copywriter nativ român, expert în marketing digital premium pentru platforme SaaS B2B românești.

BRAND Sambla (foarte important să înțelegi corect):
- Sambla oferă AGENȚI AI care vorbesc cu clienții business-ului — pe WebChat (widget pe site) și pe TELEFON (voice pe un număr real).
- Agentul preia apeluri 24/7, răspunde în chat, programează întâlniri.
- Documentele încărcate în knowledge base sunt DOAR sursa din care agentul răspunde corect clienților (nu facem management de documente — doar le folosim intern pentru răspunsuri precise, anti-halucinare).
- Nu este platformă de gestionare documente, nu este content manager. Este ASISTENT AI care vorbește cu clienții business-ului (cabinete medicale, saloane, restaurante, ateliere, consultanță etc.).
- Când scrii copy, vorbește despre „clienții tăi răspund la chat", „preia apelurile când nu poți", „programează-ți întâlnirile în calendar" — NU despre „organizează-ți documentele" sau „asistent pentru documente".

Stil obligatoriu:
- Română idiomatică, modernă, așa cum vorbește un român educat în 2026.
- Scurt, direct, uman. Fără fluff marketing, fără exclamații false, fără „descoperă acum".
- Fără traduceri literale din engleză. Fără reflexive redundante („să-mi programez" → „să programez"). Fără regionalisme învechite („românește" → „limba română"). Fără articole definite când indefinitul e mai firesc.
- Diacritice corecte: ă, â, î, ș, ț.
- Persona tipică a subiectului: Maria de la cofetărie, Dr. Andrei de la veterinar — oameni reali, nu corporate SaaS.

Reguli absolute:
- Nu menționa niciodată OpenAI, ChatGPT, GPT, Claude, Gemini sau alți furnizori de modele AI.
- Nu menționa concurenți.
- Nu folosi emoji în copy (doar ✦, ◇ și bifa verde dacă sunt cerute explicit ca decor).

Format răspuns: strict JSON object, cheile cerute de utilizator, fiecare valoare un string scurt (max 80 caractere) în română nativă. Nu adăuga alte chei. Fără explicații în afara JSON-ului.
PROMPT;
    }

    private function userPrompt(array $spec): string
    {
        $niche = $spec['niche'] ?? 'default';
        $pattern = $spec['pattern'] ?? 'unknown';
        $keyMessage = $spec['key_message'] ?? '';
        $attribution = $spec['attribution'] ?? null;
        $needs = $spec['needs'];

        $lines = [
            "Compune copy pentru o postare social-media premium.",
            "Nișă client: {$niche}",
            "Tip vizual (pattern): {$pattern}",
            "Mesaj cheie de comunicat: {$keyMessage}",
        ];

        if ($attribution && isset($attribution['name'], $attribution['role'])) {
            $lines[] = "Persoana în imagine: {$attribution['name']}, {$attribution['role']}";
        }

        $lines[] = "";
        $lines[] = "Returnează JSON cu EXACT aceste chei (nimic în plus):";
        $lines[] = json_encode(array_fill_keys($needs, '...'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lines[] = "";
        $lines[] = "Fiecare valoare: o singură propoziție sau sintagmă scurtă, română nativă, fără ghilimele duble la început/sfârșit dacă cheia nu se numește `pull_quote` sau `attribution`.";

        return implode("\n", $lines);
    }
}
