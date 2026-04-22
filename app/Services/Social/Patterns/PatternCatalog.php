<?php

namespace App\Services\Social\Patterns;

final class PatternCatalog
{
    public function get(string $slug): ?array
    {
        return config("social-image-patterns.patterns.{$slug}");
    }

    public function exists(string $slug): bool
    {
        return $this->get($slug) !== null;
    }

    public function availableSlugs(): array
    {
        return array_keys(config('social-image-patterns.patterns', []));
    }

    public function aspectRatio(string $slug): string
    {
        return $this->get($slug)['aspect_ratio'] ?? '4:5';
    }

    public function requiredCopyFields(string $slug): array
    {
        return $this->get($slug)['required_copy'] ?? [];
    }

    /**
     * Render the final prompt string sent to the image model.
     * Assembles: brand preamble + safe-zone rule + rendered pattern template + text/do-not rules.
     */
    public function render(string $slug, string $niche = 'default', array $copyOverrides = []): ?string
    {
        $pattern = $this->get($slug);
        if (!$pattern) {
            return null;
        }

        $copy = array_merge($pattern['default_copy'] ?? [], $copyOverrides);
        $copy['subject_hint'] = $this->resolveSubject($niche);
        $copy['subject_hint_before'] = $this->resolveSubject($niche, 'before');
        $copy['subject_hint_after'] = $copy['subject_hint_before'];

        $rendered = $pattern['template'];
        foreach ($copy as $key => $value) {
            $rendered = str_replace('{' . $key . '}', (string) $value, $rendered);
        }

        return implode("\n\n", [
            config('social-image-patterns.brand_preamble', ''),
            config('social-image-patterns.safe_zone_rule', ''),
            "CANVAS: portrait aspect ratio {$pattern['aspect_ratio']}.",
            $rendered,
            config('social-image-patterns.text_rule', ''),
            config('social-image-patterns.do_not_rule', ''),
        ]);
    }

    private function resolveSubject(string $niche, string $variant = 'normal'): string
    {
        $key = $variant === 'before' ? 'niche_subjects_before' : 'niche_subjects';
        $subjects = config("social-image-patterns.{$key}", []);
        return $subjects[$niche] ?? $subjects['default'] ?? 'a warm-smiling Romanian professional';
    }
}
