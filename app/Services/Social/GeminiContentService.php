<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiContentService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.0-flash'));
    }

    /**
     * Generate a social media post
     */
    public function generatePost(string $platform, string $topic, array $styleGuidelines = [], string $language = 'ro'): array
    {
        $styleContext = $this->buildStyleContext($platform, $styleGuidelines);

        $platformRules = match($platform) {
            'facebook' => "Post Facebook: 100-300 cuvinte, poate fi mai lung. Ton conversațional. Include CTA. Poate avea link-uri. Emoji-uri moderate.",
            'instagram' => "Caption Instagram: 50-150 cuvinte. Vizual, emoțional. Include 10-15 hashtag-uri relevante. Emoji-uri abundant. Fără link-uri în text.",
            'blog' => "Articol blog: 500-1000 cuvinte. SEO-friendly. Include H2/H3 headings. Ton profesional dar accesibil. Paragraf introductiv captivant.",
            default => "Post social media: 100-200 cuvinte.",
        };

        $prompt = "Generează un post pentru {$platform} despre: {$topic}\n\n"
            . "REGULI PLATFORMĂ:\n{$platformRules}\n\n"
            . "CONTEXT BRAND:\nSambla este o platformă românească de AI conversațional (chatbot + voicebot) pentru business-uri. "
            . "Oferim: chatbot inteligent, voicebot cu voce naturală, integrare WooCommerce, bază de cunoștințe AI, analytics avansate. "
            . "Setup în 10 minute, funcționează 24/7, anti-halucinare, GDPR compliant. Planuri de la 99€/lună.\n\n"
            . ($styleContext ? "STIL DORIT:\n{$styleContext}\n\n" : "")
            . "LIMBA: {$language}\n\n"
            . "Returnează JSON cu structura:\n"
            . '{"content": "textul postării", "hashtags": ["tag1", "tag2"], "image_prompt": "prompt scurt în engleză pentru generarea unei imagini potrivite", "title": "titlu (doar pentru blog)"}';

        $response = $this->callGemini($prompt);

        if (!$response) {
            return ['error' => 'Gemini API call failed'];
        }

        // Parse JSON from response
        $text = $response['text'] ?? '';
        $parsed = $this->extractJson($text);

        return [
            'content' => $parsed['content'] ?? $text,
            'hashtags' => $parsed['hashtags'] ?? [],
            'image_prompt' => $parsed['image_prompt'] ?? null,
            'title' => $parsed['title'] ?? null,
            'tokens_used' => $response['tokens_used'] ?? 0,
            'model' => $this->model,
        ];
    }

    /**
     * Generate bio text for a platform
     */
    public function generateBio(string $platform, array $styleGuidelines = []): array
    {
        $rules = match($platform) {
            'facebook' => "Bio pagină Facebook: max 255 caractere. Include: ce facem, pentru cine, CTA. Profesional dar accesibil.",
            'instagram' => "Bio Instagram: max 150 caractere. Concis, cu emoji-uri. Include: ce facem, link menționat, CTA scurt.",
            default => "Bio scurt pentru {$platform}: max 200 caractere.",
        };

        $prompt = "Generează o bio pentru pagina {$platform} a brandului Sambla.\n\n"
            . "DESPRE SAMBLA: Platformă românească de AI conversațional — chatbot și voicebot inteligent pentru business-uri. "
            . "Setup 10 minute, 24/7, anti-halucinare. De la 99€/lună.\n\n"
            . "REGULI: {$rules}\n\n"
            . "Returnează JSON: {\"bio\": \"textul\", \"alternatives\": [\"varianta2\", \"varianta3\"]}";

        $response = $this->callGemini($prompt);
        $parsed = $this->extractJson($response['text'] ?? '');

        return [
            'bio' => $parsed['bio'] ?? '',
            'alternatives' => $parsed['alternatives'] ?? [],
            'tokens_used' => $response['tokens_used'] ?? 0,
        ];
    }

    /**
     * Generate blog article (longer form)
     */
    public function generateBlogArticle(string $topic, array $styleGuidelines = [], string $language = 'ro'): array
    {
        $prompt = "Scrie un articol de blog complet despre: {$topic}\n\n"
            . "CONTEXT: Blogul platformei Sambla (sambla.ro) — AI conversațional pentru business-uri.\n\n"
            . "STRUCTURA ARTICOL:\n"
            . "- Titlu SEO-friendly (H1)\n"
            . "- Meta description (max 160 caractere)\n"
            . "- Paragraf introductiv captivant (2-3 propoziții)\n"
            . "- 3-5 secțiuni cu H2 headings\n"
            . "- Fiecare secțiune: 100-200 cuvinte\n"
            . "- Concluzie cu CTA\n"
            . "- Total: 600-1200 cuvinte\n\n"
            . "TON: Profesional dar accesibil, expert în domeniu, orientat spre valoare practică.\n"
            . "LIMBA: {$language}\n\n"
            . "Returnează JSON:\n"
            . '{"title": "...", "meta_description": "...", "content": "articolul complet în Markdown", "tags": ["tag1", "tag2"], "image_prompt": "prompt scurt în engleză pentru header image"}';

        $response = $this->callGemini($prompt, maxTokens: 4000);
        $parsed = $this->extractJson($response['text'] ?? '');

        return [
            'title' => $parsed['title'] ?? $topic,
            'meta_description' => $parsed['meta_description'] ?? '',
            'content' => $parsed['content'] ?? $response['text'] ?? '',
            'tags' => $parsed['tags'] ?? [],
            'image_prompt' => $parsed['image_prompt'] ?? null,
            'tokens_used' => $response['tokens_used'] ?? 0,
        ];
    }

    /**
     * Analyze style from approved examples and generate guidelines
     */
    public function analyzeStyle(array $approvedExamples, string $platform): array
    {
        $examples = collect($approvedExamples)->take(20)->map(fn($e) => "---\n" . $e)->implode("\n");

        $prompt = "Analizează aceste exemple de postări {$platform} aprobate și extrage ghidul de stil:\n\n"
            . "{$examples}\n\n"
            . "Returnează JSON cu:\n"
            . '{"tone": "descriere ton", "emoji_usage": "none|minimal|moderate|abundant", "avg_length": "short|medium|long", '
            . '"vocabulary": ["cuvinte cheie frecvente"], "structure_pattern": "descriere structură", '
            . '"do": ["ce să facă"], "dont": ["ce să nu facă"], '
            . '"summary": "rezumat stil în 2-3 propoziții"}';

        $response = $this->callGemini($prompt);
        return $this->extractJson($response['text'] ?? '') ?: [];
    }

    /**
     * Core Gemini API call
     */
    private function callGemini(string $prompt, int $maxTokens = 2000): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('GeminiContentService: API key not configured');
            return null;
        }

        try {
            $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(60)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $maxTokens,
                    'temperature' => 0.8,
                ],
            ]);

            if (!$response->ok()) {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokens = ($data['usageMetadata']['promptTokenCount'] ?? 0) + ($data['usageMetadata']['candidatesTokenCount'] ?? 0);

            return ['text' => $text, 'tokens_used' => $tokens];
        } catch (\Throwable $e) {
            Log::error('Gemini API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract JSON from LLM response (may be wrapped in markdown code blocks)
     */
    private function extractJson(string $text): array
    {
        // Remove markdown code blocks
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try to find JSON in the text
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Build style context from guidelines and approved examples
     */
    private function buildStyleContext(string $platform, array $guidelines): string
    {
        if (empty($guidelines)) {
            // Load from approved style preferences
            $approved = \App\Models\SocialStylePreference::where('platform', $platform)
                ->where('approved', true)
                ->latest()
                ->limit(10)
                ->pluck('example_content')
                ->toArray();

            if (empty($approved)) return '';

            return "Bazează-te pe aceste exemple de stil aprobate:\n" . implode("\n---\n", array_slice($approved, 0, 5));
        }

        $parts = [];
        if (!empty($guidelines['tone'])) $parts[] = "Ton: {$guidelines['tone']}";
        if (!empty($guidelines['emoji_usage'])) $parts[] = "Emoji: {$guidelines['emoji_usage']}";
        if (!empty($guidelines['avg_length'])) $parts[] = "Lungime: {$guidelines['avg_length']}";
        if (!empty($guidelines['do'])) $parts[] = "DA: " . implode(', ', $guidelines['do']);
        if (!empty($guidelines['dont'])) $parts[] = "NU: " . implode(', ', $guidelines['dont']);
        if (!empty($guidelines['summary'])) $parts[] = "Stil: {$guidelines['summary']}";

        return implode("\n", $parts);
    }
}
