<?php

return [
    'trigram_threshold' => (float) env('PRODUCT_TRIGRAM_THRESHOLD', 0.3),

    'weights' => [
        'trigram'          => (float) env('PRODUCT_WEIGHT_TRIGRAM', 2.0),
        'word_match'       => (float) env('PRODUCT_WEIGHT_WORD_MATCH', 2.0),
        'category'         => (float) env('PRODUCT_WEIGHT_CATEGORY', 0.5),
        'attribute'        => (float) env('PRODUCT_WEIGHT_ATTRIBUTE', 1.0),
        'popularity'       => (float) env('PRODUCT_WEIGHT_POPULARITY', 0.3),
        'stock'            => (float) env('PRODUCT_WEIGHT_STOCK', 0.1),
        'all_match_bonus'  => (float) env('PRODUCT_WEIGHT_ALL_MATCH', 3.0),
        'name_all_bonus'   => (float) env('PRODUCT_WEIGHT_NAME_ALL', 2.0),
    ],

    'cache' => [
        'enabled' => (bool) env('PRODUCT_CACHE_ENABLED', true),
        'ttl_hours' => (int) env('PRODUCT_CACHE_TTL_HOURS', 12),
    ],

    'spelling' => [
        'enabled' => (bool) env('PRODUCT_SPELLING_ENABLED', true),
        'max_distance' => (int) env('PRODUCT_LEVENSHTEIN_MAX', 1),
    ],

    'analytics' => [
        'enabled' => (bool) env('PRODUCT_ANALYTICS_ENABLED', true),
    ],

    // Minimum semantic score to return a result (below = filtered out)
    'min_confidence_score' => (float) env('PRODUCT_MIN_CONFIDENCE', 5),

    // Debug mode: logs intent parsing, scoring details, filtering decisions
    'debug' => (bool) env('PRODUCT_SEARCH_DEBUG', false),
];
