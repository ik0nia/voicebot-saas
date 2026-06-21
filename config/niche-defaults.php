<?php

return [
    // Suggested dont_rules per niche — afișate ca chips în UI la setup.
    // Toate sunt opt-in; tenant alege care vrea să le aplice.
    'dont_rules_per_niche' => [
        'magazin-online' => [
            'Nu promite termene de livrare fără verificare.',
            'Nu oferi coduri de reducere care nu sunt active în sistem.',
            'Nu cere numărul cardului sau CVV-ul — plata se face doar pe site.',
            'Nu vorbi de rău alte magazine sau produse concurente.',
        ],
        'servicii-medicale' => [
            'Nu da diagnostice. Recomandă întotdeauna consult cu medicul autorizat.',
            'Nu sugera medicamente sau tratamente fără rețetă.',
            'Confidențialitate: nu solicita date medicale sensibile pe chat.',
        ],
        'servicii-juridice' => [
            'Nu da consultanță juridică oficială. Recomandă programare cu avocat.',
            'Nu evalua cazuri specifice — fiecare situație necesită analiză detaliată.',
        ],
        'imobiliare' => [
            'Nu garanta preturile fără confirmare scrisă.',
            'Nu promite vizionări fără verificarea disponibilității agentului.',
            'Nu folosi tehnici de presiune („oferta valabilă doar azi").',
        ],
        'restaurant' => [
            'Nu garanta disponibilitatea mesei fără confirmare booking.',
            'Pentru rezervări > 8 persoane, transferă la operator.',
            'Nu prezenta ingrediente fără confirmare actualizată — alergii sunt critice.',
        ],
        'auto' => [
            'Nu propune service sau piese fără verificare VIN.',
            'Nu da diagnostic de defect — recomandă verificare în atelier.',
        ],
    ],

    // Tone presets per niche — folosite la setup wizard.
    'tone_presets' => [
        'magazin-online' => ['length' => 'short', 'register' => 'tu', 'emoji_ok' => true],
        'servicii-medicale' => ['length' => 'medium', 'register' => 'dvs', 'emoji_ok' => false],
        'servicii-juridice' => ['length' => 'medium', 'register' => 'dvs', 'emoji_ok' => false],
        'imobiliare' => ['length' => 'medium', 'register' => 'dvs', 'emoji_ok' => false],
        'restaurant' => ['length' => 'short', 'register' => 'tu', 'emoji_ok' => true],
        'auto' => ['length' => 'medium', 'register' => 'tu', 'emoji_ok' => false],
    ],
];
