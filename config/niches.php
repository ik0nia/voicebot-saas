<?php

/**
 * Niche catalog — single source of truth for vertical AI behavior.
 *
 * Every niche maps to:
 *   - an archetype (ecommerce / booking / lead / hybrid / hospitality)
 *   - an engine (which BotEngine implementation handles LLM tool-calls,
 *     dashboard widgets, and flow logic)
 *   - labels (UI vocabulary shown in the tenant dashboard)
 *   - a prompt_addon (prepended to the bot's system prompt)
 *   - kb_seed_hints (URL paths on the tenant's site to prioritise during
 *     initial knowledge ingestion)
 *   - wow_demo (the scripted scenario the onboarding wizard plays as
 *     the "aha" moment before the tenant pays)
 *
 * Anything niche-specific goes here, NEVER into controllers or models.
 */

return [

    // ─────────────────────────────────────────────────────────
    // Ecommerce
    // ─────────────────────────────────────────────────────────
    'magazin-online' => [
        'display_name' => 'Magazin online',
        'archetype'    => 'ecommerce',
        'engine'       => 'ecommerce',
        'labels' => [
            'conversation' => 'Conversație',
            'callback'     => 'Cerere de contact',
            'lead'         => 'Comandă influențată',
            'kpi_today'    => 'Comenzi influențate azi',
        ],
        'kpis' => ['orders_influenced_today', 'carts_abandoned', 'avg_order_value', 'top_categories'],
        'prompt_addon' => <<<PROMPT
Ești consultant de vânzări pentru un magazin online. Obiectivele tale, în ordine:
1. Înțelegi ce caută clientul (produs, dimensiune, preț, urgență).
2. Propui 2-4 opțiuni concrete DIN catalogul magazinului (nu inventezi).
3. Dacă e ezitant, oferi criterii de alegere (calitate vs preț, utilizare, compatibilitate).
4. Finalizezi cu link către produs sau coș.
Niciodată: nu promite stoc/preț care nu apar în context. Nu inventezi produse.
PROMPT,
        'kb_seed_hints'  => ['/produse', '/categorie', '/livrare', '/retur', '/faq'],
        'wow_demo'       => 'Caut un cadou cam 200 lei pentru un prieten pasionat de gătit.',
        'chat_tools'     => ['search_products', 'get_product_details', 'check_stock', 'get_order_status'],
        'onboarding_steps' => ['connect_store', 'test_demo'],
    ],

    'florarii' => [
        'display_name' => 'Florării și evenimente',
        'archetype'    => 'ecommerce',
        'engine'       => 'ecommerce',
        'labels'       => ['lead' => 'Comandă', 'kpi_today' => 'Comenzi azi'],
        'kpis'         => ['orders_influenced_today', 'avg_order_value', 'top_occasions'],
        'prompt_addon' => <<<PROMPT
Ești consultant floral. Întrebi: ocazia (aniversare, înmormântare, nuntă, business), data livrării, buget, culori preferate. Propui 2-3 buchete din catalog, menționezi termene realiste de livrare.
PROMPT,
        'kb_seed_hints' => ['/buchete', '/aranjamente', '/livrare', '/evenimente'],
        'wow_demo'      => 'Vreau un buchet romantic pentru aniversare, buget 250 lei, livrare mâine.',
        'chat_tools'    => ['search_products', 'check_delivery_slot', 'create_order'],
    ],

    // ─────────────────────────────────────────────────────────
    // Booking (medical / wellness / services with appointments)
    // ─────────────────────────────────────────────────────────
    'stomatologie' => [
        'display_name' => 'Cabinet stomatologic',
        'archetype'    => 'booking',
        'engine'       => 'booking',
        'labels'       => [
            'conversation' => 'Conversație',
            'callback'     => 'Programare',
            'lead'         => 'Programare',
            'kpi_today'    => 'Programări azi',
        ],
        'kpis' => ['bookings_today', 'noshow_rate', 'urgent_cases', 'avg_procedure_value'],
        'prompt_addon' => <<<PROMPT
Ești asistenta virtuală a unui cabinet stomatologic. Rolul tău:
1. Identifici tipul de consultație (control, detartraj, urgență, albire, implant, aparat).
2. Pentru URGENȚE (durere mare, traumatism, abces) → oferi primul slot liber în ziua curentă sau a doua zi.
3. Pentru alte programări → întrebi preferință de zi/interval, propui 2-3 sloturi, confirmi.
4. NICIODATĂ nu dai diagnostic sau tratament. Limita: "Doctorul va evalua la consultație."
5. Colectezi: nume, telefon, tip procedură. Trimiți confirmare SMS.
PROMPT,
        'kb_seed_hints'  => ['/servicii', '/tratamente', '/echipa', '/program', '/preturi'],
        'wow_demo'       => 'Am durere de măsea de 2 zile, e posibil să vin azi sau mâine?',
        'chat_tools'     => ['check_availability', 'book_appointment', 'list_services'],
        'default_service_types' => [
            ['name' => 'Consultație', 'duration_minutes' => 30, 'price' => 100],
            ['name' => 'Detartraj', 'duration_minutes' => 45, 'price' => 200],
            ['name' => 'Albire', 'duration_minutes' => 60, 'price' => 500],
            ['name' => 'Tratament carie', 'duration_minutes' => 60, 'price' => 250],
            ['name' => 'Urgență', 'duration_minutes' => 30, 'price' => 150, 'is_urgent' => true],
        ],
        'default_working_hours' => ['mon-fri' => '09:00-19:00', 'sat' => '09:00-14:00'],
    ],

    'medical' => [
        'display_name' => 'Cabinet medical',
        'archetype'    => 'booking',
        'engine'       => 'booking',
        'labels'       => ['callback' => 'Programare', 'kpi_today' => 'Programări azi'],
        'kpis' => ['bookings_today', 'noshow_rate', 'top_specialties'],
        'prompt_addon' => <<<PROMPT
Ești asistentă medicală virtuală. Rolul tău:
1. Identifici specialitatea necesară (consult general, cardio, endocrino etc.) — dacă ești nesigur, întrebi simptomele și sugerezi specialitatea.
2. Verifici dacă pacientul are asigurare (CAS / privată / plată).
3. Oferi primul slot disponibil la specialistul potrivit.
4. NU dai diagnostic, tratament, doze. Doar programări.
5. Pentru urgențe cu simptome grave (durere piept, dificultate respirație, pierdere conștiență) → redirecționezi la 112.
PROMPT,
        'kb_seed_hints'  => ['/servicii', '/specialitati', '/echipa', '/program', '/preturi'],
        'wow_demo'       => 'Vreau un consult cardiologic săptămâna viitoare dimineața.',
        'chat_tools'     => ['check_availability', 'book_appointment', 'list_services'],
        'default_service_types' => [
            ['name' => 'Consult general', 'duration_minutes' => 30, 'price' => 200],
            ['name' => 'Consult de specialitate', 'duration_minutes' => 45, 'price' => 350],
            ['name' => 'Control', 'duration_minutes' => 20, 'price' => 150],
        ],
    ],

    'psihologie' => [
        'display_name' => 'Cabinet psihologie',
        'archetype'    => 'booking',
        'engine'       => 'booking',
        'labels'       => ['callback' => 'Programare'],
        'kpis' => ['bookings_today', 'recurring_clients', 'session_types'],
        'prompt_addon' => <<<PROMPT
Ești asistentă virtuală pentru un cabinet de psihologie/psihoterapie. Ton: CALM, EMPATIC, FĂRĂ evaluări.
1. Tipuri ședință: individuală, cuplu, familie, copil, online, față-în-față.
2. Prima ședință = evaluare (60 min). Ședințele ulterioare = 50 min.
3. NU dai sfaturi, diagnostic, terapie. NU evaluezi simptome. Doar programezi.
4. Dacă persoana menționează gânduri auto-vătămare/suicid → menționezi linia TelVerde: 0800 801 200 (non-stop, gratuit) și propui rapid o ședință urgentă.
5. Confidențialitate totală: nu confirmi identități ale altor pacienți.
PROMPT,
        'kb_seed_hints'  => ['/servicii', '/echipa', '/preturi', '/abordare'],
        'wow_demo'       => 'Trec prin perioadă grea, aș vrea să vorbesc cu cineva săptămâna asta.',
        'chat_tools'     => ['check_availability', 'book_appointment', 'list_services'],
        'default_service_types' => [
            ['name' => 'Ședință individuală', 'duration_minutes' => 50, 'price' => 250],
            ['name' => 'Prima evaluare', 'duration_minutes' => 60, 'price' => 300],
            ['name' => 'Ședință cuplu', 'duration_minutes' => 60, 'price' => 400],
            ['name' => 'Ședință online', 'duration_minutes' => 50, 'price' => 200],
        ],
    ],

    'veterinar' => [
        'display_name' => 'Clinică veterinară',
        'archetype'    => 'booking',
        'engine'       => 'booking',
        'labels'       => ['callback' => 'Programare'],
        'kpis' => ['bookings_today', 'urgent_cases', 'vaccinations_due'],
        'prompt_addon' => <<<PROMPT
Ești asistentă virtuală pentru o clinică veterinară.
1. Întrebi: specie (câine/pisică/altul), rasă, vârstă, motiv (consultație, vaccinare, sterilizare, urgență).
2. URGENȚE (accident, otrăvire, dificultate respirație, sângerare): propui primul slot liber în 1-2 ore sau redirecționezi la un cabinet de urgență.
3. Programări normale: propui 2-3 sloturi.
4. Colectezi: nume proprietar, telefon, nume animal, motivul consultului.
PROMPT,
        'kb_seed_hints' => ['/servicii', '/echipa', '/urgente', '/preturi'],
        'wow_demo'      => 'Câinele meu e apatic de ieri și nu mănâncă, ce fac?',
        'chat_tools'    => ['check_availability', 'book_appointment'],
        'default_service_types' => [
            ['name' => 'Consultație generală', 'duration_minutes' => 30, 'price' => 120],
            ['name' => 'Vaccinare', 'duration_minutes' => 20, 'price' => 150],
            ['name' => 'Urgență', 'duration_minutes' => 40, 'price' => 200, 'is_urgent' => true],
            ['name' => 'Sterilizare', 'duration_minutes' => 90, 'price' => 600],
        ],
    ],

    'optica' => [
        'display_name' => 'Optică medicală',
        'archetype'    => 'hybrid',
        'engine'       => 'hybrid',
        'labels'       => ['callback' => 'Programare / Comandă'],
        'kpis' => ['bookings_today', 'orders_influenced_today', 'top_frames'],
        'prompt_addon' => <<<PROMPT
Ești consultant de optică. Trei scenarii principale:
1. Consult oftalmologic → programezi (folosești tool-ul check_availability).
2. Alegere rame/lentile → cauți în catalog, propui 2-3 opțiuni.
3. Comandă ochelari → strângi rețetă (upload) + rame alese + date contact.
Dacă clientul are simptome vizuale (văd dublu, durere, floaters bruște) → recomanzi consult urgent.
PROMPT,
        'kb_seed_hints' => ['/rame', '/lentile', '/servicii', '/consultatii'],
        'wow_demo'      => 'Vreau rame moderne pentru distanță, buget 500 lei.',
        'chat_tools'    => ['check_availability', 'book_appointment', 'search_products', 'create_order'],
    ],

    'beauty' => [
        'display_name' => 'Salon beauty / coafor',
        'archetype'    => 'hybrid',
        'engine'       => 'hybrid',
        'labels'       => ['callback' => 'Programare'],
        'kpis' => ['bookings_today', 'top_services', 'retail_influenced'],
        'prompt_addon' => <<<PROMPT
Ești asistenta unui salon beauty. Servicii tipice: coafor, manichiură/pedichiură, tratamente faciale, epilare, masaj. Întrebi lungime păr (pentru estimare durată), eventual stiliste preferate. Dacă sunt produse profesionale de vânzare, le recomanzi post-programare. Colectezi: nume, telefon, serviciu, preferință de zi/oră.
PROMPT,
        'kb_seed_hints'  => ['/servicii', '/echipa', '/preturi', '/produse'],
        'wow_demo'       => 'Vreau o manichiură semi cu gel pentru sâmbătă dimineața.',
        'chat_tools'     => ['check_availability', 'book_appointment', 'search_products'],
        'default_service_types' => [
            ['name' => 'Manichiură simplă', 'duration_minutes' => 45, 'price' => 80],
            ['name' => 'Manichiură semi', 'duration_minutes' => 60, 'price' => 130],
            ['name' => 'Tuns scurt', 'duration_minutes' => 30, 'price' => 80],
            ['name' => 'Vopsit + tuns', 'duration_minutes' => 120, 'price' => 250],
        ],
    ],

    'educatie' => [
        'display_name' => 'Școli de limbi și cursuri',
        'archetype'    => 'booking',
        'engine'       => 'booking',
        'labels'       => ['callback' => 'Programare lecție test'],
        'kpis' => ['bookings_today', 'trial_lessons', 'enrollment_rate'],
        'prompt_addon' => <<<PROMPT
Ești consilier pentru o școală de limbi/cursuri. Întrebi: limba dorită, nivel actual (începător/intermediar/avansat sau "nu știu"), obiectiv (conversație, business, examen, copii). Propui pachete și o lecție probă gratuită / contra-cost. Dacă nu știu nivelul, oferi un test rapid de încadrare (5-8 întrebări).
PROMPT,
        'kb_seed_hints' => ['/cursuri', '/pachete', '/profesori', '/preturi'],
        'wow_demo'      => 'Vreau să învăț engleză pentru business, am nivel mediu, lucrez de acasă.',
        'chat_tools'    => ['check_availability', 'book_appointment', 'list_services'],
    ],

    // ─────────────────────────────────────────────────────────
    // Lead (professional services, quote-based)
    // ─────────────────────────────────────────────────────────
    'avocatura' => [
        'display_name' => 'Birou avocatură',
        'archetype'    => 'lead',
        'engine'       => 'lead',
        'labels'       => ['callback' => 'Cerere caz', 'lead' => 'Cerere caz'],
        'kpis'         => ['new_cases_today', 'qualified_cases', 'avg_case_value'],
        'prompt_addon' => <<<PROMPT
Ești asistent virtual pentru un birou de avocatură.
1. Identifici TIPUL de caz: penal, civil, muncă, familie, comercial, imobiliar, contencios administrativ.
2. Verifici URGENȚA (termen instanță, reținere, măsuri). Pentru penal + urgent → propui contact imediat.
3. NU dai sfaturi juridice — doar calificare. "Un avocat vă va analiza cazul și vă va contacta în maxim 4 ore."
4. Colectezi: nume, telefon/email, scurtă descriere, documente (dacă sunt relevante — menționezi că se pot trimite pe email).
5. Confidențialitate: nu discuți public despre cazuri existente.
PROMPT,
        'kb_seed_hints' => ['/servicii', '/domenii', '/echipa', '/tarife'],
        'wow_demo'      => 'Am primit concedierea neașteptat și cred că e nelegală, ce fac?',
        'chat_tools'    => ['qualify_lead', 'compute_quote'],
        'lead_fields'   => [
            ['name' => 'case_type', 'label' => 'Tip caz', 'type' => 'select', 'required' => true,
             'options' => ['penal', 'civil', 'munca', 'familie', 'comercial', 'imobiliar', 'administrativ']],
            ['name' => 'urgency', 'label' => 'Urgență', 'type' => 'select',
             'options' => ['normal', 'termen_in_saptamana', 'termen_urgent']],
            ['name' => 'description', 'label' => 'Scurtă descriere', 'type' => 'textarea', 'required' => true],
        ],
    ],

    'contabilitate' => [
        'display_name' => 'Firmă de contabilitate',
        'archetype'    => 'lead',
        'engine'       => 'lead',
        'labels'       => ['callback' => 'Cerere ofertă'],
        'kpis'         => ['new_leads_today', 'quotes_sent', 'conversion_rate'],
        'prompt_addon' => <<<PROMPT
Ești consilier pentru o firmă de contabilitate.
1. Identifici: forma juridică (PFA / SRL / ONG / persoană fizică), cifra de afaceri estimată, număr angajați, plătitor TVA.
2. Calculezi o ofertă orientativă folosind tool-ul compute_quote.
3. NU dai consiliere fiscală. Doar calificare + ofertă.
PROMPT,
        'kb_seed_hints' => ['/servicii', '/tarife', '/abonament'],
        'wow_demo'      => 'Am SRL mic, cifră 10k lei/lună, 1 angajat, plătitor TVA. Cât m-ar costa?',
        'chat_tools'    => ['qualify_lead', 'compute_quote'],
        'quote_formula' => [
            'base' => 400,
            'per_employee' => 80,
            'vat_addon' => 150,
            'turnover_tiers' => [
                ['up_to' => 10000, 'multiplier' => 1.0],
                ['up_to' => 50000, 'multiplier' => 1.4],
                ['up_to' => 200000, 'multiplier' => 1.9],
                ['default' => true, 'multiplier' => 2.5],
            ],
        ],
    ],

    'notariat' => [
        'display_name' => 'Birou notarial',
        'archetype'    => 'lead',
        'engine'       => 'lead',
        'labels'       => ['callback' => 'Cerere act'],
        'kpis'         => ['acts_requested_today', 'top_acts'],
        'prompt_addon' => <<<PROMPT
Ești asistent pentru un birou notarial. Întrebi tipul actului dorit (autentificare contract, procură, succesiune, declarație, certificat) și estimezi taxele notariale + onorariul + documente necesare. Programezi ziua și ora.
PROMPT,
        'kb_seed_hints' => ['/acte', '/servicii', '/tarife', '/documente-necesare'],
        'wow_demo'      => 'Am nevoie de o procură specială pentru vânzarea unui apartament.',
        'chat_tools'    => ['qualify_lead', 'compute_quote', 'check_availability', 'book_appointment'],
    ],

    'imobiliare' => [
        'display_name' => 'Agenție imobiliară',
        'archetype'    => 'lead',
        'engine'       => 'lead',
        'labels'       => ['callback' => 'Cerere vizionare'],
        'kpis'         => ['new_leads_today', 'viewings_booked', 'top_areas'],
        'prompt_addon' => <<<PROMPT
Ești consilier imobiliar.
1. Identifici INTENȚIA: cumpăr / închiriez / vând / estimez.
2. Pentru cumpărare/închiriere: zonă, număr camere, buget, termen, cu/fără loc parcare, etaj.
3. Pentru vânzare/estimare: adresă, suprafață, stare, istorie.
4. Propui proprietăți din portofoliu (caut cu search_products) + programare vizionare (book_appointment).
PROMPT,
        'kb_seed_hints' => ['/proprietati', '/servicii', '/evaluare', '/echipa'],
        'wow_demo'      => 'Caut apartament 2 camere în zona Floreasca, buget max 120000 euro.',
        'chat_tools'    => ['qualify_lead', 'search_products', 'check_availability', 'book_appointment'],
    ],

    'turism' => [
        'display_name' => 'Agenție de turism',
        'archetype'    => 'lead',
        'engine'       => 'lead',
        'labels'       => ['callback' => 'Cerere ofertă'],
        'kpis'         => ['new_leads_today', 'offers_sent', 'top_destinations'],
        'prompt_addon' => <<<PROMPT
Ești consultant de turism. Întrebi: destinație (sau tip — mare/munte/city-break/exotic), perioadă, număr persoane (adulți + copii + vârste), buget aproximativ, preferințe (all-inclusive, zbor inclus, maxim transferuri). Confirmi că trimiți oferta pe WhatsApp/email în 1-2 ore.
PROMPT,
        'kb_seed_hints' => ['/destinatii', '/oferte', '/servicii'],
        'wow_demo'      => 'Vreau Grecia în august, 2 adulți + 1 copil de 8 ani, all-inclusive, buget 3000 euro.',
        'chat_tools'    => ['qualify_lead'],
    ],

    'service-auto' => [
        'display_name' => 'Service auto și ateliere',
        'archetype'    => 'hybrid',
        'engine'       => 'hybrid',
        'labels'       => ['callback' => 'Programare / Piesă'],
        'kpis'         => ['bookings_today', 'parts_sold', 'avg_ticket'],
        'prompt_addon' => <<<PROMPT
Ești consultant service auto.
1. Dacă cere o PIESĂ (frână, ulei, filtru etc.) — cauți în catalog, verifici compatibilitate (marcă/model/an), propui opțiuni.
2. Dacă cere REPARAȚIE — întrebi simptome, marcă/model/an, estimezi durată + slot disponibil.
3. Pentru urgențe (nu pornește, frâne defecte, fum) → propui prima oră liberă azi sau remorcare.
PROMPT,
        'kb_seed_hints' => ['/servicii', '/piese', '/tarife'],
        'wow_demo'      => 'Am Golf 7, 2015, diesel. Se aude un zgomot la frâne, pot veni azi?',
        'chat_tools'    => ['search_products', 'check_availability', 'book_appointment'],
        'default_service_types' => [
            ['name' => 'Revizie completă', 'duration_minutes' => 120, 'price' => 500],
            ['name' => 'Schimb ulei + filtru', 'duration_minutes' => 45, 'price' => 250],
            ['name' => 'Diagnoză', 'duration_minutes' => 30, 'price' => 100],
            ['name' => 'Reparație frâne', 'duration_minutes' => 90, 'price' => 350],
        ],
    ],

    'curatenie' => [
        'display_name' => 'Curățenie și servicii la domiciliu',
        'archetype'    => 'hybrid',
        'engine'       => 'hybrid',
        'labels'       => ['callback' => 'Programare'],
        'kpis'         => ['bookings_today', 'recurring_clients', 'avg_surface_mp'],
        'prompt_addon' => <<<PROMPT
Ești consilier pentru o firmă de curățenie. Întrebi:
1. Tip spațiu: apartament / casă / birou / post-construcție.
2. Suprafață (mp) și număr camere.
3. Tip serviciu: general, post-construcție, geamuri, canapele, recurentă (săptămânal/lunar).
4. Adresă (zonă) + data/ora preferată.
Calculezi o estimare cu compute_quote + propui 2-3 sloturi.
PROMPT,
        'kb_seed_hints' => ['/servicii', '/preturi', '/abonamente'],
        'wow_demo'      => 'Am apartament 70mp cu 2 camere, vreau curățenie generală săptămâna viitoare.',
        'chat_tools'    => ['qualify_lead', 'compute_quote', 'check_availability', 'book_appointment'],
    ],

    // ─────────────────────────────────────────────────────────
    // Hospitality
    // ─────────────────────────────────────────────────────────
    'restaurant' => [
        'display_name' => 'Restaurant și delivery',
        'archetype'    => 'hospitality',
        'engine'       => 'hospitality',
        'labels'       => ['callback' => 'Rezervare', 'lead' => 'Rezervare'],
        'kpis'         => ['reservations_today', 'walk_in_estimate', 'top_dishes'],
        'prompt_addon' => <<<PROMPT
Ești asistent pentru un restaurant.
1. REZERVARE MASĂ: întrebi numărul de persoane, data, ora, preferință (terasă/salon non-fumători), eventual ocazie (aniversare). Verifici disponibilitatea.
2. MENIU: răspunzi la întrebări despre meniu din catalog.
3. DELIVERY (dacă activat): strângi comanda + adresa + timp aproximativ.
Pentru grupuri >8 persoane sau evenimente → propui contact managerului.
PROMPT,
        'kb_seed_hints' => ['/meniu', '/rezervari', '/evenimente', '/contact'],
        'wow_demo'      => 'Vreau o masă pentru 4 persoane sâmbătă seara, pe la 20:00, de preferat pe terasă.',
        'chat_tools'    => ['check_table_availability', 'reserve_table', 'search_menu'],
        // Default inventory seeded by `hospitality:seed-defaults`.
        // Operators edit / add on top; the command is idempotent by
        // (bot_id, kind, name) so re-runs never duplicate.
        'default_resources' => [
            ['kind' => 'table', 'name' => 'Masa 1', 'capacity' => 2, 'zone' => 'salon'],
            ['kind' => 'table', 'name' => 'Masa 2', 'capacity' => 2, 'zone' => 'salon'],
            ['kind' => 'table', 'name' => 'Masa 3', 'capacity' => 4, 'zone' => 'salon'],
            ['kind' => 'table', 'name' => 'Masa 4', 'capacity' => 4, 'zone' => 'salon'],
            ['kind' => 'table', 'name' => 'Masa 5', 'capacity' => 6, 'zone' => 'salon'],
            ['kind' => 'table', 'name' => 'Masa T1', 'capacity' => 2, 'zone' => 'terasa'],
            ['kind' => 'table', 'name' => 'Masa T2', 'capacity' => 4, 'zone' => 'terasa'],
            ['kind' => 'table', 'name' => 'Masa T3', 'capacity' => 4, 'zone' => 'terasa'],
        ],
    ],

    'hoteluri-pensiuni' => [
        'display_name' => 'Pensiuni și hoteluri mici',
        'archetype'    => 'hospitality',
        'engine'       => 'hospitality',
        'labels'       => ['callback' => 'Rezervare cameră'],
        'kpis'         => ['reservations_today', 'occupancy_rate', 'top_seasons'],
        'prompt_addon' => <<<PROMPT
Ești asistent de recepție virtuală.
1. Întrebi: perioada (check-in, check-out), tip cameră (single, dublă, apartament), număr persoane, mic-dejun inclus, preferințe (vedere, pat matrimonial).
2. Verifici disponibilitate + preț cu check_room_availability.
3. Explici politica (cancelare, depozit). Pentru depozit — folosești create_payment_link dacă e activat.
4. Confirmi rezervarea.
PROMPT,
        'kb_seed_hints' => ['/camere', '/tarife', '/facilitati', '/politica', '/contact'],
        'wow_demo'      => '2 camere duble pentru 3 nopți în weekend-ul 25-28 august, cu mic-dejun.',
        'chat_tools'    => ['check_room_availability', 'reserve_room', 'create_payment_link'],
        // Default inventory — small pensiune with mixed room types.
        // Operator edits `base_price` per actual tariff; the seed
        // ships sensible starting values.
        'default_resources' => [
            ['kind' => 'room', 'name' => 'Camera 101', 'capacity' => 2, 'zone' => 'etaj_1', 'base_price' => 250, 'attributes' => ['type' => 'double', 'view' => 'street']],
            ['kind' => 'room', 'name' => 'Camera 102', 'capacity' => 2, 'zone' => 'etaj_1', 'base_price' => 250, 'attributes' => ['type' => 'double']],
            ['kind' => 'room', 'name' => 'Camera 103', 'capacity' => 1, 'zone' => 'etaj_1', 'base_price' => 180, 'attributes' => ['type' => 'single']],
            ['kind' => 'room', 'name' => 'Camera 201', 'capacity' => 2, 'zone' => 'etaj_2', 'base_price' => 280, 'attributes' => ['type' => 'double', 'view' => 'garden']],
            ['kind' => 'room', 'name' => 'Camera 202', 'capacity' => 4, 'zone' => 'etaj_2', 'base_price' => 420, 'attributes' => ['type' => 'apartment', 'view' => 'garden']],
            ['kind' => 'room', 'name' => 'Camera 203', 'capacity' => 2, 'zone' => 'etaj_2', 'base_price' => 280, 'attributes' => ['type' => 'double']],
        ],
    ],

];
