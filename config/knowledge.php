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
        'llm_rewrite' => (bool) env('KNOWLEDGE_LLM_QUERY_REWRITE', false),
        'llm_rewrite_model' => env('KNOWLEDGE_LLM_REWRITE_MODEL', 'gpt-4o-mini'),
    ],

    // Romanian synonym dictionary for FTS expansion
    'synonyms' => [
        'pret' => 'cost tarif pret',
        'livrare' => 'transport expediere curier livrare',
        'garantie' => 'garantie retur schimb',
        'plata' => 'platesc achit card numerar plata',
        'program' => 'orar deschis inchis program',
        'contact' => 'telefon email adresa locatie contact',
        'reducere' => 'oferta promotie discount reducere',
        'stoc' => 'disponibil disponibilitate stoc',
        'retur' => 'returnare schimb garantie retur',
        'dimensiune' => 'marime dimensiune grosime latime lungime',
        'culoare' => 'culoare nuanta finisaj',
        'instalare' => 'montaj montare instalare aplicare',
        'greutate' => 'greutate masa kilogram',
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

    'max_chunks_per_document' => (int) env('KNOWLEDGE_MAX_CHUNKS_PER_DOC', 3),

    'max_document_tokens' => (int) env('KNOWLEDGE_MAX_DOC_TOKENS', 100000),
];
