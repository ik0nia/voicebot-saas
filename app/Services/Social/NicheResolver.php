<?php

namespace App\Services\Social;

/**
 * Extracts a pattern-catalog niche slug and the "key message" for a social post
 * from the post's metadata blob. The old GeminiContentService path picked random
 * style presets; the new orchestrator needs semantic inputs (niche + message).
 */
final class NicheResolver
{
    /**
     * @param array $metadata Post metadata (topic, seed, category, image_concept, cta, ...).
     * @return array{niche: string, key_message: ?string}
     */
    public function resolve(array $metadata): array
    {
        return [
            'niche' => $this->inferNiche($metadata),
            'key_message' => $this->inferKeyMessage($metadata),
        ];
    }

    private function inferNiche(array $metadata): string
    {
        $haystack = mb_strtolower(
            trim(($metadata['seed'] ?? '') . ' ' . ($metadata['category'] ?? '') . ' ' . ($metadata['topic'] ?? ''))
        );

        if ($haystack === '') {
            return 'default';
        }

        foreach ($this->aliases() as $needle => $slug) {
            if (str_contains($haystack, $needle)) {
                return $slug;
            }
        }

        return 'default';
    }

    private function inferKeyMessage(array $metadata): ?string
    {
        $topic = trim((string) ($metadata['topic'] ?? ''));
        $cta = trim((string) ($metadata['cta'] ?? ''));

        if ($topic !== '') {
            return mb_substr($topic, 0, 220);
        }
        if ($cta !== '') {
            return mb_substr($cta, 0, 220);
        }
        return null;
    }

    /**
     * Substring → niche slug map. Lowercase, no diacritics. First match wins, so more-specific
     * terms (e.g. "cabinet veterinar") must come before shorter ones (e.g. "cabinet").
     */
    private function aliases(): array
    {
        return [
            // verticale specific
            'veterinar' => 'veterinar',
            'veterinara' => 'veterinar',
            'veterinară' => 'veterinar',
            'stomato' => 'stomatolog',
            'dentist' => 'stomatolog',
            'dentar' => 'stomatolog',
            'contabil' => 'contabil',
            'contabilit' => 'contabil',
            'avocat' => 'avocat',
            'avocatur' => 'avocat',
            'juridic' => 'avocat',
            'salon' => 'salon',
            'frizer' => 'salon',
            'beauty' => 'salon',
            'restaurant' => 'restaurant',
            'bistrou' => 'restaurant',
            'delivery' => 'restaurant',
            'imobil' => 'imobiliare',
            'real estate' => 'imobiliare',
            'service auto' => 'auto',
            'atelier auto' => 'auto',
            'workshop auto' => 'auto',
            'auto' => 'auto',
            'cofet' => 'cofetar',
            'brutar' => 'cofetar',
            'patiser' => 'cofetar',
            'psiholog' => 'psiholog',
            'psihoterap' => 'psiholog',
            'optica' => 'optica',
            'optică' => 'optica',
            'optician' => 'optica',
            'limbi' => 'scoala_limbi',
            'cursuri' => 'scoala_limbi',
            'scoala de limbi' => 'scoala_limbi',
            'turism' => 'turism',
            'travel' => 'turism',
            'pensiune' => 'pensiune',
            'hotel' => 'pensiune',
            'curat' => 'curatenie',
            'cleaning' => 'curatenie',
            'notar' => 'notar',
            'medic' => 'medic',
            'medical' => 'medic',
            'consult' => 'consultant',
        ];
    }
}
