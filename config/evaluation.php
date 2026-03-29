<?php

/**
 * RAG + System evaluation test cases.
 *
 * Each case is run through the full pipeline (intent → RAG → LLM)
 * and checked for expected keywords in the response.
 *
 * Types: product, knowledge, service, greeting, order
 *
 * Usage: php artisan rag:evaluate --bot=67
 */
return [
    // ─── Knowledge queries ───
    [
        'query' => 'Care sunt metodele de plată acceptate?',
        'must_contain' => ['card', 'plat'],
        'type' => 'knowledge',
    ],
    [
        'query' => 'Cum funcționează livrarea?',
        'must_contain' => ['livr'],
        'type' => 'knowledge',
    ],
    [
        'query' => 'Care e programul de lucru?',
        'must_contain' => ['program', 'or'],
        'type' => 'knowledge',
    ],
    [
        'query' => 'Aveți garanție?',
        'must_contain' => ['garanți', 'garant', 'retur'],
        'type' => 'knowledge',
    ],
    [
        'query' => 'Cum pot returna un produs?',
        'must_contain' => ['retur', 'schimb'],
        'type' => 'knowledge',
    ],

    // ─── Product queries ───
    [
        'query' => 'Cât costă livrarea?',
        'must_contain' => ['livr', 'lei', 'cost', 'gratu'],
        'type' => 'product',
    ],
    [
        'query' => 'Ce produse aveți în promoție?',
        'must_contain' => ['promo', 'reduc', 'ofer'],
        'type' => 'product',
    ],

    // ─── Service queries ───
    [
        'query' => 'Cum vă pot contacta?',
        'must_contain' => ['telefon', 'email', 'contact'],
        'type' => 'service',
    ],

    // ─── Greetings (should NOT trigger RAG) ───
    [
        'query' => 'Bună ziua!',
        'must_contain' => ['bun', 'salut', 'ajut'],
        'type' => 'greeting',
    ],
    [
        'query' => 'Mulțumesc!',
        'must_contain' => ['plăcer', 'dispozi', 'ajut'],
        'type' => 'greeting',
    ],
];
