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
                // Intentionally NO 'talk to operator' chip upfront —
                // we want callers to engage the bot first. Handoff is
                // still available via the G5 bail-signal fallback
                // ('Lasă-mi datele'), which only fires when the bot
                // itself can't answer. Keeping the default strip
                // empty makes the widget feel confident, not defeated.
                ['label' => 'Întrebări frecvente', 'text' => 'Vreau să văd întrebările frecvente.'],
            ],
        ],
    ],

    // ────────────────────────────────────────────
    // Ecommerce — magazin-online
    // ────────────────────────────────────────────
    'magazin-online' => [
        'product' => [
            'opening' => 'Îți ofer rapid o părere sinceră pe produsul ăsta. Ce te interesează?',
            'quick_replies' => [
                ['label' => 'E potrivit pentru mine?', 'text' => 'Cum știu dacă acest produs e potrivit pentru mine?'],
                ['label' => 'Compară cu alternative', 'text' => 'Compară-mi acest produs cu alternative similare.'],
                ['label' => 'Vreau să comand', 'text' => 'Cum comand acest produs?'],
                ['label' => 'Livrare & retur', 'text' => 'Cât durează livrarea și care e politica de retur?'],
            ],
        ],
        'category' => [
            'opening' => 'Te ajut să alegi rapid, nu doar să cauți. Ce îți trebuie?',
            'quick_replies' => [
                ['label' => 'Alege pentru mine', 'text' => 'Alege tu produsul potrivit pentru mine în această categorie.'],
                ['label' => 'Pe buget', 'text' => 'Recomandă-mi în funcție de buget.'],
                ['label' => 'Cele mai populare', 'text' => 'Arată-mi cele mai populare 3 opțiuni.'],
                ['label' => 'Cele mai bine cotate', 'text' => 'Care sunt cele mai bine cotate variante?'],
            ],
        ],
        'cart' => [
            'opening' => 'Văd coșul tău. Te ajut să finalizezi rapid și să eviți surprize la checkout.',
            'quick_replies' => [
                ['label' => 'Livrare gratuită?', 'text' => 'Ajung la pragul pentru livrare gratuită? Cât îmi mai lipsește?'],
                ['label' => 'Ce accesorii să adaug?', 'text' => 'Ce accesorii compatibile recomanzi pentru ce am în coș?'],
                ['label' => 'Cod promo?', 'text' => 'Există un cod promo activ pe care îl pot aplica?'],
                ['label' => 'Ajută-mă să finalizez', 'text' => 'Ghidează-mă să finalizez comanda.'],
            ],
        ],
        'general' => [
            'opening' => 'Te ajut să alegi. Spune-mi ce cauți sau apasă o sugestie.',
            'quick_replies' => [
                ['label' => 'Recomandă-mi ceva', 'text' => 'Recomandă-mi 3 produse care ți se par cele mai bune acum.'],
                ['label' => 'Cadou sub 200 lei', 'text' => 'Caut un cadou cu un buget sub 200 lei.'],
                ['label' => 'Livrare astăzi', 'text' => 'Ce produse pot primi astăzi?'],
                ['label' => 'Cele mai populare', 'text' => 'Arată-mi cele mai populare produse acum.'],
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
            'opening' => 'Îți fac rezervarea în sub un minut. Pentru câte persoane?',
            'quick_replies' => [
                ['label' => 'Masă pentru 2', 'text' => 'Vreau o masă pentru 2 persoane.'],
                ['label' => 'Masă pentru 4', 'text' => 'Vreau o masă pentru 4 persoane.'],
                ['label' => 'Astă-seară', 'text' => 'Ce disponibilitate aveți astă-seară?'],
                ['label' => 'Eveniment privat', 'text' => 'Vreau să organizez un eveniment privat.'],
            ],
        ],
        'general' => [
            'opening' => 'Rezervi masă sau vezi meniul? Îți răspund rapid la oricare.',
            'quick_replies' => [
                ['label' => 'Rezervă o masă', 'text' => 'Vreau să fac o rezervare.'],
                ['label' => 'Vezi meniul', 'text' => 'Vreau să văd meniul.'],
                ['label' => 'Oferte speciale', 'text' => 'Ce oferte speciale aveți astăzi sau săptămâna asta?'],
                ['label' => 'Program', 'text' => 'Care este programul de astăzi?'],
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
            'opening' => 'Îți propun un pas concret pentru situația ta. Ce te interesează?',
            'quick_replies' => [
                ['label' => 'Programează-mi o consultație', 'text' => 'Vreau o consultație cu un avocat.'],
                ['label' => 'Cazul meu se încadrează?', 'text' => 'Descriu pe scurt situația și îmi spui dacă se încadrează în serviciile voastre.'],
                ['label' => 'Tarife orientative', 'text' => 'Cum sunt calculate tarifele pentru cazul meu?'],
                ['label' => 'Urgent', 'text' => 'Am o situație urgentă, când pot vorbi cu cineva?'],
            ],
        ],
    ],

    'imobiliare' => [
        'general' => [
            'opening' => 'Te ajut să reduci lista la 3 proprietăți potrivite. Ce cauți?',
            'quick_replies' => [
                ['label' => 'Apartament de cumpărat', 'text' => 'Caut un apartament de cumpărat.'],
                ['label' => 'Casă de cumpărat', 'text' => 'Caut o casă de cumpărat.'],
                ['label' => 'Vreau să închiriez', 'text' => 'Vreau să închiriez o proprietate.'],
                ['label' => 'Filtrează pe buget', 'text' => 'Am un buget și vreau să filtrezi opțiunile.'],
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
