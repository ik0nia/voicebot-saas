<?php

/*
|--------------------------------------------------------------------------
| Widget Theme Presets
|--------------------------------------------------------------------------
|
| Catalog de teme pentru widget-ul de chat embeddable. Tenantul alege un
| preset în editor (dashboard.bots.channels.chatbot-setup); preset-ul
| determină accent + accent_soft (pentru gradient) + bubble_radius.
|
| Cheia 'custom' nu apare în UI ca preset — e folosită ca sentinel când
| tenantul suprascrie cu o culoare manuală via color picker.
|
*/

return [

    'default' => 'coral',

    'presets' => [

        'coral' => [
            'name' => 'Coral cald',
            'description' => 'Energic, prietenos — potrivit pentru retail, beauty, food.',
            'accent' => '#f97316',
            'accent_soft' => '#fb923c',
            'bubble_radius' => '18px',
        ],

        'corporate_blue' => [
            'name' => 'Albastru corporate',
            'description' => 'Profesional, încredere — potrivit pentru finanțe, B2B, servicii.',
            'accent' => '#1e40af',
            'accent_soft' => '#2563eb',
            'bubble_radius' => '12px',
        ],

        'natural_green' => [
            'name' => 'Verde natural',
            'description' => 'Sănătate, eco, wellness — potrivit pentru farmacie, bio, medical.',
            'accent' => '#047857',
            'accent_soft' => '#10b981',
            'bubble_radius' => '16px',
        ],

        'violet_premium' => [
            'name' => 'Violet premium',
            'description' => 'Premium, creativ — potrivit pentru lux, design, servicii premium.',
            'accent' => '#7c3aed',
            'accent_soft' => '#a855f7',
            'bubble_radius' => '16px',
        ],

        'playful_rose' => [
            'name' => 'Roz playful',
            'description' => 'Jucăuș, warm — potrivit pentru beauty, copii, lifestyle.',
            'accent' => '#db2777',
            'accent_soft' => '#ec4899',
            'bubble_radius' => '22px',
        ],

        'teal_modern' => [
            'name' => 'Teal modern',
            'description' => 'Modern, tech-friendly — potrivit pentru SaaS, digital, servicii online.',
            'accent' => '#0f766e',
            'accent_soft' => '#14b8a6',
            'bubble_radius' => '14px',
        ],

        'indigo_tech' => [
            'name' => 'Indigo tech',
            'description' => 'Tehnic, inovator — potrivit pentru IT, agenții digitale, startup.',
            'accent' => '#4338ca',
            'accent_soft' => '#6366f1',
            'bubble_radius' => '12px',
        ],

        'gold_lux' => [
            'name' => 'Gold lux',
            'description' => 'Elegant, premium — potrivit pentru bijuterii, ceasuri, avocatură.',
            'accent' => '#a16207',
            'accent_soft' => '#ca8a04',
            'bubble_radius' => '10px',
        ],

        'mono_dark' => [
            'name' => 'Mono dark',
            'description' => 'Minimal, neutru — merge pe orice site fără a atrage excesiv atenția.',
            'accent' => '#18181b',
            'accent_soft' => '#3f3f46',
            'bubble_radius' => '10px',
        ],

        'slate_elegant' => [
            'name' => 'Gri elegant',
            'description' => 'Discret, sobru — potrivit pentru servicii profesionale, contabilitate.',
            'accent' => '#334155',
            'accent_soft' => '#64748b',
            'bubble_radius' => '12px',
        ],

        'classic_red' => [
            'name' => 'Roșu clasic',
            'description' => 'Asertiv, direct — tema default istorică a widget-ului.',
            'accent' => '#991b1b',
            'accent_soft' => '#dc2626',
            'bubble_radius' => '16px',
        ],

    ],

];
