<?php

return [
    'starter' => [
        'name' => 'Starter',
        'price_monthly' => 99,
        'price_yearly' => 79,
        'minutes' => 500,
        'bots' => 1,
        'channels' => 1,
        'overage_per_minute' => 0.15,
        'features' => [
            'Transcrieri automate',
            'Suport email',
            'Rapoarte de bază',
        ],
    ],
    'professional' => [
        'name' => 'Profesional',
        'price_monthly' => 299,
        'price_yearly' => 239,
        'minutes' => 2000,
        'bots' => 5,
        'channels' => 999,
        'overage_per_minute' => 0.10,
        'features' => [
            'Toate canalele',
            'Analiză de sentiment',
            'Suport prioritar 24/7',
            'Integrări CRM',
            'Dashboard avansat',
            'API access',
        ],
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price_monthly' => 0,
        'price_yearly' => 0,
        'minutes' => 999999,
        'bots' => 999,
        'channels' => 999,
        'overage_per_minute' => 0,
        'features' => [
            'Minute nelimitate',
            'SLA 99.99%',
            'Manager dedicat',
            'Onboarding personalizat',
            'Hosting dedicat',
            'Suport telefonic 24/7',
        ],
    ],
];
