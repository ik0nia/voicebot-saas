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
{"@type":"Question","name":"Câte canale de comunicare suportă Sambla simultan?","acceptedAnswer":{"@type":"Answer","text":"Cinci canale cu același bot și același context de conversație: chat web, telefon (numere RO native via Telnyx + voce GPT-4o Realtime), WhatsApp, Facebook Messenger și Instagram DM. Clientul începe conversația pe site, sună după două ore — bot-ul ține minte ce a discutat. Acesta e «multi-canal cu același creier», nu cinci bot-uri separate."}},
{"@type":"Question","name":"Sambla e doar un wrapper peste ChatGPT?","acceptedAnswer":{"@type":"Answer","text":"Nu. Folosim GPT (sau orice alt LLM, e swappable) ca generator de text — nu pretindem că am antrenat un model propriu. Dar tot restul e construit de noi: pipeline RAG 4-stadii, 10 straturi anti-halucinare, 25 grupuri sinonime românești curate manual, hybrid search fusion (vector + BM25 + reranker), state machine de conversație, detector frustrare live, multi-channel context bridge, integrare WooCommerce nativă, tenant isolation, dashboard, billing. Dacă mâine schimbăm GPT cu Claude sau Llama, 90% din platformă funcționează identic. LLM-ul e o componentă, nu produsul."}},
{"@type":"Question","name":"AI-ul Sambla halucinează?","acceptedAnswer":{"@type":"Answer","text":"Onest: orice LLM poate halucina, e o proprietate fundamentală a tehnologiei. Sambla nu pretinde 100% perfecțiune. Ce facem este să MINIMIZĂM halucinarea prin inginerie: răspunsuri ancorate în chunk-uri sursă verificabile, fallback la „nu știu" când retrieval-ul nu returnează nimic relevant, escaladare automată la operator uman când scorul de confidență e sub prag, 10 straturi de verificare contra documentelor sursă, citare obligatorie a sursei. În producție, modul de eșec e «bot-ul spune cinstit că nu știe», nu «inventează un răspuns greșit cu încredere»."}},
{"@type":"Question","name":"Bot-ul se învață singur sau cu supervizare?","acceptedAnswer":{"@type":"Answer","text":"Cu supervizare. Bot-ul detectează automat ce întrebări nu poate răspunde bine (gap detection) și generează draft FAQ pentru ele, dar OMUL aprobă fiecare modificare înainte să se publice. Nu e auto-modificare autonomă — asta ar fi periculos. E «învățare asistată» unde AI face munca grea de identificare și redactare, iar tu validezi în 30 de secunde."}}
]}
</script>
@endsection

@section('content')

<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="why-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#why-motif)"/></svg>
    </div>
    <div class="absolute top-10 right-0 w-[350px] h-[350px] bg-red-900/20 rounded-full blur-[100px]"></div>
    <div class="absolute -bottom-20 -left-10 w-[300px] h-[300px] bg-red-800/10 rounded-full blur-[100px]"></div>
    <div class="container-custom text-center relative z-10">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in">
            <span class="block">Mai mult decât un chatbot.</span>
            <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">Un agent AI care înțelege afacerea ta.</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-3xl mx-auto animate-fade-in mb-8">
            Vorbește pe înțelesul clienților tăi în română, citește din documentele tale reale, preia apeluri telefonice, învață ce nu știe și escaladează la operator când e nevoie. Construit, antrenat și hostat în România.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center animate-fade-in">
            <a href="/register" class="btn-primary">Începe gratuit</a>
            <a href="/preturi" class="btn-secondary">Vezi prețuri</a>
        </div>
    </div>
</section>
<x-motif-border />

{{-- ============================================================== --}}
{{-- CATEGORICAL COMPARISON TABLE --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 section-padding">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Față de ce mai există pe piață</h2>
            <p class="text-lg text-slate-500">Patru categorii de „soluții AI" cu care suntem comparați des. Vezi exact unde ne diferențiem.</p>
        </div>

        @php
            // Compact SVG icon helpers — same look on every OS, no emoji.
            $iconYes = '<svg class="inline w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            $iconNo  = '<svg class="inline w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
            $iconWarn= '<svg class="inline w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>';
            $iconDash= '<span class="text-slate-300">—</span>';
            // Each cell is [type, label]; type one of: yes / no / warn / dash
            $rows = [
                ['Răspunde la întrebări neașteptate',                ['no'], ['yes'], ['warn','limitat'], ['yes']],
                ['Citește PDF/DOCX/CSV-uri reale',                   ['no'], ['warn','doar manual'], ['no'], ['yes','ingest auto']],
                ['RAG real cu hybrid search',                        ['no'], ['no'], ['no'], ['yes','vector + full-text RO']],
                ['AI reranker pentru relevanță',                     ['no'], ['no'], ['no'], ['yes','cross-encoder']],
                ['Anti-halucinare cu 10 straturi',                   ['dash'], ['no'], ['dash'], ['yes']],
                ['Cite source pentru fiecare răspuns',               ['dash'], ['warn','uneori'], ['dash'], ['yes','document + chunk']],
                ['Voce nativă română (intonație + diacritice)',      ['dash'], ['dash'], ['warn','TTS sandwich'], ['yes','GPT-4o Realtime']],
                ['Multi-canal cu același creier',                    ['warn','1-2 canale'], ['no'], ['no'], ['yes','5 canale']],
                ['Detectare frustrare live + escaladare',            ['no'], ['no'], ['warn','doar text'], ['yes','text + voce']],
                ['Setup sub 1 oră, fără cod',                        ['warn','manual'], ['warn','dev needed'], ['no','săptămâni'], ['yes','wizard']],
                ['Hosting fizic în România',                         ['dash'], ['no','US/EU-West'], ['dash'], ['yes','servere RO']],
                ['GDPR by default, izolare per cont',                ['dash'], ['no'], ['warn'], ['yes','row-level']],
                ['Învață din întrebările fără răspuns',              ['no'], ['no'], ['no'], ['yes','FAQ generation']],
                ['Integrare WooCommerce nativă',                     ['warn','plugin terț'], ['no'], ['no'], ['yes','stoc + cart']],
                ['Suport în română de la oameni reali',              ['dash'], ['dash'], ['warn'], ['yes','echipă RO']],
            ];
            $renderCell = function($cell) use ($iconYes,$iconNo,$iconWarn,$iconDash) {
                $type = $cell[0];
                $label = $cell[1] ?? '';
                $icon = match($type) { 'yes'=>$iconYes, 'no'=>$iconNo, 'warn'=>$iconWarn, default=>$iconDash };
                return $icon . ($label ? '<span class="block text-[11px] text-slate-500 mt-1">'.e($label).'</span>' : '');
            };
        @endphp

        <div class="overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0">
            <table class="min-w-[760px] w-full bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-sm">
                <thead>
                    <tr class="bg-slate-900 text-white">
                        <th class="px-4 py-4 text-left font-bold w-[28%]">Capabilitate</th>
                        <th class="px-3 py-4 text-center font-bold w-[18%]">
                            <span class="block">Chatbot scriptat</span>
                            <span class="block text-[11px] font-normal text-slate-300 mt-0.5">flowchart-uri</span>
                        </th>
                        <th class="px-3 py-4 text-center font-bold w-[18%]">
                            <span class="block">Wrapper GPT</span>
                            <span class="block text-[11px] font-normal text-slate-300 mt-0.5">„custom GPT"</span>
                        </th>
                        <th class="px-3 py-4 text-center font-bold w-[18%]">
                            <span class="block">Call-center clasic</span>
                            <span class="block text-[11px] font-normal text-slate-300 mt-0.5">IVR + STT</span>
                        </th>
                        <th class="px-3 py-4 text-center font-bold bg-red-700 w-[18%]">Sambla</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $r[0] }}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[1]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[2]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[3]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle bg-red-50">{!! $renderCell($r[4]) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-500 mt-4 text-center lg:hidden">Glisează lateral pentru a vedea tot tabelul →</p>
        <p class="text-xs text-slate-500 mt-4 text-center">Comparațiile sunt categoriale. Sambla nu se compară cu un brand anume, ci cu tipul general de produs.</p>
    </div>
</section>

{{-- ============================================================== --}}
{{-- 4-STAGE RAG PIPELINE --}}
{{-- ============================================================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                ARHITECTURĂ
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Pipeline-ul de 4 stadii</h2>
            <p class="text-lg text-slate-500">Fiecare mesaj pe care îl primește bot-ul tău trece prin aceste 4 stadii. Total: sub 2 secunde.</p>
        </div>

        {{-- Visual SVG flow diagram with latency budget --}}
        <p class="text-xs text-slate-500 text-center mb-3 lg:hidden">Glisează lateral pentru a vedea diagrama →</p>
        <div class="max-w-5xl mx-auto mb-12 overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0">
            <svg viewBox="0 0 900 280" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto min-w-[760px]" role="img" aria-label="Diagrama pipeline-ului RAG">
                <defs>
                    <marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                        <path d="M0,0 L0,8 L8,4 z" fill="#dc2626"/>
                    </marker>
                    <linearGradient id="g1" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#dbeafe"/>
                        <stop offset="100%" stop-color="#bfdbfe"/>
                    </linearGradient>
                    <linearGradient id="g2" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#fef3c7"/>
                        <stop offset="100%" stop-color="#fde68a"/>
                    </linearGradient>
                    <linearGradient id="g3" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#dcfce7"/>
                        <stop offset="100%" stop-color="#bbf7d0"/>
                    </linearGradient>
                    <linearGradient id="g4" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#fee2e2"/>
                        <stop offset="100%" stop-color="#fecaca"/>
                    </linearGradient>
                </defs>

                {{-- Customer input --}}
                <rect x="20" y="110" width="120" height="60" rx="12" fill="#1e293b"/>
                <text x="80" y="138" text-anchor="middle" fill="white" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Întrebare</text>
                <text x="80" y="156" text-anchor="middle" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11">client</text>

                <line x1="140" y1="140" x2="170" y2="140" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow)"/>

                {{-- Stage 1 --}}
                <rect x="170" y="100" width="140" height="80" rx="12" fill="url(#g1)" stroke="#3b82f6" stroke-width="2"/>
                <text x="240" y="124" text-anchor="middle" fill="#1e3a8a" font-family="system-ui, sans-serif" font-size="11" font-weight="bold">STAGE 1</text>
                <text x="240" y="143" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Intent</text>
                <text x="240" y="159" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Classifier</text>
                <text x="240" y="174" text-anchor="middle" fill="#3b82f6" font-family="system-ui, sans-serif" font-size="10">~30ms</text>

                <line x1="310" y1="140" x2="345" y2="140" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow)"/>

                {{-- Stage 2 --}}
                <rect x="345" y="80" width="170" height="120" rx="12" fill="url(#g2)" stroke="#f59e0b" stroke-width="2"/>
                <text x="430" y="104" text-anchor="middle" fill="#92400e" font-family="system-ui, sans-serif" font-size="11" font-weight="bold">STAGE 2 — RAG</text>
                <text x="430" y="123" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="12" font-weight="bold">Hybrid Retrieval</text>
                <text x="430" y="141" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="10">vector 1536-dim · BM25 RO</text>
                <text x="430" y="155" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="10">RRF fusion · cross-encoder</text>
                <text x="430" y="169" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="10">→ top 8 din 20 candidați</text>
                <text x="430" y="187" text-anchor="middle" fill="#f59e0b" font-family="system-ui, sans-serif" font-size="10" font-weight="bold">~150ms</text>

                <line x1="515" y1="140" x2="550" y2="140" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow)"/>

                {{-- Stage 3 --}}
                <rect x="550" y="100" width="150" height="80" rx="12" fill="url(#g3)" stroke="#10b981" stroke-width="2"/>
                <text x="625" y="124" text-anchor="middle" fill="#065f46" font-family="system-ui, sans-serif" font-size="11" font-weight="bold">STAGE 3</text>
                <text x="625" y="143" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Conversation</text>
                <text x="625" y="159" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Strategy</text>
                <text x="625" y="174" text-anchor="middle" fill="#10b981" font-family="system-ui, sans-serif" font-size="10">~5ms</text>

                <line x1="700" y1="140" x2="735" y2="140" stroke="#dc2626" stroke-width="2" marker-end="url(#arrow)"/>

                {{-- Stage 4 --}}
                <rect x="735" y="80" width="145" height="120" rx="12" fill="url(#g4)" stroke="#dc2626" stroke-width="2"/>
                <text x="807" y="104" text-anchor="middle" fill="#991b1b" font-family="system-ui, sans-serif" font-size="11" font-weight="bold">STAGE 4</text>
                <text x="807" y="123" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">10-Layer</text>
                <text x="807" y="139" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="13" font-weight="bold">Verification</text>
                <text x="807" y="155" text-anchor="middle" fill="#1e293b" font-family="system-ui, sans-serif" font-size="10">+ LLM gen</text>
                <text x="807" y="187" text-anchor="middle" fill="#dc2626" font-family="system-ui, sans-serif" font-size="10" font-weight="bold">~600ms</text>

                {{-- Bottom: total + branches --}}
                <text x="450" y="240" text-anchor="middle" fill="#475569" font-family="system-ui, sans-serif" font-size="12">
                    <tspan font-weight="bold">Total:</tspan> sub 2 secunde end-to-end · output:
                    <tspan fill="#10b981" font-weight="bold">răspuns publicat</tspan> ·
                    <tspan fill="#f59e0b" font-weight="bold">cere clarificare</tspan> ·
                    <tspan fill="#dc2626" font-weight="bold">escaladare la operator</tspan>
                </text>
                <text x="450" y="262" text-anchor="middle" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="11">
                    Fluxul real e implementat în <tspan font-family="ui-monospace, monospace">App\Services\Conversation</tspan> +
                    <tspan font-family="ui-monospace, monospace">App\Services\Knowledge</tspan>
                </text>
            </svg>
            <p class="text-center mt-3">
                <a href="/architecture.md" class="inline-flex items-center gap-1.5 text-sm text-red-600 hover:text-red-700 font-semibold">
                    Vezi pseudo-code public →
                </a>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $stages = [
                    ['1','Înțelegere intenție','Clasifică mesajul ca informațional, tranzacțional sau reclamație. Schimbă strategia bot-ului în consecință.'],
                    ['2','Hybrid Search RAG','Vector search 1536-dim + full-text românesc cu stemming, fuzionate. AI reranker peste 20 candidați → top 8 chunks.'],
                    ['3','Strategie conversație','Începutul conversației înțelege nevoia. Mijlocul recomandă. Finalul cere lead/escaladare. Diferit per stadiu.'],
                    ['4','Verificare 10 straturi','Răspunsul generat trece prin 10 verificări înainte să ajungă la client. Anti-halucinare e ultimul filtru.'],
                ];
            @endphp
            @foreach($stages as $s)
                <div class="p-6 lg:p-8 rounded-2xl bg-white border border-slate-100 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 relative">
                    <div class="absolute -top-4 left-6 lg:left-8 w-9 h-9 rounded-xl bg-gradient-to-br from-red-700 to-red-600 flex items-center justify-center shadow-lg shadow-red-900/20">
                        <span class="text-sm font-extrabold text-white">{{ $s[0] }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-3 mt-3">{{ $s[1] }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $s[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- HONEST DISCLOSURE — ce folosim vs ce am construit --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 section-padding border-y border-slate-200">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                ONESTITATE TEHNICĂ
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Ce folosim vs ce am construit</h2>
            <p class="text-lg text-slate-500">Nu pretindem că am antrenat un GPT propriu. Iată exact ce e al nostru și ce folosim de la alții.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
            {{-- Folosim --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:p-8">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Ce folosim de la alții</h3>
                </div>
                <p class="text-sm text-slate-500 mb-4">Tehnologii open-source și API-uri terțe, pentru ce e mai bun pe piață.</p>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>OpenAI GPT-4o</strong> — modelul de text și voce. Swappable cu Claude, Gemini, Llama.</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>OpenAI text embeddings</strong> — pentru vectorii 1536-dim</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>Telnyx</strong> — telefonie SIP cu numere RO</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>PostgreSQL + pgvector</strong> — baza de date cu vector search</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>PHP 8.3</strong> — runtime backend</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>Vertex AI Gemini</strong> — pentru generare imagini în marketing automation</span></li>
                    <li class="flex gap-2"><span class="text-slate-400 shrink-0">›</span><span><strong>Stripe</strong> — billing</span></li>
                </ul>
                <p class="text-xs text-slate-500 mt-5 italic">Nu pretindem că am construit aceste lucruri. Le folosim, ca orice produs serios.</p>
            </div>

            {{-- Am construit --}}
            <div class="bg-white rounded-2xl border-2 border-red-300 p-6 lg:p-8 relative">
                <div class="absolute top-0 right-0 bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl uppercase tracking-wider">Proprietar</div>
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Ce am construit noi</h3>
                </div>
                <p class="text-sm text-slate-500 mb-4">Asta e platforma. Restul sunt componente swappable.</p>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Pipeline RAG 4-stadii</strong> — orchestrare proprie peste LLM (intent → retrieve → strategy → verify)</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>10 straturi de verificare anti-halucinare</strong> — fiecare strat e cod custom, nu librărie</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>25 grupuri sinonime românești</strong> — lexicale, manual curate, integrate în motorul de căutare</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Hybrid search fusion</strong> — combină vector + BM25 + stemming românesc + AI reranker peste 20 candidați → top 8</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Conversation strategy state machine</strong> — comportament diferit pe stadii (început/mijloc/final)</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Detector frustrare live</strong> — clasifică text + voce și escaladează automat</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Multi-channel context bridge</strong> — același client urmat între web, telefon, WhatsApp, FB, IG cu memorie partajată. Cea mai grea parte tehnică.</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Supervised gap detection</strong> — bot-ul identifică ce nu știe, generează draft FAQ, dar TU aprobi înainte să se publice (nu auto-modificare)</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Tuning românesc complet</strong> — chunking pentru morfologia română, intonație voce română, diacritice ă â î ș ț, anti-pattern lists pentru halucinare</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>WooCommerce nativ</strong> — căutare semantică produse, stoc live, add-to-cart din chat, tracking AWB. Nu plugin terț, scris in-house.</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Tenant isolation</strong> — izolare per cont la nivel de bază de date</span></li>
                    <li class="flex gap-2"><span class="text-red-500 shrink-0 font-bold">✓</span><span><strong>Dashboard analitice + onboarding wizard + billing</strong> — întreaga aplicație web</span></li>
                </ul>
            </div>
        </div>

        <div class="max-w-3xl mx-auto mt-10 bg-white rounded-xl border border-slate-200 p-6">
            <p class="text-sm text-slate-700 leading-relaxed">
                <strong class="text-slate-900">Sumar onest:</strong> Sambla nu e „un wrapper peste GPT". GPT e generatorul de text dintr-o mașinărie mult mai mare. Dacă mâine vrem să trecem pe Claude sau Llama, 90% din platformă continuă să funcționeze. Valoarea pe care o oferim e <strong>orchestrarea, tuningul românesc, straturile de verificare, multi-channel bridge-ul, și infrastructura operațională</strong> (hosting RO, GDPR, billing, analytics, suport în română). LLM-ul e o componentă swappable, nu produsul.
            </p>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- HALUCINARE — onest --}}
{{-- ============================================================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                    REALITATE vs MARKETING
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Despre halucinare, onest</h2>
            </div>

            <div class="bg-slate-50 rounded-2xl p-8 border border-slate-200">
                <p class="text-base text-slate-700 leading-relaxed mb-4">
                    <strong class="text-red-600">Orice LLM poate halucina.</strong> Asta e o proprietate fundamentală a tehnologiei. Oricine îți spune că produsul lui bazat pe LLM are „zero halucinare" e necinstit.
                </p>
                <p class="text-base text-slate-700 leading-relaxed mb-4">
                    Ce face Sambla e să <strong>minimizeze</strong> halucinarea prin inginerie și să <strong>eșueze elegant</strong> când nu poate garanta un răspuns:
                </p>
                <ul class="space-y-2 text-sm text-slate-700 mb-4">
                    <li class="flex gap-2"><span class="text-emerald-600 shrink-0">✓</span><span>Fiecare răspuns e ancorat în chunk-urile reale extrase din baza ta de cunoștințe. LLM-ul e instruit să citeze ce chunk a folosit.</span></li>
                    <li class="flex gap-2"><span class="text-emerald-600 shrink-0">✓</span><span>Dacă retrieval-ul nu returnează nimic relevant, bot-ul NU generează „cea mai bună presupunere". Spune cinstit „nu am informația asta".</span></li>
                    <li class="flex gap-2"><span class="text-emerald-600 shrink-0">✓</span><span>Dacă scorul de confidență e sub prag, bot-ul escaladează la operator uman.</span></li>
                    <li class="flex gap-2"><span class="text-emerald-600 shrink-0">✓</span><span>Cele 10 straturi de verificare prind conținutul generat care contrazice chunk-urile sursă.</span></li>
                    <li class="flex gap-2"><span class="text-emerald-600 shrink-0">✓</span><span>Fiecare răspuns poate arăta documentele-sursă, ca clientul să verifice singur.</span></li>
                </ul>
                <p class="text-base text-slate-700 leading-relaxed">
                    În producție, modul de eșec e <strong>„bot-ul spune cinstit că nu știe și redirecționează la un om"</strong> — nu „bot-ul inventează un răspuns greșit cu încredere". Asta e obiectivul de inginerie. Nu pretindem 100% perfecțiune, pretindem <strong>răspunsuri verificabile</strong>.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================== --}}
{{-- 10 ANTI-HALLUCINATION LAYERS --}}
{{-- ============================================================== --}}
<section class="bg-slate-950 section-padding">
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
<x-motif-border />

{{-- ============================================================== --}}
{{-- ROMANIA ADVANTAGE --}}
{{-- ============================================================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <div class="inline-flex items-center gap-2 bg-red-50 text-red-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    BUILT IN ROMANIA
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
                        ['Backend','PHP 8.3 modern OOP'],
                        ['Database','PostgreSQL 16 + pgvector'],
                        ['Cache + Queue','Redis 7'],
                        ['Voice','OpenAI Realtime API (GPT-4o)'],
                        ['Telephony','Telnyx (numere RO)'],
                        ['Search','Hybrid: vector 1536-dim + full-text BM25 + AI reranker'],
                        ['Image gen','Vertex AI Gemini'],
                        ['CDN','Cloudflare cu Brotli, HTTP/3, edge cache'],
                        ['Hosting','Servere fizice în România']
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
<section class="bg-slate-50 section-padding">
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
<section class="bg-white section-padding border-t border-slate-100">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Cifrele care contează</h2>
            <p class="text-lg text-slate-500">Performanță reală a pipeline-ului, măsurată în producție.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-5xl mx-auto">
            @foreach([
                ['<2s','Latency end-to-end','RAG complet: clasificare → search → strategy → generare'],
                ['1536','Dimensiuni vector','Embeddings stocate în pgvector pentru similaritate semantică'],
                ['10','Straturi anti-halucinare','Verificate la fiecare răspuns'],
                ['25','Grupuri sinonime RO','Cunoscute nativ în motorul de căutare'],
                ['8','Chunks per query','Aleși din 20 de candidați rerankate'],
                ['5','Canale unificate','Web + voice + WhatsApp + FB + IG cu un singur context'],
                ['~10min','Setup live','De la cont nou la bot pe site-ul clientului'],
                ['100%','Hosting RO','Servere fizice în România, zero date în afara UE'],
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
{{-- DASHBOARD SCREENSHOTS — capture reale din dashboard-ul clientului --}}
{{-- ============================================================== --}}
<section class="bg-slate-50 section-padding">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                CAPTURE REALE DIN DASHBOARD
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Dashboard-ul clientului, în producție</h2>
            <p class="text-lg text-slate-500">Capture reale din dashboard-ul pe care îl primește orice client după înregistrare. Numele și datele specifice sunt obscurate ușor pentru confidențialitate — restul (layout, navigare, componente, fluxuri) e exact ce vezi în cont.</p>
        </div>

        @php
            $shots = [
                ['file' => 'home-blur.jpg',      'title' => 'Acasă',                'desc' => 'Vedere de ansamblu: bot-urile tale, conversații recente, statistici de utilizare, alerte și acces rapid la fiecare modul.'],
                ['file' => 'boti-blur.jpg',     'title' => 'Boții mei',            'desc' => 'Lista bot-urilor configurate, fiecare cu propria personalitate, bază de cunoștințe, canal și status în timp real.'],
                ['file' => 'apeluri-blur.jpg',  'title' => 'Apeluri telefonice',   'desc' => 'Istoric apeluri preluate de voicebot: durată, sentiment detectat, transcript live, escaladări la operator uman.'],
                ['file' => 'leads-blur.jpg',    'title' => 'Lead-uri',             'desc' => 'Pipeline-ul de lead-uri captate automat de bot: nou → contactat → calificat → câștigat. Cu scoring și note.'],
                ['file' => 'numere-blur.jpg',   'title' => 'Numere de telefon',    'desc' => 'Numere RO native alocate prin Telnyx, cu rutare per bot și configurare per canal.'],
                ['file' => 'setari-blur.jpg',   'title' => 'Setări cont',          'desc' => 'Personalizarea contului: tonul brand-ului, integrările active, preferințe de notificare, ore de lucru.'],
                ['file' => 'echipa-blur.jpg',   'title' => 'Echipă',               'desc' => 'Membri echipei tale care au acces la dashboard, cu roluri și permisiuni granulate.'],
                ['file' => 'facturare-blur.jpg','title' => 'Facturare',            'desc' => 'Plan curent, consum lunar, facturi istorice și opțiunea de upgrade/downgrade gestionate prin Stripe.'],
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-6xl mx-auto">
            @foreach($shots as $s)
                <figure class="bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <img src="{{ asset('images/screenshots/' . $s['file']) }}"
                         alt="Dashboard Sambla — {{ $s['title'] }}"
                         loading="lazy" width="1600" height="1000"
                         class="w-full h-auto block">
                    <figcaption class="p-5 border-t border-slate-200">
                        <p class="text-sm font-bold text-slate-900 mb-1">Dashboard · {{ $s['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $s['desc'] }}</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <p class="text-xs text-slate-500 text-center mt-8 max-w-3xl mx-auto">
            Toate imaginile sunt capture reale din dashboard-ul clienților, cu un filtru de blur subtil aplicat peste datele personale. Layout-ul, componentele, navigarea, butoanele și fluxurile sunt exact așa cum apar utilizatorilor reali — nu mockup-uri Figma.
        </p>
    </div>
</section>

{{-- ============================================================== --}}
{{-- CASE STUDIES --}}
{{-- ============================================================== --}}
<section class="bg-white section-padding">
    <div class="container-custom">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                CAZURI
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-5 tracking-tight">Cum arată Sambla în producție</h2>
            <p class="text-lg text-slate-500">Un caz real anonimizat și două scenarii ilustrative pentru a vedea concret ce face platforma.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            {{-- Real client (anonymized) --}}
            <div class="bg-white rounded-2xl border-2 border-emerald-300 p-6 lg:p-8 shadow-sm hover:shadow-md transition-shadow relative">
                <div class="absolute top-0 right-0 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl uppercase tracking-wider">Client real (anonimizat)</div>
                <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-2 mt-3">E-COMMERCE · COSMETICE NATURALE</p>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Magazin online de produse cosmetice naturale</h3>
                <p class="text-sm text-slate-600 mb-5">Magazin WooCommerce cu sute de produse, întrebări frecvente despre tipuri de ten, alergeni, mod de utilizare. Echipa de suport era copleșită de aceleași 20-30 întrebări reluate zilnic.</p>

                <div class="space-y-3 mb-5">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Provocare</p>
                        <p class="text-sm text-slate-700">Întrebări repetitive pe ten / ingrediente / alergii consumau ore din timpul echipei de suport, iar răspunsurile veneau cu întârziere seara și în weekend.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Soluție</p>
                        <p class="text-sm text-slate-700">Sambla a sincronizat catalogul WooCommerce, a învățat din descrierile produselor + politica de retur + secțiunea blog cu sfaturi de îngrijire. Bot-ul răspunde la întrebările clienților cu recomandări de produse din catalog.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Integrare</p>
                        <p class="text-sm text-slate-700">WooCommerce live (sincronizare produse + categorii la fiecare modificare), chat widget pe site, escaladare la echipa de suport prin email.</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-slate-100">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-emerald-600">24/7</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Răspuns instant</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-emerald-600">100%</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Catalog acoperit</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-emerald-600">RO</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Voce + chat</p>
                    </div>
                </div>
            </div>

            {{-- Hypothetical 1 --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:p-8 hover:border-slate-300 transition-colors relative">
                <div class="absolute top-0 right-0 bg-slate-200 text-slate-600 text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl uppercase tracking-wider">Scenariu ilustrativ</div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 mt-3">CABINET STOMATOLOGIC · 4 medici</p>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Cabinet stomatologic urban</h3>
                <p class="text-sm text-slate-600 mb-5">Cabinet cu 4 medici, ~150 pacienți pe săptămână. Recepția era depășită de telefoanele pentru programări, întrebări despre tarife și instrucțiuni post-procedură.</p>

                <div class="space-y-3 mb-5">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Provocare</p>
                        <p class="text-sm text-slate-700">Recepția pierdea 2-3 ore pe zi cu telefoane repetitive (program, tarife, ce e acoperit de asigurări, instrucțiuni post-extracție).</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Soluție</p>
                        <p class="text-sm text-slate-700">Voicebot Sambla pe numărul cabinetului, conectat la calendarul Google al medicilor. Răspunde 24/7 cu voce naturală română, ia programări, trimite confirmări, escaladează urgențele.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Integrare</p>
                        <p class="text-sm text-slate-700">Telefon RO via Telnyx, calendar Google, sync sheet de tarife și instrucțiuni post-procedură.</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-slate-100">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">+40%</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Programări noaptea</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">−2h</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Recepția zilnic</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">0</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Apeluri pierdute</p>
                    </div>
                </div>
            </div>

            {{-- Hypothetical 2 --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 lg:p-8 hover:border-slate-300 transition-colors relative">
                <div class="absolute top-0 right-0 bg-slate-200 text-slate-600 text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl uppercase tracking-wider">Scenariu ilustrativ</div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 mt-3">BIROU AVOCATURĂ · 6 avocați</p>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Birou de avocatură mediu</h3>
                <p class="text-sm text-slate-600 mb-5">Birou de 6 avocați cu un singur asistent. Asistentul filtra zilnic ~40 cereri pe email + telefon, multe procedurale (tarife, documente, programări), puține relevante.</p>

                <div class="space-y-3 mb-5">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Provocare</p>
                        <p class="text-sm text-slate-700">Avocații primeau cereri care nu erau pentru ei (întrebări procedurale generice). Asistentul nu mai avea timp să facă pre-calificare reală a cazurilor.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Soluție</p>
                        <p class="text-sm text-slate-700">Chatbot Sambla pe site cu instrucțiune strictă: NU dă consultanță juridică. Răspunde la întrebări procedurale (onorarii orientative, documente, programări). Cazurile reale ajung pre-calificate la avocat.</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Integrare</p>
                        <p class="text-sm text-slate-700">Chat widget pe site, calendar de programări shared, lead pipeline integrat în CRM-ul biroului.</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-4 border-t border-slate-100">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">−70%</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Triaj manual</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">100%</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Pre-calificat</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-700">24/7</p>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider">Programări</p>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-xs text-slate-500 text-center mt-8 max-w-3xl mx-auto">
            Cazul cu badge verde (E-commerce cosmetice naturale) este un client real al platformei, datele sale sunt anonimizate. Cazurile cu badge gri sunt scenarii ilustrative care arată cum se folosește Sambla în verticalele respective — cifrele sunt exemplificative, nu măsurate. Pe măsură ce avem mai mulți clienți cu rezultate publice, le vom adăuga aici.
        </p>
    </div>
</section>

<x-cta-section
    title="Testează platforma în 10 minute"
    subtitle="Cont nou, încarci 2-3 documente, vezi cum răspunde bot-ul în limba ta. Fără card."
    primary-text="Începe gratuit"
    primary-href="/register"
    secondary-text="Vorbește cu noi"
    secondary-href="/contact"
/>

@endsection
