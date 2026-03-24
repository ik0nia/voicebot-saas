<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model Tiers
    |--------------------------------------------------------------------------
    */
    'tiers' => [
        'fast' => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'max_tokens' => 500,
            'temperature' => 0.6,
        ],
        'smart' => [
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-5-20241022',
            'max_tokens' => 800,
            'temperature' => 0.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Complexity Detection
    |--------------------------------------------------------------------------
    */
    'word_count_threshold' => (int) env('ROUTING_WORD_THRESHOLD', 30),
    'short_message_threshold' => 8,
    'long_conversation_threshold' => 10,
    'long_conversation_word_min' => 15,

    /*
    |--------------------------------------------------------------------------
    | Cost-Aware Routing
    |--------------------------------------------------------------------------
    */
    'cost_budget_cents' => (int) env('ROUTING_COST_BUDGET_CENTS', 15),

    /*
    |--------------------------------------------------------------------------
    | Voice / Latency-Aware Routing
    |--------------------------------------------------------------------------
    */
    'voice_channel_types' => ['voice', 'webrtc', 'twilio', 'phone'],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    */
    'circuit_breaker' => [
        'window_minutes' => 5,
        'min_requests' => 5,
        'fail_rate_threshold' => 0.8,
        'cooldown_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Complexity Patterns (per language)
    |--------------------------------------------------------------------------
    */
    'patterns' => [
        'ro' => [
            '/\d+\s*(mp|m2|m²|metri|litri|l|kg|bucati|buc)/',
            '/recomand|suger|sfatu|ce.*alegi|ce.*iei|ce.*trebui|ce.*potrivit/u',
            '/compar|diferent|versus|sau.*mai bun|care.*mai/u',
            '/proiect|renovez|construi|izol|termoizol|amenaj/u',
            '/cum.*fac|cum.*aplic|cum.*montez|cum.*instalez/u',
            '/alternativ|inlocui|echivalent/u',
            '/buget|cost.*total|cat.*cheltuiesc|cat.*costa.*pentru/u',
            '/avantaj|dezavantaj|pro.*contra|merita/u',
            '/cat.*dureaz|termen.*livr|cand.*ajung|cand.*primesc|timp.*livr/u',
            '/garantie|retur|schimb|reclam|drept.*consum|legal/u',
            '/comanda.*special|personali|la.*comanda|servicii.*domicili|montaj/u',
            '/\?.*\?/',
        ],
        'en' => [
            '/\d+\s*(sqm|m2|liters|kg|pieces|pcs)/',
            '/recommend|suggest|advice|which.*choose|what.*need|what.*best/i',
            '/compar|differ|versus|or.*better|which.*more/i',
            '/project|renovate|build|insulate/i',
            '/how.*do|how.*apply|how.*install|how.*mount/i',
            '/alternative|replace|equivalent/i',
            '/budget|total.*cost|how.*much.*spend/i',
            '/advantage|disadvantage|pros.*cons|worth/i',
            '/how.*long|delivery.*time|when.*arrive|when.*receive/i',
            '/warranty|return|exchange|complaint|consumer.*right|legal/i',
            '/custom.*order|personalize|made.*to.*order|home.*service|installation/i',
            '/\?.*\?/',
        ],
    ],
];
