<?php

namespace App\Services;

/**
 * Normalizes user queries for cache key generation.
 * Similar queries should produce the same key to maximize cache hits.
 */
class QueryNormalizerService
{
    /** Common Romanian stopwords that don't affect search intent */
    private const STOPWORDS = [
        'un', 'o', 'de', 'la', 'pe', 'cu', 'in', 'din', 'si', 'sau',
        'ca', 'ce', 'cum', 'imi', 'mi', 'ma', 'te', 'se', 'ne', 'va',
        'ai', 'are', 'am', 'au', 'este', 'sunt', 'era', 'fie', 'fi',
        'pentru', 'care', 'mai', 'nu', 'da', 'sa', 'as', 'ar',
        'asa', 'daca', 'cand', 'unde', 'cat', 'cate', 'cati',
        'buna', 'salut', 'multumesc', 'mersi', 'rog',
        'vreau', 'doresc', 'as', 'vrea', 'poti', 'puteti',
    ];

    /**
     * Normalize a query to a canonical cache-friendly form.
     */
    public function normalize(string $query): string
    {
        $q = mb_strtolower(trim($query));

        // Remove diacritics
        $q = str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'a', 'a', 'i', 's', 't'],
            $q
        );

        // Remove punctuation
        $q = preg_replace('/[^\w\s]/u', '', $q);

        // Tokenize and remove stopwords
        $words = preg_split('/\s+/', $q);
        $words = array_filter($words, fn($w) => !in_array($w, self::STOPWORDS) && mb_strlen($w) > 1);

        // Sort for order-independent matching ("pret livrare" == "livrare pret")
        sort($words);

        return implode(' ', $words);
    }

    /**
     * Generate a cache key for a bot + normalized query.
     */
    public function cacheKey(int $botId, string $query): string
    {
        $normalized = $this->normalize($query);
        return "response_{$botId}_" . md5($normalized);
    }
}
