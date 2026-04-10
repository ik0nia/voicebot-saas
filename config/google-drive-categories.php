<?php

/*
|--------------------------------------------------------------------------
| Google Drive Knowledge Categories
|--------------------------------------------------------------------------
|
| When a user imports a file from Google Drive, they tag it with one of
| these categories. The category is:
|   1. Stored on the google_drive_files row (for filtering & UI)
|   2. Saved into bot_knowledge.metadata['kb_category'] (for retrieval filtering)
|   3. Prepended to the document content as a context block, so the embedding
|      reflects the category and the LLM sees an explicit hint at retrieval
|      time. This is what makes "ce reprezinta" actually useful for RAG.
|
| The `prompt` text is what gets prepended verbatim to the file content
| before chunking. Keep it short, in Romanian, and frame it as instruction
| to the bot.
|
*/

return [

    'pricing' => [
        'label'       => 'Prețuri și oferte',
        'description' => 'Tarife, liste de prețuri, promoții, oferte speciale.',
        'icon'        => 'tag',
        'prompt'      => 'Acest document conține PREȚURI ȘI OFERTE actualizate. Folosește exact valorile, monedele și condițiile menționate aici când răspunzi la întrebări despre cost.',
    ],

    'product' => [
        'label'       => 'Produse / servicii',
        'description' => 'Descrieri, specificații, catalog de produse sau servicii.',
        'icon'        => 'box',
        'prompt'      => 'Acest document descrie PRODUSE SAU SERVICII oferite. Folosește detaliile pentru a explica caracteristicile, beneficiile și cazurile de utilizare.',
    ],

    'faq' => [
        'label'       => 'Întrebări frecvente',
        'description' => 'FAQ, întrebări și răspunsuri pre-formulate.',
        'icon'        => 'help-circle',
        'prompt'      => 'Acest document este o colecție de ÎNTREBĂRI FRECVENTE și răspunsuri oficiale. Când o întrebare a userului seamănă cu una de aici, folosește răspunsul corespondent ca sursă primară.',
    ],

    'policy' => [
        'label'       => 'Politici (retur, garanție, GDPR)',
        'description' => 'Politici de retur, garanție, livrare, confidențialitate.',
        'icon'        => 'shield',
        'prompt'      => 'Acest document conține POLITICI OFICIALE ale companiei (retur, garanție, livrare, confidențialitate, GDPR). Acestea sunt obligatorii și trebuie respectate exact când răspunzi.',
    ],

    'company' => [
        'label'       => 'Despre companie',
        'description' => 'Istorie, misiune, valori, echipă, prezentare generală.',
        'icon'        => 'briefcase',
        'prompt'      => 'Acest document conține informații despre COMPANIE (istoric, misiune, valori, echipă). Folosește pentru a răspunde la întrebări de tipul "cine sunteți?".',
    ],

    'contact' => [
        'label'       => 'Contact și locații',
        'description' => 'Adrese, numere de telefon, program, hartă locații.',
        'icon'        => 'map-pin',
        'prompt'      => 'Acest document conține DATE DE CONTACT (adrese, telefoane, program de funcționare, locații). Răspunde cu informațiile exacte de aici.',
    ],

    'terms' => [
        'label'       => 'Termeni și condiții',
        'description' => 'T&C, contracte, acorduri legale.',
        'icon'        => 'file-text',
        'prompt'      => 'Acest document conține TERMENI ȘI CONDIȚII LEGALE. Citează clauzele relevante exact și nu interpreta peste ce scrie aici.',
    ],

    'manual' => [
        'label'       => 'Manual / instrucțiuni',
        'description' => 'Manuale de utilizare, ghiduri pas-cu-pas, tutoriale.',
        'icon'        => 'book-open',
        'prompt'      => 'Acest document este un MANUAL DE UTILIZARE sau ghid. Folosește pașii de aici pentru a-l ajuta pe user să folosească produsul/serviciul.',
    ],

    'specs' => [
        'label'       => 'Specificații tehnice',
        'description' => 'Fișe tehnice, parametri, dimensiuni, compatibilități.',
        'icon'        => 'sliders',
        'prompt'      => 'Acest document conține SPECIFICAȚII TEHNICE precise. Folosește valorile exact cum sunt date, fără aproximări.',
    ],

    'marketing' => [
        'label'       => 'Marketing / brochures',
        'description' => 'Materiale de marketing, broșuri, prezentări.',
        'icon'        => 'megaphone',
        'prompt'      => 'Acest document este MATERIAL DE MARKETING. Conține mesaje promoționale care pot fi folosite pentru a explica beneficii, dar verifică prețurile/promoțiile cu alte surse mai oficiale.',
    ],

    'training' => [
        'label'       => 'Training intern',
        'description' => 'Documente de training, scripturi pentru agenți, knowledge intern.',
        'icon'        => 'graduation-cap',
        'prompt'      => 'Acest document este MATERIAL DE TRAINING INTERN folosit de agenți umani. Aplică aceleași reguli și răspunsuri ca un agent uman ar face.',
    ],

    'other' => [
        'label'       => 'Altceva',
        'description' => 'Document care nu se încadrează în categoriile de mai sus.',
        'icon'        => 'file',
        'prompt'      => 'Folosește acest document ca sursă generală de informație despre companie sau produs.',
    ],

];
