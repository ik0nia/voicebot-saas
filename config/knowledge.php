<?php

return [
    'similarity_threshold' => (float) env('KNOWLEDGE_SIMILARITY_THRESHOLD', 0.68),
    'fts_weight' => (float) env('KNOWLEDGE_FTS_WEIGHT', 1.5),
    'max_context_chars' => (int) env('KNOWLEDGE_MAX_CONTEXT_CHARS', 6000),
    'embedding_model' => env('KNOWLEDGE_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'cache_ttl_hours' => (int) env('KNOWLEDGE_CACHE_TTL_HOURS', 24),

    'query_expansion' => [
        'enabled' => (bool) env('KNOWLEDGE_QUERY_EXPANSION', true),
        'max_variants' => 3,
    ],

    'reranking' => [
        'enabled' => (bool) env('KNOWLEDGE_RERANKING', false),
        'candidates' => 20,
        'model' => 'gpt-4o-mini',
    ],

    'chunking' => [
        'faq' => 128,
        'manual' => 256,
        'scan' => 384,
        'upload' => 512,
        'connector' => 512,
        'agent' => 512,
    ],

    'max_document_tokens' => (int) env('KNOWLEDGE_MAX_DOC_TOKENS', 100000),
];
