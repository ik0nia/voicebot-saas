<?php

namespace App\Services\Social;

use App\Services\Social\Composer\RomanianPromptComposer;
use App\Services\Social\ImageGenerators\GptImage2Generator;
use App\Services\Social\Patterns\PatternCatalog;
use Illuminate\Support\Facades\Log;

/**
 * Entry point for producing premium social-media images for the Sambla brand.
 *
 *   1. PatternCatalog picks the visual grammar (bento / annotated photo / split / ...).
 *   2. RomanianPromptComposer (optional, per call) polishes RO copy via GPT-5.4
 *      before it lands inside the image prompt.
 *   3. GptImage2Generator calls the image model and persists the output.
 *
 * Callers pass a pattern slug, a niche, and an optional key message. Everything
 * else (brand preamble, safe-zones, do-not rules, niche subject hints) comes
 * from the catalog.
 */
final class SocialImageOrchestrator
{
    public function __construct(
        private PatternCatalog $catalog,
        private RomanianPromptComposer $composer,
        private GptImage2Generator $generator,
    ) {}

    /**
     * @param array $options [
     *   'key_message' => string|null,         // intent for the composer ("programări automate 24/7")
     *   'copy_overrides' => array,            // manual RO string map, skips composer for these keys
     *   'attribution' => ['name','role']|null,
     *   'use_composer' => bool,               // default true — run GPT-5.4 polish
     *   'reference_image_path' => string|null // logo passed to /v1/images/edits
     * ]
     */
    public function generate(string $patternSlug, string $niche = 'default', array $options = []): ?array
    {
        if (!$this->catalog->exists($patternSlug)) {
            Log::warning('SocialImageOrchestrator: unknown pattern', ['slug' => $patternSlug]);
            return null;
        }

        $useComposer = $options['use_composer'] ?? true;
        $copyOverrides = $options['copy_overrides'] ?? [];
        $keyMessage = $options['key_message'] ?? null;
        $attribution = $options['attribution'] ?? null;

        if ($useComposer && $keyMessage) {
            $needs = array_diff($this->catalog->requiredCopyFields($patternSlug), array_keys($copyOverrides));
            if (!empty($needs)) {
                $composed = $this->composer->compose([
                    'niche' => $niche,
                    'pattern' => $patternSlug,
                    'key_message' => $keyMessage,
                    'attribution' => $attribution,
                    'needs' => array_values($needs),
                ]);
                if (is_array($composed)) {
                    $copyOverrides = array_merge($composed, $copyOverrides);
                }
            }
        }

        $prompt = $this->catalog->render($patternSlug, $niche, $copyOverrides);
        if (!$prompt) {
            return null;
        }

        $aspect = $this->catalog->aspectRatio($patternSlug);

        // Always pass the Sambla logo icon as reference when available — gpt-image-2
        // renders it pixel-perfect via /v1/images/edits instead of drawing a cartoon stand-in.
        $referenceImage = $options['reference_image_path'] ?? $this->defaultLogoPath();
        if ($referenceImage && !file_exists($referenceImage)) {
            $referenceImage = null;
        }

        $result = $this->generator->generate($prompt, $aspect, $referenceImage);
        if ($result) {
            $result['pattern'] = $patternSlug;
            $result['niche'] = $niche;
            $result['prompt_length'] = mb_strlen($prompt);
            $result['logo_referenced'] = $referenceImage !== null;
        }
        return $result;
    }

    private function defaultLogoPath(): ?string
    {
        $path = public_path('images/logo-icon.png');
        return file_exists($path) ? $path : null;
    }
}
