<?php

return [
    // Sentry DSN — gol = off. Setat în env.
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // Release tracking — preluăm SHA-ul commit-ului curent dacă există
    // (setat de deploy script) sau APP_VERSION. Fără asta, errors din versiuni
    // diferite se amestecă în Sentry și hot-fix-urile par să nu rezolve nimic.
    'release' => env('SENTRY_RELEASE', env('APP_VERSION', null)),

    'environment' => env('APP_ENV', 'production'),

    // Performance sampling — 10% default ca să nu balonăm cota Sentry.
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // PII protection — Sentry nu primește IP/headers detaliate decât dacă
    // explicit allow.
    'send_default_pii' => false,

    'breadcrumbs' => [
        'logs' => true,
        'cache' => false,
        'livewire' => true,
        'sql_queries' => false, // pot conține PII în where clauses
        'sql_bindings' => false,
        'queue_info' => true,
    ],
];
