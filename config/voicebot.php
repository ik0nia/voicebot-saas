<?php

return [
    'max_call_duration_minutes' => (int) env('VOICEBOT_MAX_CALL_MINUTES', 30),
    'warning_before_end_minutes' => 5,
    'stale_session_minutes' => 30,

    'cost' => [
        // Real measured costs (March 2026: $13.45 OpenAI / 94.3 min)
        'openai_realtime_per_minute' => (float) env('VOICEBOT_OPENAI_COST_PER_MINUTE', 0.14),
        // Real measured: $5.52 ElevenLabs / 43.2 min cloned
        'elevenlabs_per_minute' => (float) env('VOICEBOT_ELEVENLABS_COST_PER_MINUTE', 0.13),
    ],

    'token_limit' => [
        'max_instructions_tokens' => (int) env('VOICEBOT_MAX_INSTRUCTIONS_TOKENS', 12000),
    ],

    'voices' => [
        'alloy' => 'Alloy (neutral)',
        'echo' => 'Echo (male)',
        'fable' => 'Fable (British)',
        'onyx' => 'Onyx (deep male)',
        'nova' => 'Nova (female)',
        'shimmer' => 'Shimmer (female)',
    ],

    'greeting' => [
        'morning' => 'Bună dimineața',   // 06-12
        'afternoon' => 'Bună ziua',       // 12-18
        'evening' => 'Bună seara',        // 18-06
    ],

    'sentiment' => [
        'retry_backoff' => [10, 30, 120],
        'max_retries' => 3,
    ],
];
