<?php

return [
    'similarity_threshold' => (float) env('KNOWLEDGE_SIMILARITY_THRESHOLD', 0.55),
    'fts_weight' => (float) env('KNOWLEDGE_FTS_WEIGHT', 1.5),
    'max_context_chars' => (int) env('KNOWLEDGE_MAX_CONTEXT_CHARS', 8000),
    'embedding_model' => env('KNOWLEDGE_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'cache_ttl_hours' => (int) env('KNOWLEDGE_CACHE_TTL_HOURS', 24),

    'query_expansion' => [
        'enabled' => (bool) env('KNOWLEDGE_QUERY_EXPANSION', true),
        'max_variants' => 3,
        'llm_rewrite' => (bool) env('KNOWLEDGE_LLM_QUERY_REWRITE', true),
        'llm_rewrite_model' => env('KNOWLEDGE_LLM_REWRITE_MODEL', 'gpt-4o-mini'),
    ],

    // Romanian synonym dictionary for FTS expansion (25+ groups)
    'synonyms' => [
        'pret' => 'cost tarif pret valoare suma',
        'livrare' => 'transport expediere curier livrare shipping',
        'garantie' => 'garantie service reparatie piese',
        'plata' => 'platesc achit card numerar plata rate factura transfer',
        'program' => 'orar deschis inchis program ore lucru',
        'contact' => 'telefon email adresa locatie contact sediu',
        'reducere' => 'oferta promotie discount reducere cupon voucher',
        'stoc' => 'disponibil disponibilitate stoc exista aveti',
        'retur' => 'returnare schimb inapoi ramburs retur',
        'dimensiune' => 'marime dimensiune grosime latime lungime inaltime diametru',
        'culoare' => 'culoare nuanta finisaj model varianta',
        'instalare' => 'montaj montare instalare aplicare fixare',
        'greutate' => 'greutate masa kilogram greu',
        'material' => 'material compozitie fabricat din alcatuire',
        'compatibil' => 'compatibil potrivit functioneaza merge se potriveste',
        'termen' => 'termen durata cat dureaza timp asteptare',
        'comanda' => 'comanda cumparatura achizitie plasare cos',
        'calitate' => 'calitate bun rezistent durabil fiabil',
        'alternativa' => 'alternativa similar inlocuitor echivalent asemanator',
        'reclamatie' => 'reclamatie plangere problema nemultumire sesizare',
        'recomandare' => 'recomandare sugestie sfat ghid alegere',
        'specificatii' => 'specificatii tehnice caracteristici parametri detalii fisa',
        'servicii' => 'servicii prestari consultanta asistenta suport',
        'rezervare' => 'rezervare programare booking appointment vizita',
        'factura' => 'factura bon chitanta fiscal document',
    ],

    'reranking' => [
        'enabled' => (bool) env('KNOWLEDGE_RERANKING', true),
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
