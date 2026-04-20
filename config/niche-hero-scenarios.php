<?php

/**
 * Scenarii de conversație pentru hero-ul din pagina fiecărei nișe.
 * Fiecare slug returnează 3-4 scenarii specifice verticalei, folosite
 * de resources/views/new/partials/hero-chat.blade.php.
 *
 * Format scenariu:
 *   [
 *     'niche'   => data-niche pentru CSS vars (color theme),
 *     'label'   => titlul scenariului cu emoji,
 *     'footer'  => textul din footer-ul cardului (validare succes),
 *     'badge'   => rezultatul final (strip de sub conversație),
 *     'messages'=> [ ['user'=>true/false, 'text'=>'...'], ... ],
 *   ]
 *
 * Pentru card produs, înlocuiește 'text' cu 'product' => [...].
 *
 * Slug-uri care nu sunt aici folosesc fallback derivat automat din
 * $niche->demo_messages (un singur scenariu generic).
 */

return [

    // ============ SĂNĂTATE & BEAUTY ============

    'cabinete-stomatologice' => [
        ['niche' => 'medical', 'label' => '🦷 Detartraj + control', 'footer' => '✓ Programare automată', 'badge' => 'Programare confirmată',
         'messages' => [
            ['user' => true,  'text' => 'Bună ziua. Aveți loc liber pentru detartraj săptămâna viitoare?'],
            ['user' => false, 'text' => 'Da, am găsit 3 disponibilități. Marți 22, 10:00 · Dr. Ionescu — 180 lei. Vă convine?'],
            ['user' => true,  'text' => 'Da, pe Maria Popescu.'],
            ['user' => false, 'text' => '✓ Rezervat. Vă trimit SMS cu detaliile și o reamintire cu 24h înainte.'],
         ]],
        ['niche' => 'medical', 'label' => '🦷 Urgență stomatologică', 'footer' => '✓ Triaj urgențe', 'badge' => 'Triaj completat',
         'messages' => [
            ['user' => true,  'text' => 'Am durere puternică de dinte, puteți să mă primiți azi?'],
            ['user' => false, 'text' => 'Îmi pare rău să aud. Pentru urgențe avem slot de 14:30 cu Dr. Marinescu. Durerea e continuă sau la mușcat?'],
            ['user' => true,  'text' => 'Continuă, de azi noapte.'],
            ['user' => false, 'text' => '✓ Slot 14:30 rezervat urgent. Până atunci: apă sărată + Nurofen dacă nu e contraindicat. Vă sun dacă se eliberează mai devreme.'],
         ]],
        ['niche' => 'medical', 'label' => '🦷 Preț implant', 'footer' => '✓ Lista de tarife live', 'badge' => 'Cotație trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Cât costă un implant dentar la dumneavoastră?'],
            ['user' => false, 'text' => 'Implantul Straumann cu coroană ceramică pornește de la 3.800 lei (totul inclus — implant + abutment + coroană).'],
            ['user' => true,  'text' => 'Poate să fie plătit în rate?'],
            ['user' => false, 'text' => '✓ Da, 3 sau 6 rate fără dobândă cu cardul BT. Vă trimit pe email lista completă de servicii și oferta scrisă.'],
         ]],
    ],

    'cabinete-medicale' => [
        ['niche' => 'medical', 'label' => '🏥 Programare consult', 'footer' => '✓ Agenda medicilor sincronizată', 'badge' => 'Consult programat',
         'messages' => [
            ['user' => true,  'text' => 'Aveți loc la cardiologie săptămâna asta?'],
            ['user' => false, 'text' => 'Dr. Pop are slot joi 11:00 și vineri 15:30. Consult 250 lei, ~30 min. Care vă convine?'],
            ['user' => true,  'text' => 'Joi e bine.'],
            ['user' => false, 'text' => '✓ Programat joi 11:00 cu Dr. Pop. Primești SMS-ul cu detalii + ce să aduci (cardul de sănătate, rezultate anterioare).'],
         ]],
        ['niche' => 'medical', 'label' => '🏥 Rezultate analize', 'footer' => '✓ Verificare în sistem medical', 'badge' => 'Status verificat',
         'messages' => [
            ['user' => true,  'text' => 'Am făcut analize marți, când sunt gata?'],
            ['user' => false, 'text' => 'Verific. Ce nume sunteți?'],
            ['user' => true,  'text' => 'Ioana Dumitrescu.'],
            ['user' => false, 'text' => '✓ Rezultatele sunt gata. Vă trimit pe email ca PDF securizat + link pentru portalul pacientului.'],
         ]],
        ['niche' => 'medical', 'label' => '🏥 Asigurare acceptată', 'footer' => '✓ Lista parteneri live', 'badge' => 'Informație confirmată',
         'messages' => [
            ['user' => true,  'text' => 'Acceptați Signal Iduna?'],
            ['user' => false, 'text' => 'Da, suntem în rețeaua Signal Iduna pentru consulturi + analize de laborator. Internările se pre-aprobă cu 48h înainte.'],
            ['user' => true,  'text' => 'Ce trebuie să aduc la consult?'],
            ['user' => false, 'text' => '✓ Cardul Signal Iduna + buletinul. Restul se face direct între noi și asigurator. Vă aștept la programare.'],
         ]],
    ],

    'optica-medicala' => [
        ['niche' => 'medical', 'label' => '👓 Consult + dioptrii', 'footer' => '✓ Programare cu optometrist', 'badge' => 'Consult rezervat',
         'messages' => [
            ['user' => true,  'text' => 'Aveți loc pentru consult vedere săptămâna asta?'],
            ['user' => false, 'text' => 'Da, joi 10:00 sau sâmbătă 11:30 cu optometristul nostru. Consult + eliberare rețetă — 80 lei.'],
            ['user' => true,  'text' => 'Sâmbătă e perfect.'],
            ['user' => false, 'text' => '✓ Rezervat sâmbătă 11:30. Dacă aveți ochelari actuali, aduceți-i — îi măsurăm.'],
         ]],
        ['niche' => 'medical', 'label' => '👓 Ramă Ray-Ban', 'footer' => '✓ Stoc magazin live', 'badge' => 'Produs disponibil',
         'messages' => [
            ['user' => true,  'text' => 'Aveți Ray-Ban Aviator Classic auriu?'],
            ['user' => false, 'text' => 'Da, 3 bucăți în magazin. Preț cu lentile dioptrii — de la 1.240 lei cu lentile standard, 1.680 lei cu antireflex premium.'],
            ['user' => true,  'text' => 'Pot veni să le probez azi?'],
            ['user' => false, 'text' => '✓ Desigur, suntem deschiși până la 20:00. Dacă vrei, îți rezerv rama pentru 2 ore ca să nu se ia între timp.'],
         ]],
        ['niche' => 'medical', 'label' => '👓 Lentile de contact', 'footer' => '✓ Recomandare pe tip de ten', 'badge' => 'Produse recomandate',
         'messages' => [
            ['user' => true,  'text' => 'Port lentile prima dată. Ce-mi recomandați?'],
            ['user' => false, 'text' => 'Pentru începători recomandăm lentile zilnice de unică folosință — mai igienice, fără întreținere.'],
            ['user' => false, 'product' => ['emoji' => '👁️', 'name' => 'Acuvue Oasys 1-Day · 30 buc', 'meta' => 'Silicon hydrogel · UV protection', 'price' => '149 lei', 'old' => '179 lei', 'discount' => '−17%']],
            ['user' => true,  'text' => 'Sunt ok pentru purtat zilnic?'],
            ['user' => false, 'text' => '✓ Da, marca nr. 1 pe piață. Vă trimit ghidul „primele 30 zile" pe email + programarea pentru prima probă.'],
         ]],
    ],

    'clinici-veterinare' => [
        ['niche' => 'medical', 'label' => '🐾 Consult cățel', 'footer' => '✓ Fișă medicală pre-completată', 'badge' => 'Consult programat',
         'messages' => [
            ['user' => true,  'text' => 'Cățelul meu tușește de 2 zile, pot să vin la control?'],
            ['user' => false, 'text' => 'Îmi pare rău. Dr. Andrei are slot azi 16:30 sau mâine 10:00. Ce rasă e și câți ani are?'],
            ['user' => true,  'text' => 'Golden Retriever, 7 ani.'],
            ['user' => false, 'text' => '✓ Programat azi 16:30. Pre-completez fișa cu datele din ultimul control. Aduceți carnețelul de vaccinări.'],
         ]],
        ['niche' => 'medical', 'label' => '🐾 Vaccin anual', 'footer' => '✓ Reminder automat', 'badge' => 'Vaccin confirmat',
         'messages' => [
            ['user' => true,  'text' => 'Când expiră vaccinul la pisica mea?'],
            ['user' => false, 'text' => 'Luca, motan european, ultimul vaccin DHP+R: 15 mai 2025. Expiră pe 15 mai 2026. Vă trimit reminder cu o lună înainte.'],
            ['user' => true,  'text' => 'Pot să-l programez de acum?'],
            ['user' => false, 'text' => '✓ Am pus Luca pentru 14 mai 2026, 11:00 cu Dr. Ioana. Cost: 120 lei (DHP+R + consult general).'],
         ]],
        ['niche' => 'medical', 'label' => '🐾 Urgență noapte', 'footer' => '✓ Protocol urgență', 'badge' => 'Transfer urgență',
         'messages' => [
            ['user' => true,  'text' => 'Cățelul a mâncat ciocolată, e urgent!'],
            ['user' => false, 'text' => 'Situație urgentă. Cât cântărește și ce cantitate?'],
            ['user' => true,  'text' => '12 kg, ~100g ciocolată cu lapte.'],
            ['user' => false, 'text' => '✓ Escaladez la Dr. de gardă acum. Veniți imediat — clinica e deschisă 24/7. Pregătesc și contactez Medipet pentru transfer dacă e nevoie.'],
         ]],
    ],

    'psihologie-psihoterapie' => [
        ['niche' => 'medical', 'label' => '🧠 Prima ședință', 'footer' => '✓ Programare anonimă', 'badge' => 'Ședință programată',
         'messages' => [
            ['user' => true,  'text' => 'Aș vrea să încerc o ședință de terapie. Cum funcționează?'],
            ['user' => false, 'text' => 'Prima ședință e de evaluare (50 min, 250 lei). Dacă doriți, vă potrivim cu psihologul care lucrează pe tema voastră.'],
            ['user' => true,  'text' => 'Anxietate legată de muncă.'],
            ['user' => false, 'text' => '✓ Vă potrivesc cu psih. Alexandra, specialistă pe burnout + anxietate profesională. Joi 18:00 liber, online sau cabinet?'],
         ]],
        ['niche' => 'medical', 'label' => '🧠 Preț + plată', 'footer' => '✓ Condiții discrete', 'badge' => 'Informații trimise',
         'messages' => [
            ['user' => true,  'text' => 'Cât costă o ședință? Puteți factura pentru angajator?'],
            ['user' => false, 'text' => 'Ședință individuală: 250 lei (50 min). Cuplu: 350 lei (60 min). Da, facturăm către firmă cu CUI sau persoană fizică.'],
            ['user' => true,  'text' => 'OK, vreau persoană fizică.'],
            ['user' => false, 'text' => '✓ Perfect. Vă trimit pe email pachetul de informare (confidențialitate, cum lucrăm, primul pas) și link-ul de programare.'],
         ]],
        ['niche' => 'medical', 'label' => '🧠 Urgență psihologică', 'footer' => '✓ Protocol de criză', 'badge' => 'Escaladare',
         'messages' => [
            ['user' => true,  'text' => 'Am trecut printr-o despărțire dificilă, nu mai pot. Cum procedăm?'],
            ['user' => false, 'text' => 'Vă înțeleg. Avem protocol de primă ședință accelerată — slot joi 14:00 cu psih. Andrei (specialist pe traume relaționale).'],
            ['user' => true,  'text' => 'Putem mai devreme?'],
            ['user' => false, 'text' => '✓ Am găsit slot azi 17:30, online dacă preferați. Vă sun Andrei personal în următoarele 30 min pentru confirmare.'],
         ]],
    ],

    'salon-beauty' => [
        ['niche' => 'beauty', 'label' => '💆 Tratament facial', 'footer' => '✓ Agendă live', 'badge' => 'Ședință rezervată',
         'messages' => [
            ['user' => true,  'text' => 'Aveți loc pentru tratament facial hidratant?'],
            ['user' => false, 'text' => 'Da, marți 15:00 sau joi 11:00 cu Andreea (specialistă ten sensibil). Durată ~75 min, 320 lei.'],
            ['user' => true,  'text' => 'Marți e bine.'],
            ['user' => false, 'text' => '✓ Rezervat marți 15:00 cu Andreea. Dacă e prima dată, ajungeți 10 min mai devreme pentru fișă.'],
         ]],
        ['niche' => 'beauty', 'label' => '💆 Vopsit păr + mechi', 'footer' => '✓ Preț adaptat după lungime', 'badge' => 'Estimare trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Cât costă vopsitul + mechi la păr lung?'],
            ['user' => false, 'text' => 'Păr lung (sub umeri): vopsit bază + mechi baby-lights — de la 450 lei cu produse Kérastase, 320 lei cu Schwarzkopf.'],
            ['user' => true,  'text' => 'Pot veni sâmbătă dimineață?'],
            ['user' => false, 'text' => '✓ Sâmbătă 9:30 rezervat cu Raluca (expertă pe blond + mechi). Durată ~3 ore. Reminder cu o zi înainte.'],
         ]],
        ['niche' => 'beauty', 'label' => '💆 Pachet mireasă', 'footer' => '✓ Ofertă personalizată', 'badge' => 'Pachet trimis',
         'messages' => [
            ['user' => true,  'text' => 'Mă căsătoresc pe 15 iunie. Ce pachete aveți?'],
            ['user' => false, 'text' => 'Felicitări! Pachet Mireasă Completă: probă machiaj + coafură + ziua nunții + touch-up seara — 2.400 lei. Include și mama miresei la probă.'],
            ['user' => true,  'text' => 'E disponibilă data?'],
            ['user' => false, 'text' => '✓ 15 iunie e liber. Am pre-blocat slot-ul pentru 7 zile. Vă trimit pe email contractul + ce conține exact pachetul.'],
         ]],
    ],

    // ============ SERVICII PROFESIONALE ============

    'birouri-avocatura' => [
        ['niche' => 'legal', 'label' => '⚖️ Litigiu comercial', 'footer' => '✓ Calendar partener live', 'badge' => 'Consultație rezervată',
         'messages' => [
            ['user' => true,  'text' => 'Am nevoie de consultanță pentru un litigiu comercial.'],
            ['user' => false, 'text' => 'Cabinetul acoperă litigii comerciale, inclusiv recuperări creanțe. Primul slot liber: miercuri 14:00, 45 min cu av. Popescu.'],
            ['user' => true,  'text' => 'Da, cât mai repede.'],
            ['user' => false, 'text' => '✓ Programat miercuri 14:00. Confirmarea pe email, cu lista de documente utile (contract + facturi + corespondență).'],
         ]],
        ['niche' => 'legal', 'label' => '⚖️ Divorț', 'footer' => '✓ Protocol divorț', 'badge' => 'Prima ședință',
         'messages' => [
            ['user' => true,  'text' => 'Vreau să divorțez. Costă mult?'],
            ['user' => false, 'text' => 'Depinde de regim — amiabil sau litigiu. Prima consultație: 200 lei (1h). Divorț amiabil: 2.000-3.000 lei onorariu complet. Cu minori implicați: 3.500+ lei.'],
            ['user' => true,  'text' => 'E amiabil, fără copii.'],
            ['user' => false, 'text' => '✓ Recomand av. Mihaela (specialistă dreptul familiei). Am rezervat slot joi 16:00 pentru prima consultație. Adusul actelor e opțional primul pas.'],
         ]],
        ['niche' => 'legal', 'label' => '⚖️ Contract muncă', 'footer' => '✓ Verificare contract', 'badge' => 'Review comandat',
         'messages' => [
            ['user' => true,  'text' => 'Mă angajez, vreau să verific contractul înainte de semnat.'],
            ['user' => false, 'text' => 'Review contract de muncă (max 10 pagini): 350 lei + TVA. 48h timp de livrare cu observații + ședință de clarificări 30 min.'],
            ['user' => true,  'text' => 'Pot să-l trimit pe email?'],
            ['user' => false, 'text' => '✓ Da, trimiteți pe legal@firmă.ro. Av. Radu se ocupă în 48h și vă sunăm pentru ședința de discuție. Plata la final, după ce-l vedeți.'],
         ]],
    ],

    'firme-contabilitate' => [
        ['niche' => 'legal', 'label' => '📊 Pachet SRL nou', 'footer' => '✓ Ofertă adaptată', 'badge' => 'Ofertă trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Am un SRL nou, aveți pachet de contabilitate lunară?'],
            ['user' => false, 'text' => 'Felicitări! Pachetul SRL micro pornește de la 500 lei/lună — include contabilitate + declarații + consultanță.'],
            ['user' => true,  'text' => 'Ce documente îmi trebuie la început?'],
            ['user' => false, 'text' => 'Certificat înregistrare, act constitutiv și buletinele asociaților.'],
            ['user' => false, 'text' => '✓ Am programat o întâlnire joi 11:00. Vă trimit oferta detaliată pe email până diseară.'],
         ]],
        ['niche' => 'legal', 'label' => '📊 Predare declarații', 'footer' => '✓ ANAF conectat', 'badge' => 'Declarație depusă',
         'messages' => [
            ['user' => true,  'text' => 'Când e deadline-ul pentru D112?'],
            ['user' => false, 'text' => 'D112 pentru octombrie: depunere până pe 25 noiembrie, plata contribuțiilor pe 25 noiembrie.'],
            ['user' => true,  'text' => 'O depuneți voi?'],
            ['user' => false, 'text' => '✓ Da, toate clienții pachetului Standard. Am verificat contul tău — fluxul pentru luna asta e în curs, depunere pe 20 noiembrie. Primești confirmarea.'],
         ]],
        ['niche' => 'legal', 'label' => '📊 TVA la încasare', 'footer' => '✓ Consultanță fiscală', 'badge' => 'Recomandare trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Merită să trec pe sistemul de TVA la încasare?'],
            ['user' => false, 'text' => 'Depinde de cifra ta: cifra anuală sub 4,5M lei + clienți care plătesc târziu = da. Reduce stresul de cashflow.'],
            ['user' => true,  'text' => 'Încasările vin la 60-90 zile de obicei.'],
            ['user' => false, 'text' => '✓ Recomand trecerea. Cererea se depune până pe 25 ian pentru anul următor. Îți pregătesc cererea și te anunț când e gata de semnat.'],
         ]],
    ],

    'birouri-notariale' => [
        ['niche' => 'legal', 'label' => '📜 Vânzare apartament', 'footer' => '✓ Programare rapidă', 'badge' => 'Act pregătit',
         'messages' => [
            ['user' => true,  'text' => 'Vând un apartament, când putem face actul?'],
            ['user' => false, 'text' => 'Pentru vânzare apartament avem slot luni 10:00 sau miercuri 13:00. Onorar: 0,4% din valoare (minim 1.000 lei + TVA).'],
            ['user' => true,  'text' => 'Ce documente trebuie?'],
            ['user' => false, 'text' => '✓ Vă trimit check-list pe email (extras CF, act proprietate, BI, certificat fiscal, adeverință asociație). Slot luni 10:00 pre-rezervat.'],
         ]],
        ['niche' => 'legal', 'label' => '📜 Procură generală', 'footer' => '✓ Disponibil rapid', 'badge' => 'Procură redactată',
         'messages' => [
            ['user' => true,  'text' => 'Am nevoie de o procură pentru soția mea. Cât costă?'],
            ['user' => false, 'text' => 'Procură generală autentificată: 200 lei + TVA. Se face pe loc în 30 min, aveți nevoie doar de buletin.'],
            ['user' => true,  'text' => 'Pot să vin azi?'],
            ['user' => false, 'text' => '✓ Da, suntem deschiși până la 17:00. Slot liber acum la 14:30. Vă așteptăm cu buletinul + datele soției (CNP + serie/nr BI).'],
         ]],
        ['niche' => 'legal', 'label' => '📜 Contract donație', 'footer' => '✓ Rudenie verificată', 'badge' => 'Detalii trimise',
         'messages' => [
            ['user' => true,  'text' => 'Vreau să donez un teren fiului meu. Ce taxe?'],
            ['user' => false, 'text' => 'Donație la rude gradul I — scutire de impozit. Onorar notariat: 0,4% din valoare (min 800 lei + TVA) + 150 lei taxă ANCPI.'],
            ['user' => true,  'text' => 'Și un extras CF de unde?'],
            ['user' => false, 'text' => '✓ Extrasul de carte funciară îl scoatem noi (80 lei) în ziua actului. Vă trimit pe email lista completă + data posibilă.'],
         ]],
    ],

    // ============ COMERȚ & AUTO ============

    'magazine-online' => [
        ['niche' => 'auto', 'label' => '🛒 Stoc produs', 'footer' => '✓ WooCommerce live', 'badge' => 'Comandă preluată',
         'messages' => [
            ['user' => true,  'text' => 'Mai aveți vopsea albă 10L pe stoc?'],
            ['user' => false, 'text' => 'Da! 23 bucăți în stoc · 89 lei · livrare mâine 10-14 🚚'],
            ['user' => true,  'text' => 'Comanda 2 bucăți.'],
            ['user' => false, 'text' => 'Adăugat în coș. Total 178 lei + transport gratuit peste 150 lei.'],
            ['user' => false, 'text' => '✓ Comandă plasată. Primești AWB pe email mâine dimineață.'],
         ]],
        ['niche' => 'auto', 'label' => '🛒 Recomandare produs', 'footer' => '✓ Card cu preț live', 'badge' => 'Produs recomandat',
         'messages' => [
            ['user' => true,  'text' => 'Caut un laptop bun până în 4000 lei pentru lucru de acasă.'],
            ['user' => false, 'text' => 'Am 3 opțiuni în buget. Cea mai echilibrată:'],
            ['user' => false, 'product' => ['emoji' => '💻', 'name' => 'Lenovo ThinkPad E15 · 16GB RAM · 512GB SSD', 'meta' => 'Stoc 8 buc · livrare mâine', 'price' => '3.749 lei', 'old' => '4.299 lei', 'discount' => '−13%']],
            ['user' => true,  'text' => 'Pot vedea recenzii?'],
            ['user' => false, 'text' => '✓ Ți-am trimis pe email link-ul cu 284 recenzii reale + 12 poze de la clienți.'],
         ]],
        ['niche' => 'auto', 'label' => '🛒 Status comandă', 'footer' => '✓ Tracking AWB în timp real', 'badge' => 'Status verificat',
         'messages' => [
            ['user' => true,  'text' => 'Unde e comanda #4521?'],
            ['user' => false, 'text' => 'Comanda #4521 — FanCourier AWB 1234567890. Acum e în tranzit, estimat mâine 12:00-16:00.'],
            ['user' => true,  'text' => 'Pot schimba adresa?'],
            ['user' => false, 'text' => '✓ Am deschis cerere către FanCourier pentru redirecționare. Aprobare în 2 ore. Ți-o trimit pe SMS când e confirmată.'],
         ]],
        ['niche' => 'auto', 'label' => '🛒 Retur produs', 'footer' => '✓ Politică retur automată', 'badge' => 'Retur acceptat',
         'messages' => [
            ['user' => true,  'text' => 'Am primit un pantof mărime greșită, vreau să-l returnez.'],
            ['user' => false, 'text' => 'Sigur, aveți 14 zile pentru retur. Produsul nepurtat + eticheta originală. Transportul returului e gratuit.'],
            ['user' => true,  'text' => 'Cum procedez?'],
            ['user' => false, 'text' => '✓ Am generat AWB-ul de retur pe email-ul tău. Lipește-l pe colet, îl ridicăm mâine. Banii înapoi pe card în 5 zile lucrătoare.'],
         ]],
    ],

    'service-auto' => [
        ['niche' => 'auto', 'label' => '🔧 Schimb ulei', 'footer' => '✓ Istoric VIN verificat', 'badge' => 'Service rezervat',
         'messages' => [
            ['user' => true,  'text' => 'Cât costă schimbul de ulei la BMW X3?'],
            ['user' => false, 'text' => 'Ce an? Pentru X3 2020+: schimb ulei + filtru — 420 lei, durează ~1h.'],
            ['user' => true,  'text' => '2022. Marți dimineața?'],
            ['user' => false, 'text' => '✓ Marți 9:00 rezervat. Vă aștept la prima oră. Reminder cu o zi înainte.'],
         ]],
        ['niche' => 'auto', 'label' => '🔧 ITP expirat', 'footer' => '✓ Notificare automată', 'badge' => 'Programare ITP',
         'messages' => [
            ['user' => true,  'text' => 'Mi-a expirat ITP-ul, puteți să mă primiți?'],
            ['user' => false, 'text' => 'Da, avem program ITP azi 14:00-17:00 și mâine 9:00-12:00. Preț: 120 lei + 2 lei taxă RAR. Durează ~45 min.'],
            ['user' => true,  'text' => 'Azi 15:30?'],
            ['user' => false, 'text' => '✓ 15:30 rezervat. Aduceți talonul + cartea de identitate a vehiculului. Dacă trebuie regloat ceva mic, vi se oferă pe loc (opțional).'],
         ]],
        ['niche' => 'auto', 'label' => '🔧 Diagnoză motor', 'footer' => '✓ Coduri OBD citite', 'badge' => 'Diagnoză programată',
         'messages' => [
            ['user' => true,  'text' => 'Motorul face un zgomot ciudat la accelerare. Puteți să vă uitați?'],
            ['user' => false, 'text' => 'Sigur. Diagnoză cu tester OBD + test drive — 180 lei, ~30-45 min. Dacă reparația se face la noi, diagnoza se deduce.'],
            ['user' => true,  'text' => 'Când ai loc?'],
            ['user' => false, 'text' => '✓ Am rezervat slot mâine 10:30 cu tehnician Andrei. Veniți cu mașina plină de combustibil pentru test drive.'],
         ]],
    ],

    // ============ HORECA & TURISM ============

    'restaurante-delivery' => [
        ['niche' => 'resto', 'label' => '🍽️ Rezervare masă', 'footer' => '✓ Disponibilitate live', 'badge' => 'Masă confirmată',
         'messages' => [
            ['user' => true,  'text' => 'Aveți loc pentru 4 persoane vineri seara?'],
            ['user' => false, 'text' => 'Vineri 18:30 avem masă pentru 4 lângă fereastră, sau 20:00 în zona principală. Care preferi?'],
            ['user' => true,  'text' => '20:00 e perfect.'],
            ['user' => false, 'text' => '✓ Masa 7 rezervată vineri 20:00 pentru 4 persoane. Am menționat că e aniversare (preparăm mic surprize)? Confirmarea pe SMS.'],
         ]],
        ['niche' => 'resto', 'label' => '🍽️ Comandă delivery', 'footer' => '✓ Meniu live + stoc', 'badge' => 'Comandă confirmată',
         'messages' => [
            ['user' => true,  'text' => 'Aveți pizza Quattro Stagioni mare?'],
            ['user' => false, 'text' => 'Da, Quattro Stagioni 40cm — 62 lei. Livrare 30-40 min în zona Centrului, 45 lei gratuit peste 120 lei total.'],
            ['user' => true,  'text' => 'Adaug 2 pizza și o Cola 1.5L.'],
            ['user' => false, 'text' => '✓ Total 137 lei. Curierul pleacă din bucătărie în 25 min. Tracking pe SMS cu link live.'],
         ]],
        ['niche' => 'resto', 'label' => '🍽️ Meniu zilei', 'footer' => '✓ Preparat de chef', 'badge' => 'Informații trimise',
         'messages' => [
            ['user' => true,  'text' => 'Ce meniu al zilei aveți azi?'],
            ['user' => false, 'text' => 'Azi: ciorbă rădăuțeană, friptură de porc cu piure și legume, papanași cu dulceață. 42 lei complet. Și opțiune vegetariană: tocănită + mămăligă.'],
            ['user' => true,  'text' => 'La ce oră mai prindem meniul?'],
            ['user' => false, 'text' => '✓ Până la 16:00. Dacă veniți până în 14:30 prindeți și supă caldă, după aceea doar friptură. Rezerv masă?'],
         ]],
    ],

    'pensiuni-hoteluri-mici' => [
        ['niche' => 'resto', 'label' => '🏨 Rezervare weekend', 'footer' => '✓ iCal sincronizat', 'badge' => 'Rezervare trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Aveți cameră dublă pentru acest weekend?'],
            ['user' => false, 'text' => 'Da! Camera Alba liberă 22-23 apr · 280 lei/noapte, mic-dejun inclus.'],
            ['user' => true,  'text' => 'Perfect, rezerv 2 nopți.'],
            ['user' => false, 'text' => '✓ Rezervare confirmată — 2 nopți × 280 = 560 lei. Link de plată trimis pe email, valabil 24h.'],
         ]],
        ['niche' => 'resto', 'label' => '🏨 Check-in târziu', 'footer' => '✓ Self check-in', 'badge' => 'Instrucțiuni trimise',
         'messages' => [
            ['user' => true,  'text' => 'Ajung pe la 23:00, e OK?'],
            ['user' => false, 'text' => 'Fără probleme — avem self check-in 24/7. Vă trimit cod de intrare pe SMS cu 2 ore înainte de sosire.'],
            ['user' => true,  'text' => 'Parcarea e disponibilă?'],
            ['user' => false, 'text' => '✓ Da, parcare proprie, gratuită. Locul 7 rezervat pe numele tău. Wi-fi-ul la recepție pe ușă.'],
         ]],
        ['niche' => 'resto', 'label' => '🏨 Pachet romantic', 'footer' => '✓ Ofertă ajustată', 'badge' => 'Pachet rezervat',
         'messages' => [
            ['user' => true,  'text' => 'Aveți ceva special pentru aniversarea noastră?'],
            ['user' => false, 'text' => 'Pachet Romantic: cameră decorată + cină cu vin la restaurant + mic-dejun în cameră + mic dejun tip platou fruct — 780 lei (2 nopți).'],
            ['user' => true,  'text' => 'Sună perfect pentru 15 mai.'],
            ['user' => false, 'text' => '✓ Rezervat pentru 15-17 mai. Ce fel de decor preferați (trandafiri / lumânări / ambele)? Și alergii la mâncare?'],
         ]],
    ],

    'agentii-turism' => [
        ['niche' => 'travel', 'label' => '✈️ Vacanță Grecia', 'footer' => '✓ Listă oferte live', 'badge' => 'Ofertă trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Caut vacanță all-inclusive în Grecia pentru iulie, 2 adulți + 1 copil.'],
            ['user' => false, 'text' => 'Am 3 oferte potrivite. Cea mai bună raport: Halkidiki 5*, 7 nopți AI, 10-17 iulie, plecare București. Prețul e ajustat pentru familie cu copil.'],
            ['user' => true,  'text' => 'Cât costă?'],
            ['user' => false, 'text' => '✓ 3.890 € total (copil gratis). Plata 30% acum + restul până pe 20 iunie. Vă trimit pe email fișa completă + 4 alternative.'],
         ]],
        ['niche' => 'travel', 'label' => '✈️ Bilet avion', 'footer' => '✓ Comparare prețuri', 'badge' => 'Bilet rezervat',
         'messages' => [
            ['user' => true,  'text' => 'Caut zbor București-Londra pentru 20 august.'],
            ['user' => false, 'text' => 'Cel mai ieftin: Wizz Air 20 aug 6:45, bagaj mic inclus, 420 lei dus-întors. TAROM 20 aug 14:10 cu bagaj cală inclus — 890 lei.'],
            ['user' => true,  'text' => 'Wizz Air.'],
            ['user' => false, 'text' => '✓ Rezervat provizoriu 30 min. Vă trimit link de plată pe email. Confirmarea finală după plată, cu boarding pass direct în app.'],
         ]],
        ['niche' => 'travel', 'label' => '✈️ Asigurare călătorie', 'footer' => '✓ Emisă pe loc', 'badge' => 'Asigurare emisă',
         'messages' => [
            ['user' => true,  'text' => 'Pot face asigurare de călătorie pentru Turcia?'],
            ['user' => false, 'text' => 'Da. Standard: 12 lei/zi/persoană (medicală + bagaje). Premium: 22 lei/zi (+ anulare zbor, evacuare).'],
            ['user' => true,  'text' => '10 zile, 2 persoane, premium.'],
            ['user' => false, 'text' => '✓ 440 lei total. Plata pe card, polița pe email în 5 min. Pe polița cu cod QR, accesibilă și din app.'],
         ]],
    ],

    // ============ IMOBILIARE · EDUCAȚIE · SERVICII ============

    'agentii-imobiliare' => [
        ['niche' => 'imob', 'label' => '🏠 Căutare apartament', 'footer' => '✓ Listări filtrate', 'badge' => 'Vizionare programată',
         'messages' => [
            ['user' => true,  'text' => 'Caut apartament 2 camere în Cluj, până în 95.000 €.'],
            ['user' => false, 'text' => 'Am 4 proprietăți în buget. Cea mai potrivită: 58 mp, Mărăști, etaj 3 — 89.000 €.'],
            ['user' => true,  'text' => 'Aș vrea să o văd sâmbătă.'],
            ['user' => false, 'text' => '✓ Sâmbătă 11:00 cu agentul Andrei. Vă trimit adresa exactă + pre-validarea pe WhatsApp.'],
         ]],
        ['niche' => 'imob', 'label' => '🏠 Estimare preț', 'footer' => '✓ Comparabile piață', 'badge' => 'Evaluare trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Vreau să estimez cât costă apartamentul meu.'],
            ['user' => false, 'text' => 'Trimite-mi: suprafață, etaj, an construcție, zonă, câte camere. Am 2.400 tranzacții comparabile pentru evaluare.'],
            ['user' => true,  'text' => '65 mp, etaj 4, 2012, Mărăști, 2 camere.'],
            ['user' => false, 'text' => '✓ Estimare: 92.000-98.000 € (bazat pe 15 tranzacții similare). Vă trimit raport PDF cu comparabilele. Dacă vreți vânzare asistată, vorbim detalii.'],
         ]],
        ['niche' => 'imob', 'label' => '🏠 Închiriere garsonieră', 'footer' => '✓ Disponibilitate live', 'badge' => 'Vizionare rezervată',
         'messages' => [
            ['user' => true,  'text' => 'Aveți garsoniere de închiriat în Centru?'],
            ['user' => false, 'text' => 'Da, 2 disponibile. Zamca, mobilată complet, 350 €/lună + utilități, disponibilă de 1 iunie. Eminescu, nemobilată, 280 €/lună.'],
            ['user' => true,  'text' => 'Zamca, pot vedea azi?'],
            ['user' => false, 'text' => '✓ Azi 18:00 vizionare cu agentul Raluca. Aduceți buletin pentru eventual pre-contract dacă vă place pe loc.'],
         ]],
    ],

    'scoli-limbi-straine' => [
        ['niche' => 'education', 'label' => '🎓 Test nivel', 'footer' => '✓ Evaluare gratuită', 'badge' => 'Nivel stabilit',
         'messages' => [
            ['user' => true,  'text' => 'Aș vrea să învăț engleză. Cum îmi evaluați nivelul?'],
            ['user' => false, 'text' => 'Test gratuit online 20 min + conversație 15 min cu profesorul. Am slot-uri luni-vineri 10:00-19:00.'],
            ['user' => true,  'text' => 'Vineri 17:00 e bine.'],
            ['user' => false, 'text' => '✓ Programat vineri 17:00 cu profesorul Ian. Primești link-ul cu testul pe email + întâlnirea Zoom.'],
         ]],
        ['niche' => 'education', 'label' => '🎓 Curs Cambridge', 'footer' => '✓ Grupe live', 'badge' => 'Înscriere rezervată',
         'messages' => [
            ['user' => true,  'text' => 'Vreau să dau First Certificate. Ce cursuri aveți?'],
            ['user' => false, 'text' => 'Curs pregătire FCE — 12 săptămâni, 2×/săpt, max 8 elevi per grupă. Include simulări + material oficial Cambridge. 1.850 lei total.'],
            ['user' => true,  'text' => 'Când începe următoarea grupă?'],
            ['user' => false, 'text' => '✓ 15 mai, marți/joi 18:00-19:30. 3 locuri libere. Am pre-rezervat un loc — plata primei tranșe (500 lei) confirmă.'],
         ]],
        ['niche' => 'education', 'label' => '🎓 Curs firmă', 'footer' => '✓ Adaptat echipei', 'badge' => 'Ofertă personalizată',
         'messages' => [
            ['user' => true,  'text' => 'Vreau curs engleză pentru echipa mea, 8 persoane.'],
            ['user' => false, 'text' => 'Curs corporate personalizat: focus pe vocabular din domeniul vostru (tech, medical, financiar, general). 2 ore/săpt × 20 săpt — 7.200 lei total pentru grupa.'],
            ['user' => true,  'text' => 'Suntem echipă IT.'],
            ['user' => false, 'text' => '✓ Vă propun pachetul Business English IT — vocabular technical + daily stand-up + email + prezentări. Factura pe firmă cu CUI. Întâlnire demo joi la sediu?'],
         ]],
    ],

    'firme-curatenie' => [
        ['niche' => 'education', 'label' => '🧹 Curățenie apartament', 'footer' => '✓ Preț fix', 'badge' => 'Programare rezervată',
         'messages' => [
            ['user' => true,  'text' => 'Cât costă curățenia unui apartament de 60 mp?'],
            ['user' => false, 'text' => 'Curățenie generală 60 mp: 220 lei (durată 3-4h, 2 operatori). Include geamuri, aspirator, mop, sanitare, praf.'],
            ['user' => true,  'text' => 'Joi dimineața?'],
            ['user' => false, 'text' => '✓ Joi 10:00 rezervat cu echipa Ana + Maria. Reminder pe SMS cu o zi înainte. Plata la final cu cardul sau cash.'],
         ]],
        ['niche' => 'education', 'label' => '🧹 Abonament lunar', 'footer' => '✓ Reducere fidelitate', 'badge' => 'Pachet activat',
         'messages' => [
            ['user' => true,  'text' => 'Vreau curățenie regulată, o dată pe săptămână.'],
            ['user' => false, 'text' => 'Pachet săptămânal 60 mp: 180 lei/intervenție (reducere 18% vs one-time). Plăți lunare (780 lei/lună).'],
            ['user' => true,  'text' => 'Putem începe săptămâna asta?'],
            ['user' => false, 'text' => '✓ Da, joi 10:00 prima intervenție. Dacă e totul OK, abonamentul continuă automat. Poți opri oricând fără penalitate.'],
         ]],
        ['niche' => 'education', 'label' => '🧹 Post-construcție', 'footer' => '✓ Specialiști post-șantier', 'badge' => 'Ofertă trimisă',
         'messages' => [
            ['user' => true,  'text' => 'Am terminat renovarea, apartamentul e plin de praf de construcții.'],
            ['user' => false, 'text' => 'Curățenie post-construcție 60 mp: 480 lei (3 operatori, 6-8 ore). Include toate resturile de praf de gips, vopsea, silicon. Utilaj industrial.'],
            ['user' => true,  'text' => 'Când ai disponibilitate?'],
            ['user' => false, 'text' => '✓ Sâmbătă 8:00 rezervat cu echipa specializată. Avem experiență pe apartamente noi — garantat „mutabil" după ce plecăm.'],
         ]],
    ],

];
