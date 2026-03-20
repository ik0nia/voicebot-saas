<?php

return [
    'starter' => [
        'name' => 'Starter',
        'price_monthly' => 29_00,
        'price_yearly' => 290_00,
        'currency' => 'USD',
        'features' => [
            'bots' => 1,
            'calls_per_month' => 500,
            'team_members' => 2,
            'analytics_retention_days' => 30,
            'support' => 'email',
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'price_monthly' => 99_00,
        'price_yearly' => 990_00,
        'currency' => 'USD',
        'features' => [
            'bots' => 10,
            'calls_per_month' => 5000,
            'team_members' => 10,
            'analytics_retention_days' => 90,
            'support' => 'priority',
        ],
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'price_monthly' => 299_00,
        'price_yearly' => 2990_00,
        'currency' => 'USD',
        'features' => [
            'bots' => -1,
            'calls_per_month' => -1,
            'team_members' => -1,
            'analytics_retention_days' => 365,
            'support' => 'dedicated',
        ],
    ],
];
