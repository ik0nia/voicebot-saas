<?php

return [
    'similarity_threshold' => (float) env('KNOWLEDGE_SIMILARITY_THRESHOLD', 0.62),
    'fts_weight' => (float) env('KNOWLEDGE_FTS_WEIGHT', 1.0),
    'max_context_chars' => (int) env('KNOWLEDGE_MAX_CONTEXT_CHARS', 8000),
    'embedding_model' => env('KNOWLEDGE_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'cache_ttl_hours' => (int) env('KNOWLEDGE_CACHE_TTL_HOURS', 24),

    'query_expansion' => [
        'enabled' => (bool) env('KNOWLEDGE_QUERY_EXPANSION', true),
        'max_variants' => 3,
        'llm_rewrite' => (bool) env('KNOWLEDGE_LLM_QUERY_REWRITE', true),
        'llm_rewrite_model' => env('KNOWLEDGE_LLM_REWRITE_MODEL', 'gpt-4o-mini'),
    ],

    // Language-specific synonym dictionaries for FTS expansion
    'synonyms' => [
        'ro' => [
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
        'en' => [
            'price' => 'cost pricing rate fee charge amount',
            'delivery' => 'shipping transport courier dispatch express',
            'warranty' => 'guarantee warranty service repair coverage',
            'payment' => 'pay checkout card cash invoice transfer installment',
            'hours' => 'schedule hours open closed business working',
            'contact' => 'phone email address location office reach',
            'discount' => 'sale offer promotion coupon voucher deal',
            'stock' => 'available availability inventory in-stock out-of-stock',
            'return' => 'refund exchange return send-back policy',
            'size' => 'dimension measurement width length height diameter',
            'color' => 'colour shade finish variant option',
            'install' => 'setup mount assemble apply attach configure',
            'weight' => 'weight mass heavy kilogram pound',
            'material' => 'material composition made-of fabric substance',
            'compatible' => 'compatible fits works-with suitable matching',
            'order' => 'order purchase buy cart checkout basket',
            'quality' => 'quality durable reliable sturdy premium',
            'alternative' => 'alternative similar replacement equivalent substitute',
            'complaint' => 'complaint issue problem concern dissatisfied',
            'recommend' => 'recommend suggest advise guide best choice',
            'specs' => 'specifications features characteristics details datasheet',
        ],
    ],

    'reranking' => [
        'enabled' => (bool) env('KNOWLEDGE_RERANKING', true),
        'candidates' => 20,
        'model' => 'gpt-4o-mini',
        // Conditional reranking: only rerank in the "uncertain zone"
        // Skip reranking if top similarity is above max_threshold (confident enough)
        // Skip reranking if top similarity is below min_threshold (too poor to help)
        'min_threshold' => (float) env('KNOWLEDGE_RERANKING_MIN', 0.58),
        'max_threshold' => (float) env('KNOWLEDGE_RERANKING_MAX', 0.85),
    ],

    // Source-type specific chunk sizes (in tokens)
    'chunking' => [
        'faq' => 128,
        'manual' => 256,
        'scan' => 384,
        'upload' => 512,
        'connector' => 512,
        'agent' => 512,
    ],

    // Source-type specific chunk overlap ratios
    // FAQ: no overlap (self-contained Q&A)
    // Scans: higher overlap (web content needs more continuity)
    'chunk_overlap' => [
        'faq' => 0.0,
        'manual' => 0.10,
        'scan' => 0.15,
        'upload' => 0.125,
        'connector' => 0.10,
        'agent' => 0.10,
    ],

    // Parent-child retrieval: fetch adjacent chunks when a mid-document chunk is found
    'parent_child' => [
        'enabled' => (bool) env('KNOWLEDGE_PARENT_CHILD', true),
        'max_siblings' => 2, // max extra chunks to fetch per result
    ],

    'max_chunks_per_document' => (int) env('KNOWLEDGE_MAX_CHUNKS_PER_DOC', 3),
    'max_document_tokens' => (int) env('KNOWLEDGE_MAX_DOC_TOKENS', 100000),

    // Supported FTS language configs (PostgreSQL regconfig names)
    'fts_languages' => [
        'ro' => 'romanian',
        'en' => 'english',
        'de' => 'german',
        'fr' => 'french',
        'es' => 'spanish',
    ],
    'fts_default' => 'romanian',
];
