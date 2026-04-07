@extends('layouts.app')

@section('title', 'De ce Sambla — Comparație, arhitectură și diferențiatori | Sambla')
@section('meta_description', 'De ce Sambla nu e încă un chatbot. RAG real cu hybrid search, 10 straturi anti-halucinare, voce nativă în română, hosting în România. Comparație tehnică cu alternative.')

@section('jsonld')
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","@id":"https://sambla.ro/de-ce-sambla#webpage","url":"https://sambla.ro/de-ce-sambla","name":"De ce Sambla","description":"Diferențiatori tehnici și de produs ai platformei Sambla față de chatbot-uri scriptate, wrapper-e GPT, soluții call-center clasice și landing-page-only AI startups.","isPartOf":{"@id":"https://sambla.ro/#website"},"about":{"@id":"https://sambla.ro/#software"},"inLanguage":"ro-RO"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"În ce diferă Sambla de un chatbot scriptat clasic?","acceptedAnswer":{"@type":"Answer","text":"Chatbot-urile scriptate funcționează pe arbori de decizie: «dacă utilizatorul spune X, răspunde Y». Sunt în esență motoare de reguli — nu pot răspunde la întrebări neașteptate și nu citesc PDF-uri. Sambla nu are flowchart-uri. Fiecare răspuns e generat în timp real de un LLM care a căutat în baza ta de cunoștințe reală prin RAG cu hybrid search (vector + full-text românesc) și un AI reranker."}},
{"@type":"Question","name":"De ce Sambla nu e doar «GPT cu prompt»?","acceptedAnswer":{"@type":"Answer","text":"Un wrapper LLM doar concatenează întrebarea cu un system prompt și trimite la GPT — halucinează des și nu cunoaște datele tale. Sambla rulează un pipeline 4-stadii: clasificare intenție → retrieval hibrid (vectori 1536-dim + text complet românesc + reranker AI) → strategie de conversație → 10 straturi de verificare anti-halucinare. Nu e wrapper, e sistem RAG end-to-end."}},
{"@type":"Question","name":"Sambla e legată de Sambla Group (credite, împrumuturi)?","acceptedAnswer":{"@type":"Answer","text":"Nu, niciun fel de legătură. Sambla este o platformă AI românească pentru chatboți și voiceboți, creată de o echipă din România pentru afaceri din România. NU oferim credite, împrumuturi, leasing, IFN, asigurări, conturi bancare sau orice serviciu financiar. Suntem o companie de software AI, nu o instituție financiară."}},
{"@type":"Question","name":"Cum garantați că AI-ul nu inventează răspunsuri?","acceptedAnswer":{"@type":"Answer","text":"Prin 10 straturi de verificare aplicate la fiecare răspuns: base prompt blocat (interzis să inventeze prețuri/termene), politica conversației, contextul produselor + KB, regulile de business, query intelligence cu prag de confidență, strategia conversației per stadiu, scor numeric de confidență, detector frustrare live și verificare finală anti-halucinare contra chunk-urilor sursă. Dacă un strat eșuează, bot-ul cere clarificare sau escaladează la operator uman."}},
{"@type":"Question","name":"De ce contează că hosting-ul e în România?","acceptedAnswer":{"@type":"Answer","text":"Datele clienților tăi nu părăsesc niciodată România/UE. GDPR by default, izolare per cont (un client nu poate accesa datele altuia, nici accidental), backup-uri criptate cu retenție 30 zile, audit log pe acces. Echipa de suport e românească, vorbește românește. Dacă e o investigație GDPR, totul e traceabil pe servere RO."}},
{"@type":"Question","name":"Câte canale de comunicare suportă Sambla simultan?","acceptedAnswer":{"@type":"Answer","text":"Cinci canale cu același bot și același context de conversație: chat web, telefon (numere RO native via Telnyx + voce GPT-4o Realtime), WhatsApp, Facebook Messenger și Instagram DM. Clientul începe conversația pe site, sună după două ore — bot-ul ține minte ce a discutat. Acesta e «multi-canal cu același creier», nu cinci bot-uri separate."}}
]}
</script>
@endsection

@section('content')

{{-- ============================================================== --}}
{{-- HERO --}}
{{-- ============================================================== --}}
<section class="relative bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-red-950/40"></div>
    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 25% 25%, #dc2626 0%, transparent 50%), radial-gradient(circle at 75% 75%, #1e293b 0%, transparent 50%);"></div>

    <div class="container-custom relative">
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 bg-red-950/50 border border-red-800/50 text-red-300 text-xs font-bold px-4 py-2 rounded-full mb-6 backdrop-blur">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                DE CE SAMBLA
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.08] tracking-tight text-white mb-6">
                Nu e încă un chatbot.<br>
                <span class="text-red-500">E un sistem RAG complet.</span>
            </h1>
            <p class="text-lg lg:text-xl text-slate-300 leading-relaxed mb-8">
                Pe scurt: hybrid search peste documentele tale reale, voce nativă în română, 10 straturi anti-halucinare, hosting în România. Mai jos vezi exact ce înseamnă fiecare.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="/register" class="inline-flex items-center gap-2 px-7 py-4 bg-gradient-to-r from-red-700 to-red-600 text-white font-bold rounded-xl hover:from-red-600 hover:to-red-500 transition-all duration-300 shadow-lg shadow-red-900/40">
                    Începe gratuit
                </a>
                <a href="/preturi" class="inline-flex items-center gap-2 px-7 py-4 bg-white/10 backdrop-blur border border-white/20 text-white font-bold rounded-xl hover:bg-white/20 transition-all duration-300">
                    Vezi prețurile
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CATEGORICAL COMPARISON TABLE --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 py-16 lg:py-24">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Față de ce mai există pe piață</h2>
            <p class="text-lg text-slate-500">Patru categorii de „soluții AI" cu care suntem comparați des. Vezi exact unde ne diferențiem.</p>
        </div>

        <div class="overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0">
            <table class="w-full bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-sm">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="px-4 py-4 text-left font-bold w-[24%]">Capabilitate</th>
                        <th class="px-4 py-4 text-center font-bold">Chatbot scriptat<br><span class="text-xs font-normal text-slate-300">flowchart-uri</span></th>
                        <th class="px-4 py-4 text-center font-bold">Wrapper GPT<br><span class="text-xs font-normal text-slate-300">«custom GPT»</span></th>
                        <th class="px-4 py-4 text-center font-bold">Call-center clasic<br><span class="text-xs font-normal text-slate-300">IVR + STT</span></th>
                        <th class="px-4 py-4 text-center font-bold bg-red-700">Sambla</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $rows = [
                            ['Răspunde la întrebări neașteptate', '❌', '✅', '⚠️ limitat', '✅'],
                            ['Citește PDF/DOCX/CSV-uri reale', '❌', '⚠️ doar prin upload manual', '❌', '✅ ingest automat'],
                            ['RAG real cu hybrid search', '❌', '❌', '❌', '✅ vector + full-text RO'],
                            ['AI reranker pentru relevanță', '❌', '❌', '❌', '✅ cross-encoder peste 20 candidați'],
                            ['Anti-halucinare cu 10 straturi', '—', '❌', '—', '✅'],
                            ['Cite source pentru fiecare răspuns', '—', '⚠️ uneori', '—', '✅ document + chunk'],
                            ['Voce nativă română (intonație + diacritice)', '—', '—', '⚠️ TTS sandwich', '✅ GPT-4o Realtime'],
                            ['Multi-canal cu același creier', '⚠️ doar 1-2 canale', '❌', '❌', '✅ web + voice + WA + FB + IG'],
                            ['Detectare frustrare live + escaladare', '❌', '❌', '⚠️ doar pe text', '✅ text și voce'],
                            ['Setup sub 1 oră, fără cod', '⚠️ flowchart manual', '⚠️ dev needed', '❌ săptămâni', '✅ wizard'],
                            ['Hosting fizic în România', '—', '❌ US/EU-West', '—', '✅ servere RO'],
                            ['GDPR by default, izolare per cont', '—', '❌', '⚠️', '✅ row-level isolation'],
                            ['Învață din întrebările fără răspuns', '❌', '❌', '❌', '✅ FAQ generation'],
                            ['Integrare WooCommerce nativă', '⚠️ plugin terț', '❌', '❌', '✅ produse + stoc + cart'],
                            ['Suport în română de la oameni reali', '—', '—', '⚠️', '✅ echipă RO'],
                        ];
                    @endphp
                    @foreach($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $r[0] }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $r[1] }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $r[2] }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $r[3] }}</td>
                            <td class="px-4 py-3 text-center bg-red-50 font-bold text-red-700">{{ $r[4] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 mt-4 text-center">Comparațiile sunt categoriale. Sambla nu se compară cu un brand anume, ci cu tipul general de produs.</p>
    </div>
</section>

{{-- ============================================================== --}}
{{-- 4-STAGE RAG PIPELINE --}}
{{-- ============================================================== --}}
<section class="bg-white py-16 lg:py-24">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                ARHITECTURĂ
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Pipeline-ul de 4 stadii</h2>
            <p class="text-lg text-slate-500">Fiecare mesaj pe care îl primește bot-ul tău trece prin aceste 4 stadii. Total: sub 2 secunde.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            @php
                $stages = [
                    ['1','Înțelegere intenție','Clasifică mesajul ca informațional, tranzacțional sau reclamație. Schimbă strategia bot-ului în consecință.','bg-blue-50','text-blue-700','border-blue-200'],
                    ['2','Hybrid Search RAG','Vector search 1536-dim + full-text românesc cu stemming, fuzionate. AI reranker peste 20 candidați → top 8 chunks.','bg-amber-50','text-amber-700','border-amber-200'],
                    ['3','Strategie conversație','Începutul conversației înțelege nevoia. Mijlocul recomandă. Finalul cere lead/escaladare. Diferit per stadiu.','bg-emerald-50','text-emerald-700','border-emerald-200'],
                    ['4','Verificare 10 straturi','Răspunsul generat trece prin 10 verificări înainte să ajungă la client. Anti-halucinare e ultimul filtru.','bg-red-50','text-red-700','border-red-200'],
                ];
            @endphp
            @foreach($stages as $s)
                <div class="bg-white rounded-2xl border-2 {{ $s[5] }} p-6 hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 rounded-xl {{ $s[3] }} flex items-center justify-center mb-4">
                        <span class="text-xl font-extrabold {{ $s[4] }}">{{ $s[0] }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $s[1] }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $s[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- 10 ANTI-HALLUCINATION LAYERS --}}
{{-- ============================================================== --}}
<section class="bg-slate-950 py-16 lg:py-24">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-emerald-950/50 border border-emerald-800/50 text-emerald-300 text-xs font-bold px-3 py-1.5 rounded-full mb-4 backdrop-blur">
                ANTI-HALUCINARE
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-5 tracking-tight">10 straturi de verificare</h2>
            <p class="text-lg text-slate-400">Fiecare răspuns trece prin toate. Dacă un strat eșuează, bot-ul nu inventează — escaladează la om.</p>
        </div>

        @php
            $layers = [
                ['01','Base prompt','Reguli locked: niciodată să nu inventeze prețuri, stoc sau termene'],
                ['02','Politica conversației','Per-business: ton, ce subiecte refuză, cum vorbește'],
                ['03','Context produse + KB','Chunk-urile reale extrase din baza ta de cunoștințe'],
                ['04','Reguli comenzi','Date tranzacționale verificate contra produselor și stocurilor reale'],
                ['05','Stil răspuns','Cum vrea brand-ul tău să sune bot-ul'],
                ['06','Query Intelligence','S-a înțeles întrebarea? Dacă scor < prag, cere clarificare'],
                ['07','Strategia conversației','Politica per stadiu (început/mijloc/final)'],
                ['08','Scor de confidență','Confidență numerică pe răspunsul generat'],
                ['09','Detector frustrare','Semnale text + voce clasificate live, escaladare automată'],
                ['10','Verificare anti-halucinare','Răspunsul se compară contra chunk-urilor sursă înainte de send'],
            ];
        @endphp

        <div class="max-w-3xl mx-auto space-y-3">
            @foreach($layers as $l)
                <div class="flex items-start gap-4 p-5 bg-white/5 backdrop-blur rounded-xl border border-white/10 hover:border-emerald-500/50 transition-colors">
                    <div class="w-12 h-12 rounded-lg bg-emerald-950/50 border border-emerald-800/50 flex items-center justify-center shrink-0">
                        <span class="text-sm font-extrabold text-emerald-400">{{ $l[0] }}</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-white mb-1">{{ $l[1] }}</h3>
                        <p class="text-sm text-slate-400">{{ $l[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- ROMANIA ADVANTAGE --}}
{{-- ============================================================== --}}
<section class="bg-white py-16 lg:py-24">
    <div class="container-custom">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center gap-2 bg-red-50 text-red-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                    🇷🇴 BUILT IN ROMANIA
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-6 tracking-tight">De ce contează că suntem din România</h2>
                <p class="text-lg text-slate-500 mb-6">Pentru afaceri românești, hosting-ul și echipa locală nu sunt detaliu — sunt avantaj competitiv direct.</p>
                <ul class="space-y-4">
                    <li class="flex gap-3">
                        <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-bold text-slate-900">Servere fizice în România</p>
                            <p class="text-sm text-slate-500">Zero transfer de date în afara UE. Servesc inclusiv direct GDPR audit-uri.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-bold text-slate-900">Voce română nativă</p>
                            <p class="text-sm text-slate-500">Intonație, pauze, diacritice ă â î ș ț corecte. Nu accent franțuzesc, nu robotic TTS.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-bold text-slate-900">25 grupuri sinonime românești în motorul de căutare</p>
                            <p class="text-sm text-slate-500">„retur" = „returnare" = „înapoiere". Stemming românesc nativ în full-text search.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-bold text-slate-900">Numere de telefon românești native</p>
                            <p class="text-sm text-slate-500">Telnyx ne dă numere RO direct. Clienții tăi sună un 0775 / 0376, nu un internațional.</p>
                        </div>
                    </li>
                    <li class="flex gap-3">
                        <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-bold text-slate-900">Suport pe românește cu oameni reali</p>
                            <p class="text-sm text-slate-500">Fără tichete în engleză prin trei nivele de suport în India.</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- Stack panel --}}
            <div class="bg-slate-900 rounded-2xl p-8 border border-slate-800">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5">Stack tehnic public</p>
                <div class="space-y-3">
                    @foreach([
                        ['Backend','Laravel 11 · PHP 8.3'],
                        ['Database','PostgreSQL 16 + pgvector'],
                        ['Cache + Queue','Redis 7'],
                        ['Voice','OpenAI Realtime API (GPT-4o)'],
                        ['Telephony','Telnyx (numere RO)'],
                        ['Search','Hybrid: vector 1536-dim + full-text BM25 + AI reranker'],
                        ['Image gen','Vertex AI Gemini'],
                        ['CDN','Cloudflare cu Brotli, HTTP/3, edge cache'],
                        ['Hosting','Servere fizice 🇷🇴 RO']
                    ] as $row)
                        <div class="flex justify-between items-baseline gap-4 pb-2 border-b border-slate-800 last:border-b-0">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider shrink-0">{{ $row[0] }}</span>
                            <span class="text-sm text-slate-200 text-right">{{ $row[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- WHAT SAMBLA IS NOT --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 py-16 lg:py-24">
    <div class="container-custom">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Ce NU este Sambla</h2>
                <p class="text-lg text-slate-500">Disambiguăm explicit ca să nu fie confuzii.</p>
            </div>

            <div class="space-y-4">
                @php
                    $nots = [
                        ['Nu suntem Sambla Group', 'Sambla.ro este o platformă AI românească pentru chatboți și voiceboți, distinctă de Sambla Group sau orice alt brand cu nume similar. NU oferim credite, împrumuturi, leasing, IFN, asigurări, conturi bancare sau orice serviciu financiar regulat. Suntem o companie de software AI, nu o instituție financiară.'],
                        ['Nu suntem un chatbot template', 'Fiecare bot Sambla e ancorat în documentele și produsele clientului prin RAG real, nu într-o bază FAQ generică partajată între clienți.'],
                        ['Nu suntem un wrapper de GPT', 'Avem un pipeline 4-stadii (intent → retrieve → strategy → generate) cu 10 straturi de verificare, hybrid search vector + full-text românesc, AI reranker, detector frustrare live. Eliminați orice strat și produsul nu mai funcționează.'],
                        ['Nu suntem un AI care inventează', 'Când bot-ul nu știe, spune cinstit. Când nu e sigur, escaladează la operator uman. Fiecare răspuns poate cita documentul-sursă.'],
                        ['Nu suntem un tool american tradus în română', 'Construit nativ în România, în română, pe servere românești, de o echipă românească. Vocea are intonație corectă și diacritice ă â î ș ț. Search-ul cunoaște morfologia românească.'],
                        ['Nu înlocuim profesionistul', 'În fiecare verticală (medical, juridic, financiar, psihologic), Sambla este unealta profesionistului — niciodată nu oferă consultanță care necesită licență.'],
                    ];
                @endphp
                @foreach($nots as $n)
                    <div class="bg-white rounded-xl border border-slate-200 p-6 hover:border-red-300 transition-colors">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-2">{{ $n[0] }}</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $n[1] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- METRICS (placeholder values — replace with real once available) --}}
{{-- ============================================================== --}}
<section class="bg-white py-16 lg:py-24 border-t border-slate-100">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Cifrele care contează</h2>
            <p class="text-lg text-slate-500">Performanță reală a pipeline-ului, măsurată în producție.</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto">
            @foreach([
                ['<2s','Latency end-to-end','RAG complet: clasificare → search → strategy → generare'],
                ['1536','Dimensiuni vector','Embeddings stocate în pgvector pentru similaritate semantică'],
                ['10','Straturi anti-halucinare','Verificate la fiecare răspuns'],
                ['25','Grupuri sinonime RO','Cunoscute nativ în motorul de căutare'],
                ['8','Chunks per query','Aleși din 20 de candidați rerankate'],
                ['5','Canale unificate','Web + voice + WhatsApp + FB + IG cu un singur context'],
                ['~10min','Setup live','De la cont nou la bot pe site-ul clientului'],
                ['100%','Hosting 🇷🇴','Servere fizice în România, zero date în afara UE'],
            ] as $m)
                <div class="bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-200 p-6 text-center">
                    <p class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-1">{{ $m[0] }}</p>
                    <p class="text-sm font-bold text-red-600 mb-2">{{ $m[1] }}</p>
                    <p class="text-xs text-slate-500 leading-snug">{{ $m[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CTA --}}
{{-- ============================================================== --}}
<section class="bg-gradient-to-br from-red-700 to-red-900 py-16 lg:py-24">
    <div class="container-custom">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-5 tracking-tight">Testează platforma în 10 minute</h2>
            <p class="text-lg text-red-100 mb-8">Cont nou, încarci 2-3 documente, vezi cum răspunde bot-ul în limba ta. Fără card.</p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="/register" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-red-700 font-bold rounded-xl hover:bg-red-50 transition-all shadow-lg">
                    Începe gratuit
                </a>
                <a href="/contact" class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur border border-white/30 text-white font-bold rounded-xl hover:bg-white/20 transition-all">
                    Vorbește cu noi
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
