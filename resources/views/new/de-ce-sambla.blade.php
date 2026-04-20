@extends('layouts.new')

@section('title', 'De ce Sambla — diferența față de alte soluții AI')
@section('meta_description', 'De ce aleg afacerile Sambla: nativ românesc, onest când nu știe, construit în România, GDPR by default, fără promisiuni goale. Compară pe bune.')
@section('canonical', url('/new/de-ce-sambla'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ de ce Sambla</div>
        <h1 class="display h-display-xl mb-6">Diferența <em class="italic accent-text">se aude.</em></h1>
        <p class="text-xl max-w-3xl mx-auto leading-relaxed" style="color: var(--muted);">Există destule soluții AI pe piață. Le-am testat pe majoritatea. Iată de ce afacerile românești aleg Sambla — și unde nu suntem încă cea mai bună opțiune (ca să știi din start).</p>
    </div>
</section>

<section class="py-16 md:py-20">
    <div class="max-w-6xl mx-auto px-6 space-y-10">

        @foreach([
            [
                'Nativ românesc, nu tradus',
                'Majoritatea agenților AI de pe piață au fost antrenați în engleză și apoi „localizați". Simți imediat — răspund rigid, ratează idiomuri, pun diacritice la întâmplare. Sambla gândește românește de la prima propoziție. Așa ar vorbi cineva din echipa ta, nu un asistent de call center offshore.',
            ],
            [
                'Nu inventează. Niciodată.',
                'Pentru a economisi costuri, multe soluții lasă AI-ul să „completeze" informațiile lipsă. Tu descoperi abia când un client îți zice „mi-a spus că livrați sâmbăta" — ceea ce nu este adevărat. Sambla este construit cu reguli stricte: dacă informația nu e în documentele tale, agentul spune onest „nu am această informație" și te transferă.',
            ],
            [
                'GDPR by default, nu ca bifă',
                'Nu e suficient să scrii „suntem GDPR compliant" pe site. Trebuie să poți demonstra cum stochezi, cine are acces, cum ștergi. Sambla: DPA gata de semnat, audit trail per conversație, control granular pe retenție. Fără promisiuni de marketing, doar documentație.',
            ],
            [
                'Suport uman, în limba română, de către cineva care scrie cod',
                'Când ai nevoie de ajutor serios, nu vorbești cu un chatbot despre produsul tău de chatbot. Vorbești cu un om din echipa noastră, în română, care înțelege exact ce se întâmplă și poate să rezolve.',
            ],
            [
                'Construit să facă bani, nu să impresioneze',
                'Sambla nu are demo-uri spectaculoase cu agenți care „rezolvă orice". Are agenți care programează, vând, captează lead-uri — și o echipă care măsoară impactul real în clienți noi câștigați și ore libere pentru echipa ta.',
            ],
        ] as $i => $point)
            <div class="fade-up rounded-3xl p-8 md:p-10 bg-paper border border-line grid md:grid-cols-12 gap-6 items-start">
                <div class="md:col-span-1">
                    <div class="display text-6xl font-semibold accent-text">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="md:col-span-11">
                    <h2 class="display h-display-m mb-3">{{ $point[0] }}</h2>
                    <p class="text-lg leading-relaxed" style="color: var(--muted);">{{ $point[1] }}</p>
                </div>
            </div>
        @endforeach

        <div class="fade-up rounded-3xl p-8 md:p-10 bg-ink grid md:grid-cols-12 gap-6 items-start">
            <div class="md:col-span-1">
                <div class="display text-6xl font-semibold" style="color: var(--sun);">—</div>
            </div>
            <div class="md:col-span-11">
                <h2 class="display h-display-m mb-3" style="color: var(--cream);">Unde NU suntem încă cea mai bună alegere</h2>
                <p class="text-lg leading-relaxed mb-4" style="color:#D7D3CA;">Dacă ai nevoie astăzi de:</p>
                <ul class="space-y-2 text-lg" style="color:#D7D3CA;">
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Suport pentru mai mult de 2 limbi în aceeași conversație (azi: română + engleză)</li>
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Integrări out-of-the-box cu CRM-uri enterprise internaționale (încă nu sunt live — putem discuta pe caz)</li>
                    <li class="flex gap-3"><span style="color: var(--sun);">—</span>Volum de peste 100.000 de conversații pe zi (putem discuta un plan enterprise)</li>
                </ul>
                <p class="mt-4" style="color:#A8A29E;">… atunci probabil nu suntem cea mai potrivită alegere chiar acum. Spune-ne ce îți lipsește — poate o construim împreună.</p>
            </div>
        </div>
    </div>
</section>

{{-- CATEGORICAL COMPARISON TABLE --}}
<section class="py-20 bg-paper border-y border-line">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ comparație pe bune</div>
            <h2 class="display h-display-m mb-4">Față de <em class="italic accent-text">ce mai există</em> pe piață.</h2>
            <p class="text-lg" style="color: var(--muted);">Patru tipuri de „soluții" cu care suntem comparați. Fără nume de branduri — doar capabilități reale, pe care le poți verifica tu însuți.</p>
        </div>

        @php
            $cYes  = '<svg class="inline w-5 h-5" style="color: var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
            $cNo   = '<svg class="inline w-5 h-5" style="color: #C2410C;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
            $cWarn = '<svg class="inline w-5 h-5" style="color: #D97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>';
            $cDash = '<span style="color: var(--line);">—</span>';
            $rows = [
                ['Răspunde la întrebări neașteptate',                ['no'],   ['yes'],              ['warn','limitat'],   ['yes']],
                ['Citește documentele afacerii tale',                ['no'],   ['warn','doar manual'], ['no'],               ['yes','automat']],
                ['Spune onest când nu știe ceva',                    ['no'],   ['no'],               ['warn','uneori'],    ['yes']],
                ['Citează sursa răspunsului',                        ['dash'], ['warn','rareori'],   ['dash'],             ['yes']],
                ['Voce naturală în română',                          ['dash'], ['dash'],             ['warn','robotică'],  ['yes']],
                ['Același agent pe chat, telefon, WhatsApp',         ['warn','1-2 canale'], ['no'],  ['no'],               ['yes']],
                ['Detectează frustrare și escaladează',              ['no'],   ['no'],               ['warn','doar text'], ['yes']],
                ['Setup sub o oră, fără programator',                ['warn','manual'], ['warn','dezvoltator'], ['no','săptămâni'], ['yes']],
                ['Hosting fizic în România',                         ['dash'], ['no'],               ['dash'],             ['yes']],
                ['GDPR nativ, izolare pe cont',                      ['dash'], ['no'],               ['warn'],             ['yes']],
                ['Învață din întrebări fără răspuns',                ['no'],   ['no'],               ['no'],               ['yes']],
                ['Integrare nativă cu magazinul online',             ['warn','plugin terț'], ['no'], ['no'],               ['yes']],
                ['Suport uman în română, de la dezvoltatori',        ['dash'], ['dash'],             ['warn'],             ['yes']],
            ];
            $renderCell = function($cell) use ($cYes,$cNo,$cWarn,$cDash) {
                $type  = $cell[0];
                $label = $cell[1] ?? '';
                $icon  = match($type) { 'yes'=>$cYes, 'no'=>$cNo, 'warn'=>$cWarn, default=>$cDash };
                return $icon . ($label ? '<span class="block text-[11px] mt-1" style="color: var(--muted);">'.e($label).'</span>' : '');
            };
        @endphp

        <div class="overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0 fade-up">
            <table class="min-w-[760px] w-full bg-cream rounded-3xl border border-line overflow-hidden text-sm">
                <thead>
                    <tr style="background: var(--ink); color: var(--cream);">
                        <th class="px-5 py-5 text-left font-semibold w-[30%]">Capabilitate</th>
                        <th class="px-3 py-5 text-center font-semibold w-[17%]">
                            <span class="block">Agent scriptat</span>
                            <span class="block text-[11px] font-normal mt-0.5" style="color: #A8A29E;">„dacă spune X, răspunde Y"</span>
                        </th>
                        <th class="px-3 py-5 text-center font-semibold w-[17%]">
                            <span class="block">„GPT cu prompt"</span>
                            <span class="block text-[11px] font-normal mt-0.5" style="color: #A8A29E;">custom wrapper</span>
                        </th>
                        <th class="px-3 py-5 text-center font-semibold w-[17%]">
                            <span class="block">Call-center clasic</span>
                            <span class="block text-[11px] font-normal mt-0.5" style="color: #A8A29E;">meniu + operator</span>
                        </th>
                        <th class="px-3 py-5 text-center font-bold w-[19%] accent-bg" style="color: #fff;">Sambla</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--line)]">
                    @foreach($rows as $r)
                        <tr class="hover:bg-paper transition">
                            <td class="px-5 py-3 font-medium">{{ $r[0] }}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[1]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[2]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle">{!! $renderCell($r[3]) !!}</td>
                            <td class="px-3 py-3 text-center align-middle accent-soft-bg">{!! $renderCell($r[4]) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs mt-4 text-center lg:hidden" style="color: var(--muted);">← Glisează lateral pentru tot tabelul →</p>
        <p class="text-xs mt-4 text-center" style="color: var(--muted);">Comparațiile sunt pe categorii, nu pe nume de brand. Verificabile în conturile noastre de demo.</p>
    </div>
</section>

{{-- FAQ --}}
<section class="py-20">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-10 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ întrebări sincere</div>
            <h2 class="display h-display-m">Răspundem la ce <em class="italic accent-text">chiar ne întreabă</em> lumea.</h2>
        </div>
        <div class="space-y-3 fade-up">
            @foreach([
                ['În ce diferă Sambla de un chatbot obișnuit cu butoane?',
                 'Chatbot-urile cu butoane funcționează pe scenarii fixe. Dacă clientul formulează altfel decât ai prevăzut, se blochează. Sambla înțelege limbaj natural — clientul scrie sau spune cum îi vine, agentul înțelege intenția și răspunde din documentele tale reale.'],
                ['Sambla e doar „GPT cu prompt"?',
                 'Nu. Folosim modele AI moderne ca motor de limbaj — nu pretindem că am construit de la zero un model de miliarde de parametri. Dar restul platformei este al nostru: baza de cunoștințe pe afacere, controalele anti-invenție, conexiunea cu telefonul, canalele multiple, dashboard-ul, izolarea între conturi. Dacă mâine schimbăm motorul, agentul tău funcționează la fel.'],
                ['Cum garantați că AI-ul nu inventează?',
                 'Onest: niciun model AI nu garantează 100% că nu greșește. Ce garantăm este că agentul răspunde doar din documentele pe care le-ai încărcat tu, citează sursa, iar când nu are informația, spune onest „nu știu" și te transferă. În producție, modul de eșec este „îți cere clarificare", nu „inventează cu încredere".'],
                ['Are Sambla vreo legătură cu Sambla Group (IFN, credite)?',
                 'Nu, niciuna. Sambla este o platformă de software AI pentru afaceri românești. Nu oferim credite, leasing, asigurări sau orice alt serviciu financiar. Numele vine dintr-o expresie ardelenească — „a sâmbla", a pronunța, a da glas. Povestea completă este pe pagina Despre.'],
                ['De ce contează că e hostat în România?',
                 'Datele tale (și ale clienților tăi) nu părăsesc UE. GDPR nativ, izolare pe tenant (niciun cont nu poate accesa datele altui cont, nici din greșeală), backup-uri criptate, audit trail pe fiecare acces. Dacă ai o investigație GDPR, totul este trasabil pe infrastructură din România.'],
                ['Câte canale suportă Sambla cu același creier?',
                 'Chat pe site, telefon, WhatsApp, Facebook Messenger, Instagram DM. Clientul poate începe pe site, continua pe WhatsApp, suna după două ore — agentul ține minte contextul. Este „multi-canal cu același creier", nu cinci agenți separați.'],
                ['Agentul învață singur sau e nevoie de cineva să-l antreneze?',
                 'Învață asistat. Detectează singur întrebările la care nu răspunde bine și generează o propunere de răspuns, dar un om din echipa ta o aprobă înainte să intre în baza de cunoștințe. Nu este auto-modificare autonomă — asta ar fi periculos. Tu rămâi în control, AI-ul face munca grea.'],
                ['Pot să-l probez înainte să iau decizia?',
                 'Da. 7 zile gratuit, fără card, fără să te sunăm. Urci documentele tale, îl testezi pe site și pe telefon, vezi cum răspunde cu datele tale reale. Dacă nu e ce îți trebuie, nu se întâmplă nimic.'],
            ] as $f)
                <details class="rounded-2xl bg-cream border border-line px-5 py-4 group">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-semibold pr-6">{{ $f[0] }}</span>
                        <svg class="chev w-4 h-4 shrink-0 transition-transform group-open:rotate-180" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 leading-relaxed text-[15px]" style="color: var(--muted);">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA FINAL --}}
<section class="py-24">
    <div class="max-w-4xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink p-10 md:p-14 text-center relative overflow-hidden fade-up">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 40%, transparent) 0%, transparent 70%);"></div>
            <div class="relative">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-5" style="color:#F2E59A;">◇ convinge-te singur</div>
                <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-4 text-cream">Vezi <em class="italic accent-text">tu însuți.</em></h2>
                <p class="text-lg mb-8" style="color: #D7D3CA;">7 zile gratuit, fără card, fără să te sunăm. Îl pornești în 10 minute, cu documentele tale reale.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ url('/register') }}" class="btn btn-primary" style="background:#F2E59A; color:#1C1917;">
                        Începe gratuit
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ route('new.contact') }}" class="btn btn-outline" style="border-color: rgba(255,255,255,.4); color: #F5F1E8;">Pune o întrebare întâi</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
