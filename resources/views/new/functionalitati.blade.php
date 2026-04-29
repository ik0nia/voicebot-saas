@extends('layouts.new')

@section('title', 'Ce face Sambla — funcționalități complete pentru agenți AI')
@section('meta_description', 'Agent AI pe site și agent AI vocal — răspunsuri din documentele tale, programări automate în calendar, lead capture, recomandări produse, GDPR nativ. Vezi lista completă.')
@section('canonical', url('/functionalitati'))
@section('og_image', route('new.og', ['title' => 'Tot ce face agentul tău AI', 'sub' => 'Chat, voce, calendar, WooCommerce. 24/7 în română.', 'eyebrow' => 'sambla.ro · funcționalități']))

@section('content')

<section class="hero-glow relative overflow-hidden">
    <div class="max-w-5xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ funcționalități</div>
        <h1 class="display h-display-xl mb-6 fade-up">Tot ce face<br><span class="italic accent-text">agentul tău AI.</span></h1>
        <p class="text-xl leading-relaxed max-w-3xl mx-auto fade-up" style="color: var(--muted); transition-delay:.1s;">Construit pentru afacerile românești. Răspunde clienților 24/7, face programări în calendar, recomandă produse, captează lead-uri — fără să inventeze, fără să promită ce nu poate.</p>
    </div>
</section>

{{-- Pillars: canale, inteligență, operațional, integrări --}}
<section class="py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-6 space-y-16">

        {{-- CANALE --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ canale</div>
                <h2 class="display h-display-l">Un creier.<br><span class="italic">Oriunde vorbești cu clientul.</span></h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['Chat pe site', 'Widget premium, o linie de cod. Recomandă produse, preia lead-uri, trimite link-uri.', '💬'],
                    ['Telefon', 'Agent vocal pe numere românești dedicate. Voce naturală, clientul poate întrerupe, transcriere în timp real a fiecărui apel.', '📞'],
                    ['WhatsApp', 'Inbound și outbound. Conectare directă WhatsApp Business.', '💚'],
                    ['Messenger · Instagram', 'Facebook Messenger și Instagram DM — inbound din dashboard.', '✨'],
                ] as $c)
                    <div class="fade-up rounded-3xl p-6 bg-paper border border-line">
                        <div class="w-11 h-11 rounded-xl accent-soft-bg flex items-center justify-center mb-4 text-xl">{{ $c[2] }}</div>
                        <h3 class="display text-lg font-semibold mb-2">{{ $c[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $c[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- INTELIGENȚĂ --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ inteligență</div>
                <h2 class="display h-display-l">Răspunde <em class="italic accent-text">ce știe</em> — nu ce pare.</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach([
                    ['Antrenat pe conținutul tău', 'PDF-uri, pagini web, catalog de produse, politici interne — fiecare răspuns are o sursă citabilă.'],
                    ['Politici anti-halucinare', 'Reguli stricte de confidence și prudență pe subiecte sensibile. Când nu știe, recunoaște onest.'],
                    ['Învăț automat din conversații', 'Întrebările pe care nu le acoperi în documente devin sugestii pentru baza ta de cunoștințe.'],
                    ['Ton și personalitate ajustabile', 'De la formal la prietenos. Configurabil pe fiecare canal separat.'],
                    ['Detectare frustrare și escaladare', 'Transferă la operator uman când clientul are nevoie, cu tot contextul conversației.'],
                    ['Multilingv: română și engleză', 'Detectează automat limba clientului. Fără accent robotic, fără traduceri stângace.'],
                ] as $f)
                    <div class="fade-up rounded-3xl p-6 bg-cream border border-line">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 rounded-lg accent-soft-bg flex items-center justify-center">
                                <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $f[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $f[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- OPERAȚIONAL --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ operațional</div>
                <h2 class="display h-display-l">Transformă conversații<br><span class="italic accent-text">în rezultate.</span></h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['Programări în calendar', 'Sincronizare Google Calendar, slot-uri libere în timp real, confirmare SMS/e-mail.'],
                    ['Lead capture automat', 'Nume, telefon, context — extrase din conversație, cu scoring de intenție.'],
                    ['Pipeline CRM integrat', 'Stadii configurabile pentru lead-uri și oportunități, cu notificări pe echipă.'],
                    ['Callback widget', 'Formular de cerere retur apel, cu consimțământ GDPR explicit.'],
                    ['Recomandări produse', 'Carduri de produs din magazin, cu preț și stoc live.'],
                    ['Dashboard analitice', 'Conversații, apeluri, lead-uri, conversii — într-un singur loc.'],
                ] as $f)
                    <div class="fade-up rounded-3xl p-6 bg-paper border border-line">
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $f[0] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $f[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- INTEGRĂRI live --}}
        <div>
            <div class="max-w-2xl mb-10 fade-up">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ integrări</div>
                <h2 class="display h-display-l">Se conectează la<br><span class="italic accent-text">uneltele tale curente.</span></h2>
                <p class="text-lg mt-4" style="color: var(--muted);">Listăm doar integrările live pe care le poți activa astăzi. Dacă ai nevoie de ceva care nu e aici, ne scrii — adăugăm la cerere.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['WooCommerce', 'Plugin oficial. Sincronizare automată produse, stoc și categorii. Recomandări în conversație.'],
                    ['WordPress', 'Instalare widget în 2 click-uri prin plugin. Funcționează cu orice temă.'],
                    ['Google Calendar', 'OAuth per cont. Agentul vede slot-urile libere și programează direct în calendar.'],
                    ['WhatsApp Business', 'Conectare inbound + outbound. Mesajele curg prin același dashboard.'],
                    ['Facebook Messenger', 'Inbound pentru pagina ta — răspunsuri din aceeași bază de cunoștințe.'],
                    ['Instagram DM', 'Inbound pentru contul de business. Conversații centralizate în inbox.'],
                    ['Stripe', 'Facturare abonamente + top-up credite. Facturi automate în contabilitate.'],
                    ['Google Drive', 'Import documente direct dintr-un folder de drive pentru baza de cunoștințe.'],
                    ['API + webhooks', 'Integrări custom prin API REST și event webhooks — pentru CRM-ul tău intern.'],
                ] as $i)
                    <div class="fade-up rounded-2xl p-5 bg-cream border border-line">
                        <h3 class="display text-base font-semibold mb-2 leading-tight">{{ $i[0] }}</h3>
                        <p class="text-xs leading-relaxed" style="color: var(--muted);">{{ $i[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</section>

{{-- TABEL: Sambla AI vs Operator uman --}}
<section class="py-20 bg-paper border-t border-line">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ agent ai vs operator uman</div>
            <h2 class="display h-display-m mb-4">Cifre <em class="italic accent-text">sincere</em>.</h2>
            <p class="text-lg" style="color: var(--muted);">Nu-l comparăm cu un om ca să-l înlocuim. Îl comparăm ca să vezi unde ajută și unde nu. Operatorul uman rămâne esențial — agentul preia volumul pe care omul nu-l poate prelua.</p>
        </div>

        @php
            $vsRows = [
                ['Disponibilitate',       'Luni–Vineri, ~8h/zi',           '24/7, inclusiv sărbători'],
                ['Timp de răspuns',       '2–10 minute (sau mai mult)',    'sub 1 secundă'],
                ['Cost per interacțiune', '2–5 € (salariu + taxe)',        '0,10 – 0,30 € (api + infra)'],
                ['Scalabilitate',         'angajezi alt om, training 2 săpt.', 'scalează la mii de conversații paralel'],
                ['Consistență',           'variază după dispoziție / oboseală', 'același răspuns de fiecare dată'],
                ['Limbi',                 '1–2 fluent',                    '10+ cu detecție automată'],
                ['Sentiment & escaladare',   'subiectiv, greu de măsurat',    'detecție live + transfer automat'],
                ['Oboseală / erori',      'crescătoare pe parcursul zilei', 'constantă'],
            ];
        @endphp

        <div class="overflow-x-auto -mx-4 px-4 lg:mx-0 lg:px-0 fade-up">
            <table class="min-w-[680px] w-full bg-cream rounded-3xl border border-line overflow-hidden text-sm">
                <thead>
                    <tr style="background: var(--ink); color: var(--cream);">
                        <th class="px-5 py-5 text-left font-semibold w-[28%]">Dimensiune</th>
                        <th class="px-5 py-5 text-left font-semibold w-[36%]">Operator uman</th>
                        <th class="px-5 py-5 text-left font-bold w-[36%] accent-bg" style="color: #fff;">Agent AI Sambla</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--line)]">
                    @foreach($vsRows as $r)
                        <tr class="hover:bg-paper transition">
                            <td class="px-5 py-4 font-semibold">{{ $r[0] }}</td>
                            <td class="px-5 py-4" style="color: var(--muted);">{{ $r[1] }}</td>
                            <td class="px-5 py-4 accent-soft-bg font-medium">{{ $r[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs mt-4 text-center lg:hidden" style="color: var(--muted);">← Glisează lateral pentru tot tabelul →</p>
        <p class="text-sm mt-6 text-center" style="color: var(--muted);">Asta e matematica. De aceea folosești omul pentru conversațiile unde contează empatia, și agentul pentru volumul care azi îți lasă clienți neserviți.</p>
    </div>
</section>

{{-- CONVERSAȚIE INTELIGENTĂ --}}
<section class="py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ conversație inteligentă</div>
            <h2 class="display h-display-l">Nu răspunde doar. <em class="italic accent-text">Conduce conversația.</em></h2>
        </div>

        <div class="grid md:grid-cols-12 gap-8 items-start">
            {{-- Callouts --}}
            <div class="md:col-span-5 space-y-4 fade-up">
                @foreach([
                    ['🌍', 'Multi-limbă automat', 'Detectează limba din primul mesaj și păstrează-o. Română, engleză, germană, franceză, spaniolă, italiană, maghiară — toate fluente.'],
                    ['🎭', '6 personalități', 'Ales dintr-o paletă sau configurat custom: profesional, prietenos, premium, jovial, empatic, tehnic. Configurabil pe canal.'],
                    ['🔔', 'Detecție frustrare în timp real', 'Monitorizează tonul și escalează la operator uman înainte ca clientul să închidă frustrat.'],
                    ['⚙️', 'Reguli de business', 'Condiții pe care agentul le urmează mereu: niciodată nu promite livrare în 24h fără verificare stoc, de ex.'],
                ] as $c)
                    <div class="rounded-3xl p-6 bg-paper border border-line">
                        <div class="flex gap-4 items-start">
                            <div class="w-11 h-11 rounded-xl accent-soft-bg flex items-center justify-center text-xl shrink-0">{{ $c[0] }}</div>
                            <div>
                                <h3 class="display text-base font-semibold mb-1.5 leading-tight">{{ $c[1] }}</h3>
                                <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $c[2] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Chat mockup: tracking comandă --}}
            <div class="md:col-span-7 fade-up" style="transition-delay:.1s;">
                <div class="rounded-[2rem] overflow-hidden bg-paper border border-line">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-line bg-cream">
                        <div class="flex items-center gap-2">
                            <div class="flex gap-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#F87171]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#FBBF24]"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-[#34D399]"></span>
                            </div>
                            <span class="mono text-[10px] ml-2" style="color: var(--muted);">chat · magazin online</span>
                        </div>
                        <span class="chip accent-soft-bg mono text-[10px]" style="color: var(--accent-dark);">live</span>
                    </div>

                    <div class="px-5 py-5 space-y-3 text-sm">
                        <div class="flex justify-end"><div class="max-w-[78%] bg-ink text-cream rounded-2xl rounded-tr-sm px-4 py-2.5">Bună, unde e comanda #4521?</div></div>

                        <div class="flex"><div class="max-w-[82%] bg-cream border border-line rounded-2xl rounded-tl-sm px-4 py-2.5">Salut! Am găsit comanda #4521 pe numele tău. E <strong>în tranzit</strong>, livrată de FanCourier, AWB 1234567890. Estimat mâine 12:00–16:00.</div></div>

                        {{-- Tracking card --}}
                        <div class="flex">
                            <div class="max-w-[82%] rounded-2xl rounded-tl-sm bg-cream border border-line overflow-hidden">
                                <div class="px-4 pt-3 pb-2 flex items-center justify-between">
                                    <span class="mono text-[10px] uppercase tracking-wider" style="color: var(--muted);">◇ tracking awb</span>
                                    <span class="chip accent-soft-bg mono text-[10px]" style="color: var(--accent-dark);">FanCourier</span>
                                </div>
                                <div class="px-4 pb-4">
                                    <div class="text-xs mb-3" style="color: var(--muted);">AWB 1234567890 · Estimat 21 apr, 12:00–16:00</div>
                                    <div class="flex items-center gap-0 text-[10px]">
                                        <div class="flex-1 flex flex-col items-center gap-1"><span class="w-4 h-4 rounded-full accent-bg flex items-center justify-center"><svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg></span><span>preluat</span></div>
                                        <div class="h-[2px] w-6 accent-bg"></div>
                                        <div class="flex-1 flex flex-col items-center gap-1"><span class="w-4 h-4 rounded-full accent-bg flex items-center justify-center"><svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg></span><span>sortat</span></div>
                                        <div class="h-[2px] w-6 accent-bg"></div>
                                        <div class="flex-1 flex flex-col items-center gap-1"><span class="w-4 h-4 rounded-full accent-bg ring-4 ring-offset-0 ring-[var(--accent-soft)] animate-pulse"></span><span class="font-semibold accent-text">în tranzit</span></div>
                                        <div class="h-[2px] w-6" style="background: var(--line);"></div>
                                        <div class="flex-1 flex flex-col items-center gap-1"><span class="w-4 h-4 rounded-full" style="background: var(--line);"></span><span style="color: var(--muted);">livrat</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end"><div class="max-w-[78%] bg-ink text-cream rounded-2xl rounded-tr-sm px-4 py-2.5">Super, mulțumesc!</div></div>
                        <div class="flex"><div class="max-w-[82%] bg-cream border border-line rounded-2xl rounded-tl-sm px-4 py-2.5">Cu plăcere. Dacă nu ajunge mâine până la 16:00, îți trimitem automat un mesaj cu detalii. 📦</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ANALYTICS & RAPOARTE --}}
<section class="py-20 bg-cream border-y border-line">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ analytics &amp; rapoarte</div>
            <h2 class="display h-display-l">Vezi <em class="italic accent-text">ce se întâmplă</em> în spatele conversațiilor.</h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 fade-up">
            @foreach([
                ['Conversații',  'Volum zilnic, trend 7/30/90 zile, rata de soluționare fără operator'],
                ['Costuri',      'Cost per mesaj, per conversație, per agent — actualizat live'],
                ['Vânzări',      'Atribuire pe 3 moduri: strict (directă), probabil, asistat'],
                ['Sentiment',    'Detecție live pozitiv / neutru / frustrat, cu alertă pe escaladări'],
            ] as $m)
                <div class="rounded-3xl p-6 bg-paper border border-line">
                    <div class="mono text-[10px] uppercase tracking-wider mb-2 accent-text">◇ {{ strtolower($m[0]) }}</div>
                    <h3 class="display text-lg font-semibold mb-2">{{ $m[0] }}</h3>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $m[1] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid md:grid-cols-3 gap-4 fade-up" style="transition-delay:.1s;">
            @foreach([
                ['40+ tipuri de evenimente',  'Tracking granular pe fiecare conversație: întrebări, intenții, produse afișate, click-uri, adăugări în coș, transferuri.'],
                ['Atribuire vânzări în 3 moduri', 'Strict: clientul a achiziționat direct după conversație. Probabil: a cumpărat în 7 zile. Asistat: agentul a contribuit, nu a încheiat.'],
                ['Detecție knowledge gaps',   'Întrebările la care agentul nu știe să răspundă sunt marcate automat ca sugestii pentru baza de cunoștințe.'],
            ] as $s)
                <div class="rounded-2xl p-5 bg-paper border border-line">
                    <h4 class="font-semibold mb-1.5">{{ $s[0] }}</h4>
                    <p class="text-xs leading-relaxed" style="color: var(--muted);">{{ $s[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- SECURITATE & FIABILITATE --}}
<section class="py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ securitate &amp; fiabilitate</div>
            <h2 class="display h-display-l">Construit <em class="italic accent-text">să nu te dea de rușine</em>.</h2>
            <p class="text-lg mt-4" style="color: var(--muted);">Nu îți lăsăm datele clienților pe mesele altora. Nu ne bazăm pe „sperăm că e OK". Sunt reguli stricte, scrise în cod și verificate automat.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['Izolare pe cont', 'Datele fiecărui client sunt separate complet. Niciun cont nu poate accesa accidental datele altuia — nici prin bug, nici prin config greșit.', '🔐'],
                ['GDPR nativ',      'DPA gata de semnat, export și ștergere la cerere în max 48h, retenție configurabilă per tip de date.', '🇪🇺'],
                ['Rate limit',      'Protecție la abuz pe API-uri publice. Un atacator nu poate face mii de cereri care să te coste pe tine.', '🛡️'],
                ['Control acces pe roluri', '4 niveluri: Super Admin, Admin, Manager, Viewer. Permisiuni granulare pe fiecare secțiune.', '🧑‍💼'],
                ['Protecție SSRF',  'Integrările externe nu pot fi forțate să acceseze rețele interne. Liste de blocare și validare URL.', '🚧'],
                ['Backup-uri criptate', 'Zilnice, retenție 30 zile, criptate la repaus. Restore testat periodic, nu doar declarat.', '💾'],
                ['Audit trail',     'Fiecare acțiune în platformă e logată cu user, timestamp, IP. Pentru investigații GDPR sau de securitate.', '📋'],
            ] as $s)
                <div class="fade-up rounded-3xl p-6 bg-paper border border-line">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl accent-soft-bg flex items-center justify-center text-lg">{{ $s[2] }}</div>
                        <h3 class="display text-base font-semibold leading-tight">{{ $s[0] }}</h3>
                    </div>
                    <p class="text-sm leading-relaxed" style="color: var(--muted);">{{ $s[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- API & DEZVOLTATORI --}}
<section class="py-20 bg-paper border-y border-line">
    <div class="max-w-5xl mx-auto px-6">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ pentru dezvoltatori</div>
            <h2 class="display h-display-l">Conectează-l <em class="italic accent-text">la sistemul tău</em>.</h2>
            <p class="text-lg mt-4" style="color: var(--muted);">Ai un CRM, ERP sau aplicație internă? Inițiezi apeluri din cod, primești evenimente prin webhook, sincronizezi conversațiile în timp real. API REST documentat complet, autentificare cu cheie API, fără proceduri exotice.</p>
        </div>

        <div class="rounded-3xl overflow-hidden border border-line shadow-2xl fade-up">
            <div class="px-4 py-3 flex items-center gap-3" style="background: #1C1917;">
                <div class="flex gap-2">
                    <div class="w-3 h-3 rounded-full" style="background: rgba(220,38,38,.7);"></div>
                    <div class="w-3 h-3 rounded-full" style="background: rgba(245,158,11,.7);"></div>
                    <div class="w-3 h-3 rounded-full" style="background: rgba(16,185,129,.7);"></div>
                </div>
                <span class="mono text-xs ml-2" style="color: #78716C;">api-example.js</span>
            </div>
            <div class="p-6 overflow-x-auto" style="background: #0C0A09;">
<pre class="mono text-sm leading-relaxed" style="color: #E7E5E4;"><span style="color: #78716C;">// Inițiezi un apel programat prin agentul AI</span>
<span style="color: #F87171;">const</span> response = <span style="color: #F87171;">await</span> <span style="color: #60A5FA;">fetch</span>(<span style="color: #34D399;">'https://api.sambla.ro/v1/calls'</span>, {
  method: <span style="color: #34D399;">'POST'</span>,
  headers: {
    <span style="color: #34D399;">'Authorization'</span>: <span style="color: #34D399;">'Bearer YOUR_API_KEY'</span>,
    <span style="color: #34D399;">'Content-Type'</span>: <span style="color: #34D399;">'application/json'</span>
  },
  body: <span style="color: #60A5FA;">JSON</span>.stringify({
    to: <span style="color: #34D399;">'+40721234567'</span>,
    agent_id: <span style="color: #34D399;">'agent_receptie'</span>,
    scenario: <span style="color: #34D399;">'programare_consultatie'</span>,
    language: <span style="color: #34D399;">'ro'</span>,
    webhook_url: <span style="color: #34D399;">'https://app.example.com/webhook'</span>
  })
});

<span style="color: #F87171;">const</span> call = <span style="color: #F87171;">await</span> response.<span style="color: #60A5FA;">json</span>();
<span style="color: #60A5FA;">console</span>.<span style="color: #60A5FA;">log</span>(<span style="color: #34D399;">`Apel inițiat: ${call.id}`</span>);</pre>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-8 justify-center">
            @foreach([
                ['REST API', 'Endpoints REST documentate'],
                ['WebSocket', 'Streaming în timp real'],
                ['Webhook-uri', 'Evenimente push către tine'],
                ['Cheie API', 'Bearer token, scoped per integrare'],
            ] as $b)
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-cream border border-line">
                    <span class="w-1.5 h-1.5 rounded-full" style="background: var(--accent);"></span>
                    <span>{{ $b[0] }}</span>
                    <span style="color: var(--muted);">· {{ $b[1] }}</span>
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-20">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="display h-display-l mb-5">Vezi cum arată pentru <em class="italic accent-text">afacerea ta</em>.</h2>
        <p class="text-lg mb-8" style="color: var(--muted);">Alegi industria, agentul preia instant tonul potrivit.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ url('/register') }}" class="btn btn-primary">Începe gratuit</a>
            <a href="{{ route('new.home') }}#industrii" class="btn btn-outline">Vezi industriile</a>
        </div>
    </div>
</section>

@endsection
