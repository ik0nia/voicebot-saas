<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiContentService
{
    private string $geminiApiKey;
    private string $geminiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $textModel = 'gpt-4o-mini'; // OpenAI for text (better Romanian)
    private string $imageModel;                  // Gemini for images (native generation)

    // Vertex AI config for image generation via service account
    private string $vertexProjectId = 'gen-lang-client-0096953872';
    private string $vertexImageModel = 'gemini-3.1-flash-image-preview';
    private string $serviceAccountPath;

    public function __construct()
    {
        $dbKey = \DB::table('settings')->where('key', 'gemini_api_key')->value('value');
        $this->geminiApiKey = $dbKey ? decrypt($dbKey) : config('services.gemini.api_key', '');
        $this->imageModel = \DB::table('settings')->where('key', 'gemini_image_model')->value('value')
            ?: env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash');
        $this->serviceAccountPath = storage_path('app/google-service-account.json');
    }

    /**
     * Generate a social media post
     */
    public function generatePost(string $platform, string $topic, array $styleGuidelines = [], string $language = 'ro'): array
    {
        $styleContext = $this->buildStyleContext($platform, $styleGuidelines);

        $platformRules = match($platform) {
            'facebook' => "Post Facebook: 100-300 cuvinte, poate fi mai lung. Ton conversațional. Include CTA. Poate avea link-uri. Emoji-uri moderate.",
            'instagram' => "Caption Instagram: 50-150 cuvinte. Vizual, emoțional. Emoji-uri abundant. Fără link-uri în text. Nu folosi hashtag-uri.",
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
            . '{"content": "textul postării", "image_prompt": "prompt scurt în engleză pentru generarea unei imagini potrivite", "title": "titlu (doar pentru blog)"}';

        $response = $this->callGemini($prompt);

        if (!$response) {
            return ['error' => 'Gemini API call failed'];
        }

        // Parse JSON from response
        $text = $response['text'] ?? '';
        $parsed = $this->extractJson($text);

        return [
            'content' => $parsed['content'] ?? $text,
            'hashtags' => [],
            'image_prompt' => $parsed['image_prompt'] ?? null,
            'title' => $parsed['title'] ?? null,
            'tokens_used' => $response['tokens_used'] ?? 0,
            'model' => $this->textModel,
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
     * Generate an image using Vertex AI Gemini 3.1 Flash Image Preview.
     * Uses OAuth2 service account authentication.
     *
     * @return array|null Image data array or null on failure
     */
    /**
     * Canonical brand + composition rules applied to EVERY image prompt,
     * regardless of which provider (OpenAI or Vertex) actually renders it.
     * Single source of truth so both backends behave identically.
     */
    private function imageRulesPreamble(): string
    {
        return "STRICT BRAND & COMPOSITION RULES (apply to every image, override any conflicting instruction below):\n"
            . "1. NO PEOPLE — no humans, faces, hands, silhouettes, crowds. If the brief mentions a person, replace them with a typography poster, device mockup, isometric diorama, abstract geometric composition, paper collage, or data visualization. (Rare exception: only if the topic absolutely requires a person, and only one Caucasian/European subject.)\n"
            . "2. NO FAKE LOGO — do NOT invent or render any 'Sambla' wordmark, brand text, or logo of your own. Leave the top-right corner clean. The real logo is composited separately.\n"
            . "3. ROMANIAN TEXT ONLY — any on-image text must be in Romanian with proper diacritics (ă â î ș ț). Keep it short (max 3-5 words), large, centered, as the visual hero. If you cannot render diacritics cleanly, skip the text entirely.\n"
            . "4. STYLE — graphic design first: editorial typography, isometric 3D dioramas (uninhabited), abstract Bauhaus geometry, product device mockups on clean surfaces, paper collage, data visualization. NEVER stock-photo clichés (handshakes, suits pointing at laptops, smiling diverse teams).\n"
            . "5. PALETTE — off-white background, deep slate #1e293b, single Sambla red #dc2626 accent. Premium, not busy. Generous whitespace. Strong focal point.\n"
            . "6. AUDIENCE — Romanian small-business owners. Keep it warm, premium, designed.\n\n"
            . "BRIEF:\n";
    }

    public function generateImage(string $prompt, string $aspectRatio = '1:1', ?string $style = null): ?array
    {
        // Wrap the caller's brief with the canonical rules so both providers
        // see the exact same instructions.
        $wrapped = $this->imageRulesPreamble() . $prompt;

        // Vertex AI FIRST — it accepts our real logo PNG as a reference
        // image, so the brand mark renders correctly. OpenAI gpt-image-1
        // can't accept reference images and tends to fabricate fake "Sambla"
        // wordmarks, so we only fall back to it when Vertex is unavailable.
        $vertexResult = $this->generateImageVertex($wrapped, $aspectRatio, $style);
        if ($vertexResult) {
            return $vertexResult;
        }

        Log::warning('Vertex AI failed, falling back to OpenAI', ['aspect' => $aspectRatio]);
        return $this->generateImageOpenAi($wrapped, $aspectRatio);
    }

    private function generateImageVertex(string $prompt, string $aspectRatio = '1:1', ?string $style = null): ?array
    {
        try {
            $accessToken = $this->getVertexAccessToken();
            if (!$accessToken) {
                Log::error('GeminiContentService: Failed to obtain Vertex AI access token');
                return null;
            }

            $url = "https://aiplatform.googleapis.com/v1/projects/{$this->vertexProjectId}/locations/global/publishers/google/models/{$this->vertexImageModel}:generateContent";

            // Pick style preset — random if not specified
            $styles = config('social-image-styles');
            if (!$style || !isset($styles[$style])) {
                $style = array_rand($styles);
            }
            $preset = $styles[$style];

            // Always attach the real logo as a reference image. The previous
            // 40%-of-the-time strategy let Gemini fabricate its own fake
            // "Sambla" wordmarks on the other 60% of generations. Sending
            // the real PNG every time anchors the model on our actual mark.
            $logoFile = public_path('images/social/logo-light.png');
            $logoBase64 = file_exists($logoFile) ? base64_encode(file_get_contents($logoFile)) : null;

            $parts = [];

            // Add logo as reference image
            if ($logoBase64) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'image/png',
                        'data' => $logoBase64,
                    ]
                ];
            }

            $stylePrompt = $preset['prompt'];

            $brandLine = $logoBase64
                ? "BRAND LOGO: Use the attached logo EXACTLY as provided — place it as a stamp in a visible corner (vary the corner). Place a solid WHITE rounded rectangle directly behind the logo so the black 'Sambla' wordmark is crisp and perfectly legible. Do NOT redraw, recolor or recreate the logo text. "
                : "BRAND: Do NOT add any logo, wordmark, or brand text to this image. Keep it purely visual. ";

            $parts[] = ['text' => "Generate a professional social media graphic with EXACT aspect ratio {$aspectRatio} (this is critical — the image MUST be {$aspectRatio}, portrait orientation if 3:4, vertical 9:16 for stories). "
                . $brandLine
                . "VISUAL STYLE ({$preset['name']}): {$stylePrompt} "
                . "TEXT RULES: All text on the graphic MUST be in Romanian. Keep text very short — use punchy CTA phrases, max 5-7 words per line. "
                . "CONTENT: {$prompt}"];

            $startTime = microtime(true);

            $response = Http::timeout(300)
                ->withHeaders(['Authorization' => "Bearer {$accessToken}"])
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => $parts,
                        ]
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->ok()) {
                Log::error('Vertex AI Image API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                $this->trackImageMetric('error', 0, 0, 0, $responseTimeMs);
                return null;
            }

            $data = $response->json();
            $parts = $data['candidates'][0]['content']['parts'] ?? [];

            $imageData = null;
            $mimeType = null;
            $altText = '';

            foreach ($parts as $part) {
                if (isset($part['inlineData'])) {
                    $imageData = $part['inlineData']['data'] ?? null;
                    $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                }
                if (isset($part['text'])) {
                    $altText = $part['text'];
                }
            }

            if (!$imageData) {
                Log::warning('Vertex AI Image: no image in response', ['parts_count' => count($parts)]);
                $this->trackImageMetric('error', 0, 0, 0, $responseTimeMs);
                return null;
            }

            // Save to public storage
            $extension = $mimeType === 'image/jpeg' ? 'jpg' : 'png';
            $filename = 'social/' . date('Y/m') . '/' . uniqid('img_') . '.' . $extension;
            $storagePath = public_path($filename);

            $dir = dirname($storagePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($storagePath, base64_decode($imageData));

            $publicUrl = rtrim(config('app.cdn_url') ?: config('app.url'), '/') . '/' . $filename;
            $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            $this->trackImageMetric('success', $inputTokens, $outputTokens, ($inputTokens + $outputTokens) * 0.01 / 1000, $responseTimeMs);

            return [
                'url' => $publicUrl,
                'path' => $filename,
                'mime_type' => $mimeType,
                'alt_text' => $altText,
            ];
        } catch (\Throwable $e) {
            Log::error('Vertex AI Image exception', ['error' => $e->getMessage()]);
            $this->trackImageMetric('error', 0, 0, 0, 0);
            return null;
        }
    }

    /**
     * Fallback image generation via OpenAI gpt-image-1 when Vertex AI is unavailable.
     * gpt-image-1 sizes: 1024x1024 (1:1), 1024x1536 (portrait), 1536x1024 (landscape).
     */
    private function generateImageOpenAi(string $prompt, string $aspectRatio = '1:1'): ?array
    {
        try {
            $apiKey = \App\Models\PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));
            if (!$apiKey) {
                Log::error('OpenAI fallback: no API key configured');
                return null;
            }

            $size = match ($aspectRatio) {
                '9:16', '3:4', '2:3' => '1024x1536',
                '16:9', '4:3', '3:2' => '1536x1024',
                default              => '1024x1024',
            };

            // OpenAI gpt-image-1 cannot receive reference images, so any
            // "Sambla" mark it draws is fabricated. Hammer this into the
            // prompt as the FIRST and LAST instruction so it has zero
            // ambiguity. We composite the real logo separately when needed.
            $openaiGuard = "ABSOLUTE RULE — READ FIRST: this image must contain ZERO brand marks. Do NOT draw, render, type, or imply the word 'Sambla' anywhere on the image. Do NOT invent any wordmark, logo, badge, brand stamp, watermark, or 'AI brand' element. Do NOT put any company-looking text in any corner. Leave the top-right corner completely empty (no shapes, no text, no decoration) — a real logo will be composited there separately. If you are tempted to draw a logo or brand text, STOP and replace it with empty whitespace. ";
            $openaiGuardEnd = "\n\nFINAL REMINDER: ZERO brand text on this image. ZERO 'Sambla' marks. ZERO invented logos. Top-right corner = empty whitespace.";
            $prompt = $openaiGuard . $prompt . $openaiGuardEnd;

            $startTime = microtime(true);

            $response = Http::timeout(180)
                ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => 'gpt-image-1',
                    'prompt' => mb_substr($prompt, 0, 3900),
                    'size' => $size,
                    'n' => 1,
                ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->ok()) {
                Log::error('OpenAI image API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                $this->trackImageMetric('error', 0, 0, 0, $responseTimeMs);
                return null;
            }

            $data = $response->json();
            $b64 = $data['data'][0]['b64_json'] ?? null;
            $remoteUrl = $data['data'][0]['url'] ?? null;

            if (!$b64 && $remoteUrl) {
                $bin = Http::timeout(60)->get($remoteUrl);
                if ($bin->ok()) {
                    $b64 = base64_encode($bin->body());
                }
            }

            if (!$b64) {
                Log::warning('OpenAI image: no image bytes in response');
                $this->trackImageMetric('error', 0, 0, 0, $responseTimeMs);
                return null;
            }

            $filename = 'social/' . date('Y/m') . '/' . uniqid('openai_') . '.png';
            $storagePath = public_path($filename);
            $dir = dirname($storagePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($storagePath, base64_decode($b64));

            $publicUrl = rtrim(config('app.cdn_url') ?: config('app.url'), '/') . '/' . $filename;

            $usage = $data['usage'] ?? [];
            $inputTokens = $usage['input_tokens'] ?? 0;
            $outputTokens = $usage['output_tokens'] ?? 0;
            $this->trackImageMetric('success', $inputTokens, $outputTokens, ($inputTokens + $outputTokens) * 0.01 / 1000, $responseTimeMs);

            return [
                'url' => $publicUrl,
                'path' => $filename,
                'mime_type' => 'image/png',
                'alt_text' => '',
                'provider' => 'openai',
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI image exception', ['error' => $e->getMessage()]);
            $this->trackImageMetric('error', 0, 0, 0, 0);
            return null;
        }
    }

    /**
     * Get OAuth2 access token for Vertex AI using service account JWT.
     * Tokens are cached for 55 minutes (they expire after 60).
     */
    private function getVertexAccessToken(): ?string
    {
        return Cache::remember('vertex_ai_access_token', 3300, function () {
            if (!file_exists($this->serviceAccountPath)) {
                Log::error('Google service account file not found', ['path' => $this->serviceAccountPath]);
                return null;
            }

            $sa = json_decode(file_get_contents($this->serviceAccountPath), true);
            $now = time();

            // Build JWT
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => $sa['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signingInput = "{$header}.{$claims}";
            $privateKey = openssl_pkey_get_private($sa['private_key']);
            if (!$privateKey) {
                Log::error('Failed to parse service account private key');
                return null;
            }

            openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $jwt = "{$signingInput}." . $this->base64UrlEncode($signature);

            // Exchange JWT for access token
            $response = Http::asForm()->post($sa['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->ok()) {
                Log::error('Google OAuth2 token exchange failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Base64 URL-safe encoding (no padding).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Track image generation metrics.
     */
    private function trackImageMetric(string $status, int $inputTokens, int $outputTokens, float $costCents, int $responseTimeMs): void
    {
        try {
            \App\Models\AiApiMetric::create([
                'provider' => 'google',
                'model' => $this->vertexImageModel,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_cents' => $costCents,
                'response_time_ms' => $responseTimeMs,
                'status' => $status,
                'error_type' => 'social_image',
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Generate a complete post with text + image
     */
    public function generatePostWithImage(string $platform, string $topic, array $styleGuidelines = [], string $language = 'ro'): array
    {
        // Step 1: Generate text content
        $post = $this->generatePost($platform, $topic, $styleGuidelines, $language);

        if (isset($post['error'])) {
            return $post;
        }

        // Step 2: Generate image using the image_prompt from step 1
        $imagePrompt = $post['image_prompt'] ?? "Professional social media visual about: {$topic}";

        // Platform-specific aspect ratios
        $aspectRatio = match($platform) {
            'facebook', 'instagram' => '3:4',
            'blog' => '16:9',
            default => '1:1',
        };

        $image = $this->generateImage($imagePrompt, $aspectRatio);

        if ($image) {
            $post['image_url'] = $image['url'];
            $post['image_path'] = $image['path'];
        }

        return $post;
    }

    /**
     * Core text generation via OpenAI GPT-4o-mini (better Romanian than Gemini)
     */
    private function callGemini(string $prompt, int $maxTokens = 2000): ?array
    {
        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => $this->textModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești un expert în marketing digital și social media pentru branduri tech/SaaS. Generezi conținut creativ, engaging și optimizat per platformă. Răspunzi întotdeauna în formatul JSON cerut.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.8,
                'response_format' => ['type' => 'json_object'],
            ]);

            $text = $response->choices[0]->message->content ?? '';
            $tokens = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);

            // Track cost
            $costCents = (($response->usage->promptTokens ?? 0) * 0.015 / 1000) + (($response->usage->completionTokens ?? 0) * 0.06 / 1000);
            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'openai',
                    'model' => $this->textModel,
                    'input_tokens' => $response->usage->promptTokens ?? 0,
                    'output_tokens' => $response->usage->completionTokens ?? 0,
                    'cost_cents' => $costCents,
                    'response_time_ms' => 0,
                    'status' => 'success',
                    'error_type' => 'social_text',
                ]);
            } catch (\Throwable $e) {}

            return ['text' => $text, 'tokens_used' => $tokens];
        } catch (\Throwable $e) {
            Log::error('OpenAI Social content exception', ['error' => $e->getMessage()]);
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
