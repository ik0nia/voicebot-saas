<?php

return [
    'trigram_threshold' => (float) env('PRODUCT_TRIGRAM_THRESHOLD', 0.25),

    'weights' => [
        'trigram' => (float) env('PRODUCT_WEIGHT_TRIGRAM', 2.0),
        'word_match' => (float) env('PRODUCT_WEIGHT_WORD_MATCH', 2.0),
        'category' => (float) env('PRODUCT_WEIGHT_CATEGORY', 0.5),
        'popularity' => (float) env('PRODUCT_WEIGHT_POPULARITY', 0.3),
        'stock' => (float) env('PRODUCT_WEIGHT_STOCK', 0.1),
    ],

    'cache' => [
        'enabled' => (bool) env('PRODUCT_CACHE_ENABLED', true),
        'ttl_hours' => (int) env('PRODUCT_CACHE_TTL_HOURS', 12),
    ],

    'spelling' => [
        'enabled' => (bool) env('PRODUCT_SPELLING_ENABLED', true),
        'max_distance' => (int) env('PRODUCT_LEVENSHTEIN_MAX', 2),
    ],

    'analytics' => [
        'enabled' => (bool) env('PRODUCT_ANALYTICS_ENABLED', true),
    ],

    'fallback_count' => 5,
];
