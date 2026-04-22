<?php

namespace App\Services\Social\ImageGenerators;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GptImage2Generator
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));
        $this->model = config('services.image_generation.gpt_image_2_model', 'gpt-image-2');
    }

    /**
     * Generate an image for a social media post.
     * Returns ['url', 'path', 'mime_type', 'provider', 'response_ms'] on success, null on failure.
     */
    public function generate(string $prompt, string $aspectRatio = '4:5', ?string $referenceImagePath = null): ?array
    {
        if (!$this->apiKey) {
            Log::warning('GptImage2Generator: no OpenAI API key configured');
            return null;
        }

        $size = $this->resolveSize($aspectRatio);
        $start = microtime(true);

        try {
            $response = $referenceImagePath && file_exists($referenceImagePath)
                ? $this->callEdits($prompt, $size, $referenceImagePath)
                : $this->callGenerations($prompt, $size);

            $elapsed = (int) ((microtime(true) - $start) * 1000);

            if (!$response->ok()) {
                Log::warning('GptImage2Generator: API error', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            $data = $response->json();
            $b64 = $data['data'][0]['b64_json'] ?? null;
            $remoteUrl = $data['data'][0]['url'] ?? null;

            if (!$b64 && $remoteUrl) {
                $fetch = Http::timeout(60)->get($remoteUrl);
                if ($fetch->ok()) {
                    $b64 = base64_encode($fetch->body());
                }
            }

            if (!$b64) {
                Log::warning('GptImage2Generator: no image in response');
                return null;
            }

            $filename = 'social/' . date('Y/m') . '/' . uniqid('gi2_') . '.png';
            $storagePath = public_path($filename);
            $dir = dirname($storagePath);
            if (!is_dir($dir)) {
                // Use 0777 explicitly — the mount is shared with a host user
                // whose uid doesn't match the container user, so anything
                // narrower means the next month's subdirectory becomes write-
                // locked.
                mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
            file_put_contents($storagePath, base64_decode($b64));
            @chmod($storagePath, 0666);
            $publicUrl = rtrim(config('app.cdn_url') ?: config('app.url'), '/') . '/' . $filename;

            return [
                'url' => $publicUrl,
                'path' => $filename,
                'mime_type' => 'image/png',
                'provider' => 'gpt-image-2',
                'response_ms' => $elapsed,
            ];
        } catch (\Throwable $e) {
            Log::error('GptImage2Generator exception', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    private function callGenerations(string $prompt, string $size)
    {
        return Http::timeout(300)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $this->model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => 'high',
                'n' => 1,
            ]);
    }

    private function callEdits(string $prompt, string $size, string $imagePath)
    {
        return Http::timeout(300)
            ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
            ->attach('image', file_get_contents($imagePath), basename($imagePath))
            ->post('https://api.openai.com/v1/images/edits', [
                'model' => $this->model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => 'high',
                'n' => 1,
            ]);
    }

    private function resolveSize(string $aspectRatio): string
    {
        return match ($aspectRatio) {
            '1:1' => '1024x1024',
            '4:5' => '1024x1280',   // Instagram feed portrait — exact 4:5, no grid crop
            '9:16' => '1024x1792',  // Instagram Stories / Reels — closest supported portrait
            '2:3' => '1024x1536',   // legacy / landing hero portrait
            '4:3' => '1280x1024',
            '3:2' => '1536x1024',
            '16:9' => '1792x1024',
            default => '1024x1280',
        };
    }
}
