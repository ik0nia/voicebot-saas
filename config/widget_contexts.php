<?php

/**
 * Widget contexts — per-niche contextual UX for the embedded chat.
 *
 * Given a bot's niche_slug + a detected page_type (product, category,
 * cart, booking, hospitality, home, general), return the opening
 * message and quick-reply buttons the widget should render.
 *
 * Shape per entry:
 *   [
 *     'opening' => 'Message shown above quick replies on first open',
 *     'quick_replies' => [
 *       ['label' => 'Button label', 'text' => 'What the user actually sends'],
 *       ...
 *     ],
 *   ]
 *
 * Resolver order (see App\Services\Widget\WidgetContextResolver):
 *   1. channel->config['widget_contexts'][{page_type}] — tenant override
 *   2. widget_contexts.{niche_slug}.{page_type} — niche default
 *   3. widget_contexts._default.{page_type} — universal fallback
 *
 * Everything is OPTIONAL — a niche/page-type without an entry falls
 * back silently and the widget renders with no quick replies.
 *
 * Keep wording concise, premium, and in Romanian. Buttons are CTAs,
 * not FAQ. Users who want the full FAQ still have freeform chat.
 */

return [

    // ────────────────────────────────────────────
    // Universal fallback — used when niche has no specific entry
    // ────────────────────────────────────────────
    '_default' => [
        'general' => [
            'opening' => 'Bună! Cu ce te pot ajuta astăzi?',
            'quick_replies' => [
                ['label' => 'Întrebări frecvente', 'text' => 'Vreau să văd întrebările frecvente.'],
                ['label' => 'Vorbesc cu cineva', 'text' => 'Vreau să vorbesc cu un operator.'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Ecommerce — magazin-online
    // ────────────────────────────────────────────
    'magazin-online' => [
        'product' => [
            'opening' => 'Ai nevoie de mai multe detalii despre acest produs?',
            'quick_replies' => [
                ['label' => 'Este în stoc?', 'text' => 'Este în stoc?'],
                ['label' => 'Cât durează livrarea?', 'text' => 'Cât durează livrarea?'],
                ['label' => 'Există reduceri?', 'text' => 'Există reduceri sau coduri promo active?'],
                ['label' => 'E potrivit pentru mine?', 'text' => 'Cum știu dacă acest produs e potrivit pentru mine?'],
            ],
        ],
        'category' => [
            'opening' => 'Cauți ceva anume din această categorie? Te ajut să alegi rapid.',
            'quick_replies' => [
                ['label' => 'Cele mai populare', 'text' => 'Arată-mi cele mai populare produse din categoria asta.'],
                ['label' => 'Recomandare pe buget', 'text' => 'Recomandă-mi în funcție de buget.'],
                ['label' => 'Cele mai bine cotate', 'text' => 'Care sunt cele mai bine cotate opțiuni?'],
            ],
        ],
        'cart' => [
            'opening' => 'Văd că ai produse în coș. Vrei să verific dacă mai lipsește ceva?',
            'quick_replies' => [
                ['label' => 'Îmi lipsesc accesorii?', 'text' => 'Am toate accesoriile de care am nevoie?'],
                ['label' => 'Produsele sunt compatibile?', 'text' => 'Produsele din coș sunt compatibile între ele?'],
                ['label' => 'Ai o variantă mai bună?', 'text' => 'Există o variantă mai bună la preț similar?'],
                ['label' => 'Cost livrare?', 'text' => 'Cât costă livrarea pentru comanda asta?'],
            ],
        ],
        'general' => [
            'opening' => 'Bună! Caut eu produsul potrivit pentru tine. Ce cauți?',
            'quick_replies' => [
                ['label' => 'Caut un cadou', 'text' => 'Caut un cadou.'],
                ['label' => 'Livrare rapidă', 'text' => 'Ce produse se livrează astăzi?'],
                ['label' => 'Politica de retur', 'text' => 'Care e politica de retur?'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Ecommerce — florării
    // ────────────────────────────────────────────
    'florarii' => [
        'product' => [
            'opening' => 'Te pot ajuta cu detalii despre acest buchet?',
            'quick_replies' => [
                ['label' => 'Livrare azi/mâine?', 'text' => 'Puteți livra astăzi sau mâine?'],
                ['label' => 'Pot personaliza?', 'text' => 'Pot să adaug un mesaj sau să personalizez aranjamentul?'],
                ['label' => 'Există alternative?', 'text' => 'Aveți alternative la acest buchet?'],
            ],
        ],
        'general' => [
            'opening' => 'Pentru ce ocazie cauți flori? Îți propun câteva opțiuni potrivite.',
            'quick_replies' => [
                ['label' => 'Aniversare', 'text' => 'Caut un buchet pentru o aniversare.'],
                ['label' => 'Evenimente', 'text' => 'Organizez un eveniment și am nevoie de aranjamente.'],
                ['label' => 'Pentru înmormântare', 'text' => 'Am nevoie de coroană / jerbă pentru o înmormântare.'],
                ['label' => 'Livrare astăzi', 'text' => 'Ce flori pot primi astăzi?'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Booking engines — medical / dental / vet / salon / psi
    // ────────────────────────────────────────────
    'stomatologie' => [
        'booking' => [
            'opening' => 'Vrei primul loc liber sau preferi o zi/oră anume?',
            'quick_replies' => [
                ['label' => 'Primul loc liber', 'text' => 'Vreau primul loc disponibil.'],
                ['label' => 'Am durere — urgent', 'text' => 'Am durere de dinți, pot veni astăzi sau mâine?'],
                ['label' => 'Mâine dimineață', 'text' => 'Vreau o programare mâine dimineață.'],
                ['label' => 'După ora 17:00', 'text' => 'Vreau o programare după ora 17:00.'],
            ],
        ],
        'general' => [
            'opening' => 'Te ajut cu o programare rapidă. Ce procedură dorești?',
            'quick_replies' => [
                ['label' => 'Consultație', 'text' => 'Vreau o consultație.'],
                ['label' => 'Detartraj', 'text' => 'Vreau detartraj.'],
                ['label' => 'Urgență', 'text' => 'Am o urgență, pot veni astăzi?'],
                ['label' => 'Preț tratament', 'text' => 'Care e prețul pentru tratamentul meu?'],
            ],
        ],
    ],

    'medical' => [
        'booking' => [
            'opening' => 'Îți pot propune primele intervale disponibile. Ce specialitate?',
            'quick_replies' => [
                ['label' => 'Primul loc liber', 'text' => 'Vreau prima programare disponibilă.'],
                ['label' => 'Mâine dimineață', 'text' => 'Vreau mâine dimineață.'],
                ['label' => 'Doctor anume', 'text' => 'Vreau la un doctor specific.'],
                ['label' => 'Control', 'text' => 'Vreau un control de specialitate.'],
            ],
        ],
        'general' => [
            'opening' => 'Te pot ajuta cu o programare. Ce specialitate cauți?',
            'quick_replies' => [
                ['label' => 'Consult general', 'text' => 'Vreau un consult general.'],
                ['label' => 'Cardiologie', 'text' => 'Vreau un consult cardiologic.'],
                ['label' => 'Urgență', 'text' => 'Am o urgență.'],
            ],
        ],
    ],

    'psihologie' => [
        'booking' => [
            'opening' => 'Îți pot propune o primă evaluare sau o ședință.',
            'quick_replies' => [
                ['label' => 'Primă evaluare', 'text' => 'Vreau să programez o primă evaluare.'],
                ['label' => 'Ședință online', 'text' => 'Vreau o ședință online.'],
                ['label' => 'Seara, după muncă', 'text' => 'Vreau o ședință după ora 17:00.'],
            ],
        ],
    ],

    'veterinar' => [
        'booking' => [
            'opening' => 'Te ajut cu o programare pentru animalul tău.',
            'quick_replies' => [
                ['label' => 'Vaccinare', 'text' => 'Vreau o programare pentru vaccinare.'],
                ['label' => 'Urgență', 'text' => 'Am o urgență.'],
                ['label' => 'Control', 'text' => 'Vreau un control general.'],
                ['label' => 'Prima vizită', 'text' => 'E prima vizită — ce trebuie să aduc?'],
            ],
        ],
    ],

    'beauty' => [
        'booking' => [
            'opening' => 'Te ajut să găsești rapid un slot potrivit.',
            'quick_replies' => [
                ['label' => 'Primul loc liber', 'text' => 'Vreau primul loc disponibil.'],
                ['label' => 'În weekend', 'text' => 'Vreau o programare în weekend.'],
                ['label' => 'După muncă', 'text' => 'Vreau o programare după ora 17:00.'],
                ['label' => 'La specialistul meu', 'text' => 'Vreau la specialistul preferat.'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Hospitality — restaurant / hoteluri-pensiuni
    // ────────────────────────────────────────────
    'restaurant' => [
        'hospitality' => [
            'opening' => 'Te ajut cu rezervarea unei mese. Pentru câte persoane?',
            'quick_replies' => [
                ['label' => 'Masă pentru 2', 'text' => 'Vreau o masă pentru 2 persoane.'],
                ['label' => 'Masă pentru 4', 'text' => 'Vreau o masă pentru 4 persoane.'],
                ['label' => 'Grup mare', 'text' => 'Am nevoie de o masă pentru un grup mare (6+).'],
                ['label' => 'Eveniment privat', 'text' => 'Vreau să organizez un eveniment privat.'],
            ],
        ],
        'general' => [
            'opening' => 'Bună! Te ajut cu o rezervare sau alte informații.',
            'quick_replies' => [
                ['label' => 'Meniu', 'text' => 'Vreau să văd meniul.'],
                ['label' => 'Rezervare', 'text' => 'Vreau să fac o rezervare.'],
                ['label' => 'Program', 'text' => 'Care este programul?'],
            ],
        ],
    ],

    'hoteluri-pensiuni' => [
        'hospitality' => [
            'opening' => 'Te ajut cu rezervarea unei camere. Pentru ce perioadă?',
            'quick_replies' => [
                ['label' => 'Weekend-ul acesta', 'text' => 'Vreau o cameră pentru weekend-ul acesta.'],
                ['label' => 'Două nopți', 'text' => 'Vreau o cameră pentru două nopți.'],
                ['label' => 'Cameră dublă', 'text' => 'Vreau o cameră dublă.'],
                ['label' => '2 adulți + 1 copil', 'text' => 'Vreau o cameră pentru 2 adulți și 1 copil.'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Lead / service niches
    // ────────────────────────────────────────────
    'avocatura' => [
        'general' => [
            'opening' => 'Te pot ajuta cu o consultație sau informații generale.',
            'quick_replies' => [
                ['label' => 'Programare consultație', 'text' => 'Vreau o consultație.'],
                ['label' => 'Domenii de activitate', 'text' => 'Ce domenii acoperiți?'],
                ['label' => 'Tarife', 'text' => 'Cum calculați tarifele?'],
            ],
        ],
    ],

    'imobiliare' => [
        'general' => [
            'opening' => 'Te ajut să găsești repede proprietatea potrivită.',
            'quick_replies' => [
                ['label' => 'Caut apartament', 'text' => 'Caut un apartament.'],
                ['label' => 'Caut casă', 'text' => 'Caut o casă.'],
                ['label' => 'Vreau să închiriez', 'text' => 'Vreau să închiriez.'],
                ['label' => 'Pe buget', 'text' => 'Caut în funcție de buget.'],
            ],
        ],
    ],

    'turism' => [
        'general' => [
            'opening' => 'Te pot ajuta cu un sejur. Ce destinație preferi?',
            'quick_replies' => [
                ['label' => 'City break', 'text' => 'Caut un city break.'],
                ['label' => 'Sejur pe litoral', 'text' => 'Caut un sejur la mare.'],
                ['label' => 'Pe buget', 'text' => 'Caut pe buget.'],
                ['label' => 'Weekend-ul acesta', 'text' => 'Ce opțiuni ai pentru weekend-ul acesta?'],
            ],
        ],
    ],
];
