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

    /**
     * Pick a pattern slug at random, weighted by each pattern's `weight` field.
     * Illustration patterns default to weight 1.0; photo patterns to 0.2-0.25, so
     * random selection trends ~80% illustration in line with the social visual mix spec.
     */
    public function pickWeighted(?string $category = null): ?string
    {
        $patterns = config('social-image-patterns.patterns', []);
        if (empty($patterns)) {
            return null;
        }

        $candidates = [];
        foreach ($patterns as $slug => $def) {
            if ($category !== null && ($def['category'] ?? null) !== $category) {
                continue;
            }
            $candidates[$slug] = (float) ($def['weight'] ?? 1.0);
        }
        if (empty($candidates)) {
            return null;
        }

        $total = array_sum($candidates);
        $roll = mt_rand() / mt_getrandmax() * $total;
        $acc = 0.0;
        foreach ($candidates as $slug => $weight) {
            $acc += $weight;
            if ($roll <= $acc) {
                return $slug;
            }
        }
        return array_key_first($candidates);
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
        $copy['niche_graphics'] = $this->resolveMap($niche, 'niche_graphic_elements');
        $copy['niche_scene'] = $this->resolveMap($niche, 'niche_scene');
        $copy['niche_label'] = $this->resolveMap($niche, 'niche_labels');
        $copy['sambla_mark'] = (string) config('social-image-patterns.sambla_mark', '');

        $rendered = $pattern['template'];
        foreach ($copy as $key => $value) {
            $rendered = str_replace('{' . $key . '}', (string) $value, $rendered);
        }
        // Second pass: catch placeholders that reference niche_label inside default_copy values
        // (e.g. footer_tag = "... {niche_label}").
        $rendered = str_replace(
            ['{niche_label}', '{niche_graphics}', '{niche_scene}', '{sambla_mark}'],
            [$copy['niche_label'], $copy['niche_graphics'], $copy['niche_scene'], $copy['sambla_mark']],
            $rendered
        );

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

    private function resolveMap(string $niche, string $mapKey): string
    {
        $map = config("social-image-patterns.{$mapKey}", []);
        return $map[$niche] ?? $map['default'] ?? '';
    }
}
