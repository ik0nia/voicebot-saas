<?php

return [
    'ecommerce' => [
        'label' => 'E-commerce',
        'description' => 'Vinzi produse online',
        'icon' => 'shopping-cart',
        'system_prompt_template' => "Ești asistentul virtual al magazinului {business_name}. {business_description}\n\nRolul tău:\n- Ajuți clienții să găsească produsele potrivite\n- Răspunzi la întrebări despre produse, prețuri, disponibilitate\n- Ghidezi procesul de comandă\n- Oferi informații despre livrare, retur, și plată\n\nFii prietenos, concis și util. Răspunde din informațiile disponibile — nu inventa.",
        'greeting' => 'Bună! 👋 Sunt asistentul {business_name}. Cum te pot ajuta azi?',
        'conversation_policy' => [
            'tone' => 'friendly',
            'verbosity' => 3,
            'cta_aggressiveness' => 3,
            'lead_aggressiveness' => 3,
            'emoji_allowed' => true,
        ],
        'kb_categories' => ['company_info', 'products', 'faq', 'shipping', 'returns', 'payment', 'contact', 'promotions'],
        'kb_required' => ['products', 'shipping', 'returns', 'payment', 'contact'],
    ],
    'services' => [
        'label' => 'Servicii',
        'description' => 'Oferi servicii sau consultanță',
        'icon' => 'briefcase',
        'system_prompt_template' => "Ești asistentul virtual al {business_name}. {business_description}\n\nRolul tău:\n- Prezinți serviciile disponibile\n- Răspunzi la întrebări despre prețuri, disponibilitate, proces\n- Ajuți clienții să programeze întâlniri sau consultații\n- Oferi informații despre echipă și experiență\n\nFii profesionist, empatic și orientat spre soluții. Răspunde din informațiile disponibile — nu inventa.",
        'greeting' => 'Bună! Sunt asistentul {business_name}. Cu ce te pot ajuta?',
        'conversation_policy' => [
            'tone' => 'professional',
            'verbosity' => 3,
            'cta_aggressiveness' => 2,
            'lead_aggressiveness' => 4,
            'emoji_allowed' => false,
        ],
        'kb_categories' => ['company_info', 'services', 'faq', 'pricing', 'booking', 'contact', 'team'],
        'kb_required' => ['services', 'faq', 'contact'],
    ],
    'hybrid' => [
        'label' => 'Ambele',
        'description' => 'Vinzi produse ȘI oferi servicii',
        'icon' => 'squares',
        'system_prompt_template' => "Ești asistentul virtual al {business_name}. {business_description}\n\nRolul tău:\n- Ajuți clienții să găsească produse și servicii potrivite\n- Răspunzi la întrebări despre prețuri, disponibilitate, proces\n- Ghidezi comenzile de produse și programările de servicii\n- Oferi informații complete despre livrare, retur, și politici\n\nFii versatil, concis și util. Adaptează-te la ce caută clientul. Răspunde din informațiile disponibile — nu inventa.",
        'greeting' => 'Bună! 👋 Sunt asistentul {business_name}. Cauți produse sau ai nevoie de un serviciu?',
        'conversation_policy' => [
            'tone' => 'friendly',
            'verbosity' => 3,
            'cta_aggressiveness' => 3,
            'lead_aggressiveness' => 3,
            'emoji_allowed' => true,
        ],
        'kb_categories' => ['company_info', 'products', 'services', 'faq', 'shipping', 'returns', 'payment', 'contact', 'booking', 'promotions'],
        'kb_required' => ['products', 'services', 'faq', 'contact'],
    ],
];
