<?php

/*
|--------------------------------------------------------------------------
| Identitate juridică a operatorului platformei.
|--------------------------------------------------------------------------
|
| Sambla este o platformă SaaS operată de Ikonia Agency SRL. Aceste date
| sunt obligatorii pe orice pagină legală (Termeni, Confidențialitate,
| Cookie-uri), în footer-ul site-ului public, în antetul facturilor și
| în orice corespondență cu autoritățile (ANSPDCP, ANAF) sau cu
| platformele care cer identificarea operatorului la review.
|
| Sursă unică ca să nu rămână variante divergente între pagini când se
| modifică (mutare sediu, schimbare DPO, capital social actualizat).
|
*/

return [

    // Denumire juridică completă, exact cum apare la Registrul Comerțului.
    // NU se traduce și nu se prescurtează — ANSPDCP cere acest format.
    'legal_name' => 'IKONIA AGENCY SRL',

    // Brandul comercial sub care e cunoscută platforma — folosit în
    // titluri și call-to-action-uri, NU în secțiuni juridice.
    'brand' => 'Sambla',

    // CUI = Cod Unic de Identificare (TVA prefix RO pentru plătitori).
    // Stocat fără prefix; helperul .formatted() adaugă „RO" pentru afișare.
    'cui' => '47310601',
    'vat_prefix' => 'RO',

    // Numărul de înregistrare la Oficiul Registrului Comerțului.
    'reg_com' => 'J05/3636/2022',

    // EUID = European Unique Identifier; necesar pentru anumite
    // platforme paneuropene de business directory.
    'euid' => 'ROONRC.J5/3636/2022',

    // Data înființării — utilă în pagina „Despre" și în clauzele de
    // jurisdicție din contractele B2B.
    'founded_at' => '2022-12-14',

    // Sediu social — adresa oficială declarată la Trade Register.
    'address' => [
        'street' => 'Bd. Dacia 31, Bl. AN57, Et. 4, Ap. 13',
        'city' => 'Oradea',
        'county' => 'Bihor',
        'postal_code' => '410464',
        'country' => 'România',
        'country_code' => 'RO',
    ],

    // Contact public — apare în footer + footer email-uri tranzacționale.
    'contact' => [
        // Email-ul oficial de contact (servicii pentru clienți).
        'email' => 'contact@sambla.ro',
        // Email pentru cereri GDPR / DPO — același ca general azi,
        // separat când vom desemna formal un DPO extern.
        'dpo_email' => 'contact@sambla.ro',
        // Email pentru chestiuni legale / contractuale.
        'legal_email' => 'contact@sambla.ro',
    ],

    // Website-ul produsului (NU al firmei mamă).
    'website' => 'https://sambla.ro',

    // Helper de afișare standardizată pentru identificarea în footer:
    //   IKONIA AGENCY SRL · CUI RO47310601 · J05/3636/2022 · Oradea
    'footer_line' => 'IKONIA AGENCY SRL · CUI RO47310601 · J05/3636/2022 · Oradea, Bihor',

];
