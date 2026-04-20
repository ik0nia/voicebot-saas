@extends('layouts.new')

@section('title', 'Sambla — Agenți AI care răspund clienților tăi 24/7, în limba română')
@section('meta_description', 'Agenți AI care răspund la telefon, pe site și pe WhatsApp — 24/7, în limba română, din documentele afacerii tale. Programări automate, lead-uri calificate, răspunsuri fără halucinații. 7 zile gratuit.')
@section('og_title', 'Agenții AI care preiau apelurile și scriu clienților în locul tău')
@section('og_description', 'Răspund 24/7, în limba română, din documentele afacerii tale. Fac programări, recomandă produse, preiau lead-uri.')
@section('canonical', url('/new'))

@section('jsonld')
<script type="application/ld+json">
{
    "@context":"https://schema.org",
    "@type":"SoftwareApplication",
    "name":"Sambla",
    "applicationCategory":"BusinessApplication",
    "operatingSystem":"Web (Cloud SaaS)",
    "offers":{"@type":"AggregateOffer","lowPrice":"29","highPrice":"399","priceCurrency":"RON"},
    "aggregateRating":{"@type":"AggregateRating","ratingValue":"4.9","reviewCount":"52"},
    "description":"Agenți AI conversaționali — pe site, pe telefon, pe WhatsApp — antrenați pe documentele afacerii tale.",
    "url":"https://sambla.ro/new"
}
</script>
@endsection

@section('content')

{{-- HERO --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 pt-20 pb-24 grid lg:grid-cols-12 gap-12 items-start relative">
        <div class="lg:col-span-6 fade-up">
            <div class="chip chip-outline mb-7">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-coral opacity-60 animate-ping"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-coral"></span>
                </span>
                <span class="mono text-[11px] uppercase tracking-wider">live · răspunde acum</span>
            </div>

            <h1 class="display text-5xl md:text-6xl lg:text-7xl font-medium leading-[1.02] tracking-tight mb-7">
                Angajatul tău AI care<br>
                <span class="italic font-normal accent-text">nu iese din tură.</span>
            </h1>

            <p class="text-xl leading-relaxed text-muted mb-9 max-w-xl">
                Răspunde la telefon, pe site și pe WhatsApp — la 3 dimineața, duminica, în concediu. Cu documentele, produsele și tonul afacerii tale.
                <span class="text-ink font-medium">Nu inventează. Nu ghicește. Știe.</span>
            </p>

            <div class="flex flex-wrap gap-3 mb-9">
                <a href="{{ url('/register') }}" class="btn-primary">
                    Începe gratuit
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="#demo" class="btn-outline">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                    Ascultă o conversație
                </a>
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Fără card</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Setup 10 min</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> GDPR nativ</span>
                <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg> Construit în 🇷🇴</span>
            </div>
        </div>

        {{-- Rotating hero chat card --}}
        <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
            <div class="relative">
                <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" id="heroGlow" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%); transition: background .6s ease;"></div>
                <div id="heroChatCard" data-niche="" class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-line accent-soft-bg transition-colors duration-500">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full accent-bg flex items-center justify-center transition-colors duration-500">
                                <span class="text-white display text-base font-semibold">S</span>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-paper">
                                <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60"></span>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">Sambla</div>
                            <div class="text-xs text-muted flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online · răspunde instant
                            </div>
                        </div>
                        <span id="heroScenarioLabel" class="chip accent-soft-bg mono text-[10px] transition-all duration-500" style="color: var(--accent-dark);">🦷 Cabinet stomatologic</span>
                    </div>

                    <div id="heroChat" class="px-5 py-4 h-[420px] overflow-y-auto relative no-scrollbar">
                        <div id="heroChatInner" class="space-y-3"></div>
                        <div id="heroTyping" class="hidden items-center gap-2 mt-3">
                            <div class="bg-sand rounded-2xl rounded-bl-sm px-4 py-2.5">
                                <div class="dots flex gap-1.5 h-4 items-center">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
                            <span id="heroFooterText" class="text-xs text-muted font-medium transition-opacity duration-300 truncate">Răspuns din baza de cunoștințe</span>
                        </div>
                        <div id="heroDotsContainer" class="flex gap-1.5 shrink-0"></div>
                    </div>
                </div>

                <div class="absolute -left-4 -bottom-4 bg-white rounded-2xl shadow-xl p-4 pr-5 flex items-center gap-3 border border-line max-w-[260px] float" style="animation-delay:.5s;">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div id="heroBadgeTitle" class="text-sm font-semibold leading-tight transition-all duration-500">Programare confirmată</div>
                        <div class="text-xs text-muted">automat · fără operator</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Trust strip (generic categories, no fake client logos) --}}
<section class="border-y border-line/60 bg-paper">
    <div class="max-w-7xl mx-auto px-6 py-10">
        <p class="text-center mono text-[11px] uppercase tracking-[0.2em] text-muted mb-7">Afaceri românești care folosesc agenți AI Sambla</p>
        <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-4 opacity-70">
            <div class="display text-xl font-semibold tracking-tight">🦷 Stomatologie</div>
            <div class="display italic text-xl">💆 Estetică</div>
            <div class="mono text-sm font-medium">🔧 AUTO · SERVICE</div>
            <div class="display font-bold text-xl">🏠 Imobiliare</div>
            <div class="display font-semibold tracking-wide text-xl">🍽️ HORECA</div>
            <div class="font-light text-xl">⚖️ Avocatură</div>
            <div class="display text-xl">🛒 E-commerce</div>
        </div>
    </div>
</section>

{{-- 3 channels --}}
<section id="colegi" class="py-24 bg-paper">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ un singur creier</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
                Trei canale.<br>
                <span class="italic">Aceleași răspunsuri.</span>
            </h2>
            <p class="text-lg text-muted leading-relaxed">Clientul sună sau scrie — primește același răspuns expert. Aceeași bază de cunoștințe, același ton, aceleași politici. Într-un singur dashboard.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div class="niche-card rounded-3xl p-8 bg-cream fade-up" style="border:1px solid #E7E0CE;" data-niche="">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
                        <svg class="w-6 h-6 text-ink" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
                    </div>
                    <span class="chip mono text-[10px]" style="background:#D1FAE5; color:#047857;">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        live · beta
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3">Telefon</h3>
                <p class="text-muted leading-relaxed mb-5">Agentul ridică apelul, vorbește natural în română, confirmă programări, escaladează când e nevoie. Voce clonată AI premium (opțional).</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><span class="text-coral">✦</span> Numere românești dedicate</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Latență sub 800ms, barge-in natural</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Transcriere live + analiză sentiment</li>
                </ul>
            </div>

            <div class="niche-card rounded-3xl p-8 bg-ink text-cream fade-up" style="transition-delay: .1s;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:#3A3532;">
                        <svg class="w-6 h-6 text-coral" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4m-4 4l-4-4h4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
                    </div>
                    <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.1); color:#F2E59A;">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-300"></span>
                        cel mai folosit
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3">Chat pe site</h3>
                <p class="leading-relaxed mb-5" style="color:#D7D3CA;">Widget premium. O linie de cod. Răspunde instant, recomandă produse, preia lead-uri, trimite link-uri direct la paginile relevante.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><span class="text-coral">✦</span> Carduri produse, preview link, asistență proactivă</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Dark mode, branding custom, mobile-first</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Sandboxed iframe, zero risc XSS</li>
                </ul>
            </div>

            <div class="niche-card rounded-3xl p-8 bg-cream fade-up" style="border:1px solid #E7E0CE; transition-delay: .2s;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
                        <svg class="w-6 h-6 text-ink" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l-2-5-5-2 5-2 2-5 2 5 5 2-5 2-2 5z"/></svg>
                    </div>
                    <span class="chip chip-outline text-[10px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        multi-canal
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3">WhatsApp · FB · IG</h3>
                <p class="text-muted leading-relaxed mb-5">Un singur inbox pentru toate canalele sociale. Sincronizare WooCommerce nativă. Produse cu stoc real, nu date vechi.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><span class="text-coral">✦</span> Produse cu stoc real, nu date vechi</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Handoff către operator când e cazul</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Istoric unificat per client</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Onboarding 3 steps --}}
<section id="onboarding" class="py-24 grain relative">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="max-w-xl mb-16 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ onboarding</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
                De la <em class="italic accent-text">pagină goală</em><br>
                la agent live, în 8 minute.
            </h2>
        </div>

        <div class="space-y-4">
            <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">01</div></div>
                <div class="md:col-span-7">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="display text-2xl font-semibold">Spune-i despre afacerea ta</h3>
                        <span class="chip chip-outline mono text-[10px]">~2 min</span>
                    </div>
                    <p class="text-muted leading-relaxed">Descrie în 2 propoziții ce face afacerea ta. AI-ul generează automat prompt-ul, personalitatea și setările inițiale — tu doar ajustezi.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl bg-cream p-4 border border-line">
                        <div class="mono text-[10px] uppercase text-muted mb-2">Exemplu input</div>
                        <p class="text-sm italic">„Clinică stomatologică în București, sector 2. Acceptăm programări online, plătim cu cardul, asigurare parțială."</p>
                    </div>
                </div>
            </div>

            <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">02</div></div>
                <div class="md:col-span-7">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="display text-2xl font-semibold">Hrănește baza de cunoștințe</h3>
                        <span class="chip chip-outline mono text-[10px]">~5 min</span>
                    </div>
                    <p class="text-muted leading-relaxed">Uploadezi PDF-uri, scanezi site-ul, conectezi magazinul WooCommerce. Agentul AI învață fiecare pagină, fiecare politică, fiecare produs — cu preț, stoc și imagini.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl bg-cream p-3 border border-line space-y-1.5">
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded bg-red-50 text-red-600 flex items-center justify-center text-[10px] font-bold">PDF</span>
                            <span class="flex-1 truncate">Tarife 2026.pdf</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] font-bold">WEB</span>
                            <span class="flex-1 truncate">sambla.ro — 32 pagini</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded bg-violet-50 text-violet-600 flex items-center justify-center text-[10px] font-bold">WOO</span>
                            <span class="flex-1 truncate">247 produse · sync live</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fade-up rounded-3xl p-7 md:p-8 bg-ink text-cream grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-1"><div class="display text-6xl font-semibold" style="color:#F2E59A;">03</div></div>
                <div class="md:col-span-7">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="display text-2xl font-semibold">Pune-l la treabă</h3>
                        <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">~1 min</span>
                    </div>
                    <p class="leading-relaxed" style="color:#D7D3CA;">O linie de cod pe site. Număr românesc dedicat pentru apeluri. WhatsApp Business conectat. Și gata — agentul e la muncă.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl p-4 mono text-xs leading-relaxed" style="background:#0F0E0C; color:#F2E59A;">
                        <div style="color:#78716C;">// adaugă în &lt;head&gt;</div>
                        <div>&lt;script src=<span style="color:#A7C7F0;">"https://cdn.sambla.ro/w.js"</span><br>&nbsp;&nbsp;data-bot=<span style="color:#A7C7F0;">"afacerea-ta"</span>&gt;&lt;/script&gt;</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 text-center fade-up">
            <a href="{{ url('/register') }}" class="btn-primary">
                Încearcă tu în 10 minute
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- Trust / anti-hallucination --}}
<section id="incredere" class="py-24 bg-paper">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-3xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ de ce să ai încredere</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
                Dacă nu știe,<br>
                <span class="italic accent-text">spune că nu știe.</span>
            </h2>
            <p class="text-lg text-muted leading-relaxed">Orice sistem AI poate inventa — e natura tehnologiei. De asta am construit reguli stricte între întrebarea clientului și răspunsul primit. Când agentul nu e sigur, recunoaște cinstit și trimite spre operator uman. Fără improvizații pe politici, prețuri sau promisiuni.</p>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
            @foreach([
                ['01','Înțelege intenția','Detectează ce vrea clientul: cumpără, întreabă, reclamă, compară. Adaptează tot răspunsul.','🎯'],
                ['02','Caută în documentele tale','Răspunsul vine direct din ce ai încărcat tu — PDF-uri, pagini, fișe produse. Nu din ghiciri.','🔎'],
                ['03','Verifică înainte să răspundă','Confidence scoring · citare sursă obligatorie · detecție halucinație activă.','🛡️'],
                ['04','Răspunde în context','Empatie la frustrare. Recomandare la interes. Scurt dacă vrea rapid. Mereu în tonul tău.','💬'],
            ] as $s)
                <div class="fade-up rounded-3xl p-6 bg-cream border border-line">
                    <div class="flex items-center justify-between mb-5">
                        <div class="text-3xl">{{ $s[3] }}</div>
                        <div class="mono text-xs text-muted">{{ $s[0] }}</div>
                    </div>
                    <h3 class="display text-lg font-semibold mb-2">{{ $s[1] }}</h3>
                    <p class="text-sm text-muted leading-relaxed">{{ $s[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Big stats --}}
<section class="py-24 bg-cream relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ cifrele care contează</div>
            <h2 class="display text-4xl md:text-5xl font-medium leading-tight">Construit pentru calitate,<br><span class="italic accent-text">nu pentru demo-uri.</span></h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center">
                <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium accent-text" data-countup="94">94<span class="text-ink">%</span></div>
                <div class="mt-2 text-sm text-muted">rată rezolvare</div>
                <div class="mono text-[10px] text-muted mt-1">fără operator uman</div>
            </div>
            <div class="fade-up rounded-3xl p-8 bg-ink text-cream text-center" style="transition-delay:.1s;">
                <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium" style="color:#F2E59A;">&lt;2s</div>
                <div class="mt-2 text-sm" style="color:#D7D3CA;">latență răspuns</div>
                <div class="mono text-[10px] mt-1" style="color:#A8A29E;">end-to-end, p95</div>
            </div>
            <div class="fade-up rounded-3xl p-8 bg-paper border border-line text-center" style="transition-delay:.2s;">
                <div class="display text-[7rem] md:text-[8rem] stat-num leading-none font-medium accent-text" data-countup="100">100<span class="text-ink">%</span></div>
                <div class="mt-2 text-sm text-muted">răspunsuri cu sursă</div>
                <div class="mono text-[10px] text-muted mt-1">citabile, verificabile</div>
            </div>
            <div class="fade-up rounded-3xl p-8 text-center" style="transition-delay:.3s; background: linear-gradient(135deg, #FCE7E3 0%, #FDBA8C 100%);">
                <div class="display text-[7rem] md:text-[8rem] leading-none font-medium">🇷🇴</div>
                <div class="mt-2 text-sm font-medium">construit în RO</div>
                <div class="mono text-[10px] text-muted mt-1">GDPR nativ, UE-only</div>
            </div>
        </div>
    </div>
</section>

{{-- Industries grid (dinamic din DB, color-adaptive per niche) --}}
<section id="industrii" class="py-24 bg-paper">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ industrii</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Creat pentru <em class="italic">afacerea ta</em>.</h2>
            <p class="text-lg text-muted">{{ $niches->count() ?: 17 }} verticale cu prompt-uri, personalități și integrări adaptate pentru piața românească.</p>
        </div>

        @php
            /* Emoji fallback map — actual DB slugs, used only when the
               niche row has no icon_svg. */
            $nicheEmojiMap = [
                'cabinete-stomatologice'     => '🦷',
                'cabinete-medicale'          => '🏥',
                'salon-beauty'               => '💆',
                'service-auto'               => '🔧',
                'magazine-online'            => '🛒',
                'agentii-imobiliare'         => '🏠',
                'birouri-avocatura'          => '⚖️',
                'firme-contabilitate'        => '📚',
                'restaurante-delivery'       => '🍽️',
                'pensiuni-hoteluri-mici'     => '🏨',
                'optica-medicala'            => '👓',
                'agentii-turism'             => '✈️',
                'birouri-notariale'          => '📜',
                'psihologie-psihoterapie'    => '🧠',
                'firme-curatenie'            => '🧹',
                'clinici-veterinare'         => '🐾',
                'scoli-limbi-straine'        => '🎓',
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($niches as $n)
                <a href="{{ route('new.niche', $n->slug) }}" data-niche="{{ $n->color_theme }}" class="niche-card group block rounded-2xl overflow-hidden bg-cream border border-line">
                    <div class="accent-soft-bg p-5 flex items-center justify-between">
                        <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center accent-text shrink-0">
                            @if(!empty($n->icon_svg))
                                <span class="w-6 h-6 inline-flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">{!! $n->icon_svg !!}</span>
                            @else
                                <span class="text-2xl">{{ $nicheEmojiMap[$n->slug] ?? '✦' }}</span>
                            @endif
                        </div>
                        <span class="w-8 h-8 rounded-full accent-bg flex items-center justify-center shrink-0 group-hover:scale-110 transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                    </div>
                    <div class="p-5">
                        <div class="mono text-[10px] uppercase tracking-[0.15em] accent-text mb-2">{{ $n->vertical_label ?: 'Agent AI' }}</div>
                        <div class="display text-lg font-semibold mb-1 leading-snug">{{ $n->name }}</div>
                        @if(!empty($n->hero_subtitle))
                            <p class="text-xs text-muted leading-relaxed line-clamp-2">{{ Str::limit($n->hero_subtitle, 90) }}</p>
                        @endif
                    </div>
                </a>
            @empty
                @foreach([['🦷','Stomatologie','emerald'],['💆','Estetică','rose'],['🔧','Service auto','orange'],['🏠','Imobiliare','amber'],['⚖️','Avocatură','purple'],['🍽️','Restaurante','emerald'],['🛒','E-commerce','blue'],['🏨','Pensiuni','teal']] as $ind)
                    <a href="#" data-niche="{{ $ind[2] }}" class="niche-card group block rounded-2xl overflow-hidden bg-cream border border-line">
                        <div class="accent-soft-bg p-5 flex items-center justify-between">
                            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center text-2xl">{{ $ind[0] }}</div>
                            <span class="w-8 h-8 rounded-full accent-bg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </span>
                        </div>
                        <div class="p-5">
                            <div class="mono text-[10px] uppercase tracking-[0.15em] accent-text mb-2">Agent AI</div>
                            <div class="display text-lg font-semibold">{{ $ind[1] }}</div>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('new.industrii') }}" class="inline-flex items-center gap-2 text-sm font-medium text-ink hover:accent-text transition">
                Vezi toate industriile disponibile
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- Testimonial big --}}
<section class="py-24 grain relative">
    <div class="max-w-6xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink text-cream p-10 md:p-16 overflow-hidden relative grid md:grid-cols-12 gap-10 items-center fade-up">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, rgba(220,38,38,0.3) 0%, transparent 70%);"></div>

            <div class="md:col-span-8 relative">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6" style="color:#F2E59A;">◇ experiență reală</div>
                <p class="display text-3xl md:text-4xl leading-[1.15] font-normal mb-8">
                    „Pierdeam jumătate din apelurile după program. Acum
                    <span class="italic" style="color:#F2E59A;">fiecare e preluat de Sambla</span>
                    — și programările se fac în timp ce dormim. Veniturile au crescut vizibil în primele luni."
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold" style="background:#F2E59A; color:#1C1917;">M</div>
                    <div>
                        <div class="font-semibold">Medic coordonator</div>
                        <div class="text-sm" style="color:#A8A29E;">Clinică estetică · București</div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-4 relative">
                <div class="rounded-2xl p-5 space-y-4" style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);">
                    <div>
                        <div class="text-xs mb-1" style="color:#A8A29E;">Apeluri pierdute · săpt.</div>
                        <div class="flex items-baseline gap-2">
                            <span class="display text-3xl line-through" style="color:#78716C;">42</span>
                            <span class="display text-4xl accent-text font-semibold">3</span>
                        </div>
                    </div>
                    <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
                    <div>
                        <div class="text-xs mb-1" style="color:#A8A29E;">Programări / lună</div>
                        <div class="flex items-baseline gap-2">
                            <span class="display text-3xl line-through" style="color:#78716C;">120</span>
                            <span class="display text-4xl font-semibold" style="color:#F2E59A;">188</span>
                        </div>
                    </div>
                    <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
                    <div>
                        <div class="text-xs mb-1" style="color:#A8A29E;">Timp recuperat</div>
                        <div class="display text-4xl font-semibold accent-text">+18h</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="preturi" class="py-24">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-xl mx-auto text-center mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ prețuri</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5"><em class="italic accent-text">Simple.</em> În lei.<br>Fără surprize.</h2>
            <p class="text-lg text-muted">7 zile gratuit. Fără card. Anulezi oricând. 30% reducere ONG + școli.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div class="fade-up rounded-3xl p-8 bg-paper border border-line">
                <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Starter</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-6xl font-medium">29</span>
                    <span class="text-muted">lei / lună</span>
                </div>
                <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Agent AI simplu pe un site.</p>
                <ul class="space-y-2.5 text-sm mb-8">
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>1 agent AI · 500 conversații/lună</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Widget chat + 1 site</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Bază cunoștințe nelimitată</li>
                    <li class="flex gap-2 items-start text-muted"><span>—</span>WhatsApp / FB / IG</li>
                    <li class="flex gap-2 items-start text-muted"><span>—</span>Agent vocal</li>
                </ul>
                <a href="{{ url('/register') }}" class="btn-outline w-full justify-center" style="width:100%;">Începe gratuit</a>
            </div>

            <div class="fade-up rounded-3xl p-8 bg-ink text-cream relative" style="transition-delay:.1s;">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold">Recomandat</div>
                <div class="mono text-xs uppercase tracking-wider mb-3" style="color:#F2E59A;">Professional</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-6xl font-medium">79</span>
                    <span style="color:#A8A29E;">lei / lună</span>
                </div>
                <p class="text-sm mb-6 pb-6 border-b" style="color:#D7D3CA; border-color: rgba(255,255,255,.1);">Multi-canal + CRM lead pipeline.</p>
                <ul class="space-y-2.5 text-sm mb-8">
                    <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>3 agenți AI · 2.500 conversații/lună</li>
                    <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>WooCommerce + WhatsApp</li>
                    <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Lead scoring + CRM pipeline</li>
                    <li class="flex gap-2 items-start"><span style="color:#F2E59A;">✓</span>Analiză avansată</li>
                    <li class="flex gap-2 items-start" style="color:#A8A29E;"><span>—</span>Voce AI (addon +49 lei)</li>
                </ul>
                <a href="{{ url('/register') }}" class="btn-primary w-full justify-center" style="background:#F2E59A; color:#1C1917; width:100%;">Alege Professional →</a>
            </div>

            <div class="fade-up rounded-3xl p-8 bg-paper border border-line" style="transition-delay:.2s;">
                <div class="mono text-xs uppercase tracking-wider text-muted mb-3">Business</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-6xl font-medium">199</span>
                    <span class="text-muted">lei / lună</span>
                </div>
                <p class="text-sm text-muted mb-6 pb-6 border-b border-line">Volum mare + toate canalele + voce.</p>
                <ul class="space-y-2.5 text-sm mb-8">
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>10 agenți · 10.000 conversații/lună</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Toate canalele (FB · IG · WA)</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Voce AI disponibilă</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>Suport prioritar</li>
                    <li class="flex gap-2 items-start"><span class="accent-text">✓</span>API + webhooks</li>
                </ul>
                <a href="{{ url('/register') }}" class="btn-outline w-full justify-center" style="width:100%;">Alege Business</a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-24 bg-paper">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ întrebări</div>
            <h2 class="display text-5xl font-medium">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
        </div>
        <div class="space-y-3 fade-up">
            @foreach([
                ['Ce se întâmplă când depășesc limita de mesaje?', 'Se taxează automat la costul suplimentar al planului tău — fără întreruperi de serviciu. Vezi tabelul overage la pagina de prețuri.'],
                ['Pot combina chat + voce?', 'Da. Alegi un plan chat și adaugi un addon de voce (+49 sau +149 lei). Ambele folosesc aceeași bază de cunoștințe.'],
                ['Cum garantați că AI-ul nu inventează?', 'Reguli stricte de prudență, citare sursă obligatorie și confidence scoring. Când nu e sigur, răspunde cinstit „nu am această informație" și trimite către operator.'],
                ['Unde sunt stocate datele?', 'Servere în UE, cu izolare strictă per cont. GDPR compliant by default — DPA gata de semnat, audit trail per conversație, retenție configurabilă.'],
                ['Funcționează cu WordPress și WooCommerce?', 'Avem plugin WordPress și sync WooCommerce nativ — produse, stocuri, prețuri, comenzi. Plus embed pe orice site cu o linie de cod.'],
                ['Oferiți reducere pentru ONG-uri?', 'Da. 30% permanent pentru ONG-uri, școli, universități, muzee. Ne trimiți documentele organizației și activăm reducerea.']
            ] as $f)
                <details class="rounded-2xl bg-cream border border-line overflow-hidden group">
                    <summary class="px-6 py-5 flex items-center justify-between cursor-pointer list-none font-medium hover:bg-sand/50 transition">
                        <span>{{ $f[0] }}</span>
                        <svg class="chev w-4 h-4 text-muted transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-6 pb-5 text-sm text-muted leading-relaxed">{{ $f[1] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- Final CTA --}}
<section class="py-28 relative overflow-hidden">
    <div class="absolute inset-0 opacity-60 pointer-events-none" style="background: radial-gradient(ellipse 60% 50% at 50% 50%, color-mix(in srgb, var(--accent) 20%, transparent) 0%, transparent 60%);"></div>
    <div class="max-w-4xl mx-auto px-6 text-center relative fade-up">
        <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-6">◇ acum e momentul</div>
        <h2 class="display text-5xl md:text-7xl font-medium leading-[1.02] mb-8">
            Să-i dăm voce<br>
            <em class="italic accent-text">afacerii tale.</em>
        </h2>
        <p class="text-xl text-muted mb-10 max-w-xl mx-auto leading-relaxed">Configurezi în 10 minute. Primele rezultate din prima zi. Fără card de credit, fără obligații.</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ url('/register') }}" class="btn-primary text-base" style="padding: 16px 28px;">
                Creează cont gratuit
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="{{ route('new.contact') }}" class="btn-outline text-base" style="padding: 16px 28px;">Programează un demo</a>
        </div>
        <p class="mt-8 text-xs mono text-muted">Sau scrie direct · servus@sambla.ro · 0775 222 333</p>
    </div>
</section>

@endsection

@push('scripts')
<script>
/* Hero rotating scenarios — port V5 warm */
(function() {
    const scenarios = [
        { niche:'medical', label:'🦷 Cabinet stomatologic', footer:'✓ Programare din baza de cunoștințe', badge:'Programare confirmată',
          messages:[
            { user:true,  text:'Bună ziua. Aveți loc liber pentru detartraj săptămâna viitoare?' },
            { user:false, text:'Da, am găsit 3 disponibilități. Marți 22, 10:00 · Dr. Ionescu — 180 lei. Vă convine?' },
            { user:true,  text:'Da, pe Maria Popescu.' },
            { user:false, text:'✓ Rezervat. Vă trimit SMS cu detaliile și o reamintire cu 24h înainte.' },
          ]},
        { niche:'auto', label:'🛒 Magazin online', footer:'✓ Stoc real sincronizat WooCommerce', badge:'Comandă preluată',
          messages:[
            { user:true,  text:'Mai aveți vopsea albă 10L pe stoc?' },
            { user:false, text:'Da! 23 bucăți în stoc · 89 lei · livrare mâine 10-14 🚚' },
            { user:true,  text:'Comanda 2 bucăți.' },
            { user:false, text:'Adăugat în coș. Total 178 lei + transport gratuit peste 150 lei. Finalizezi?' },
          ]},
        { niche:'auto', label:'🔧 Service auto', footer:'✓ Istoric VIN verificat automat', badge:'Programare service',
          messages:[
            { user:true,  text:'Cât costă schimbul de ulei la BMW X3?' },
            { user:false, text:'Ce an? Pentru X3 2020+: schimb ulei + filtru — 420 lei, durează ~1h.' },
            { user:true,  text:'2022. Marți dimineața?' },
            { user:false, text:'Marți 9:00 rezervat ✓ Vă așteptăm. Aveți nevoie să lăsați mașina?' },
          ]},
        { niche:'resto', label:'🏨 Pensiune', footer:'✓ Disponibilitate iCal live', badge:'Rezervare trimisă',
          messages:[
            { user:true,  text:'Aveți cameră dublă pentru acest weekend?' },
            { user:false, text:'Da! Camera Alba liberă 22-23 apr · 280 lei/noapte, mic-dejun inclus.' },
            { user:true,  text:'Perfect, rezerv 2 nopți.' },
            { user:false, text:'✓ 2 nopți × 280 = 560 lei. Vă trimit link de plată pe email?' },
          ]},
        { niche:'imob', label:'🏠 Agenție imobiliară', footer:'✓ Listări filtrate după criterii', badge:'Vizionare programată',
          messages:[
            { user:true,  text:'Caut apartament 2 camere în Cluj, până în 95.000 €.' },
            { user:false, text:'Am 4 proprietăți în buget. Cea mai potrivită: 58 mp, Mărăști, etaj 3 — 89.000 €.' },
            { user:true,  text:'Aș vrea să o văd sâmbătă.' },
            { user:false, text:'Sâmbătă 11:00 cu agentul Andrei ✓ Vă trimit adresa exactă pe WhatsApp.' },
          ]},
        { niche:'beauty', label:'💆 Clinică estetică', footer:'✓ Preț din lista de servicii', badge:'Consultație rezervată',
          messages:[
            { user:true,  text:'Cât costă un tratament cu acid hialuronic?' },
            { user:false, text:'De la 450 lei/ml. Primul pachet include consultație gratuită cu dr. Petrescu.' },
            { user:true,  text:'Când am loc?' },
            { user:false, text:'Joi 17:00 e primul disponibil. Rezerv? Durată consultație ~30 min.' },
          ]},
        { niche:'legal', label:'⚖️ Cabinet avocatură', footer:'✓ Programare în calendar partener', badge:'Consultație rezervată',
          messages:[
            { user:true,  text:'Am nevoie de consultanță pentru un litigiu comercial.' },
            { user:false, text:'Cabinetul acoperă litigii comerciale, inclusiv recuperări creanțe. Doriți o programare?' },
            { user:true,  text:'Da, cât mai repede.' },
            { user:false, text:'Cel mai apropiat slot: miercuri 14:00 cu av. Popescu. 45 min consultație.' },
          ]},
        { niche:'legal', label:'📊 Contabilitate', footer:'✓ Pachet adaptat tipului de firmă', badge:'Ofertă trimisă',
          messages:[
            { user:true,  text:'Am un SRL nou, aveți pachet de contabilitate lunară?' },
            { user:false, text:'Felicitări! Pachetul SRL micro pornește de la 500 lei/lună — include contabilitate + declarații + consultanță.' },
            { user:true,  text:'Ce documente îmi trebuie la început?' },
            { user:false, text:'Certificat înregistrare, act constitutiv și buletinele asociaților. Programăm o întâlnire?' },
          ]},
    ];
    for (let i = scenarios.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [scenarios[i], scenarios[j]] = [scenarios[j], scenarios[i]];
    }

    const card    = document.getElementById('heroChatCard');
    const chatEl  = document.getElementById('heroChat');
    const inner   = document.getElementById('heroChatInner');
    const typing  = document.getElementById('heroTyping');
    const label   = document.getElementById('heroScenarioLabel');
    const footer  = document.getElementById('heroFooterText');
    const dots    = document.getElementById('heroDotsContainer');
    const badge   = document.getElementById('heroBadgeTitle');
    if (!card || !chatEl) return;

    let current = 0, timers = [], gen = 0;
    const t = (fn, d) => { const id = setTimeout(fn, d); timers.push(id); return id; };
    const clearAll = () => { timers.forEach(clearTimeout); timers = []; gen++; typing.classList.add('hidden'); typing.classList.remove('flex'); };

    scenarios.forEach((_, i) => {
        const s = document.createElement('button');
        s.className = 'rounded-full transition-all duration-300';
        s.style.background = '#D7D3CA';
        s.style.width = '6px'; s.style.height = '6px';
        s.setAttribute('aria-label', 'Scenariu ' + (i+1));
        s.addEventListener('click', () => { current = i; play(current); });
        dots.appendChild(s);
    });

    function setDot(i) {
        for (let k = 0; k < dots.children.length; k++) {
            const d = dots.children[k];
            const on = k === i;
            d.style.background = on ? 'var(--accent)' : '#D7D3CA';
            d.style.width = on ? '18px' : '6px';
        }
    }

    function addBubble(msg, onDone) {
        const row = document.createElement('div');
        row.className = msg.user ? 'flex justify-end' : 'flex';
        row.style.opacity = '0'; row.style.transform = 'translateY(6px)';
        row.style.transition = 'opacity .35s ease, transform .35s ease';
        const bubble = document.createElement('div');
        if (msg.user) {
            bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14.5px] leading-relaxed text-white';
            bubble.style.background = 'var(--accent)';
        } else {
            bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14.5px] leading-relaxed text-ink';
            bubble.style.background = '#EFE5D0';
        }
        bubble.textContent = msg.text;
        row.appendChild(bubble);
        inner.appendChild(row);
        requestAnimationFrame(() => {
            row.style.opacity = '1'; row.style.transform = 'translateY(0)';
            chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
        });
        t(onDone, 450);
    }

    function addMessage(msg, onDone) {
        if (!msg.user) {
            typing.classList.remove('hidden'); typing.classList.add('flex');
            chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
            t(() => { typing.classList.add('hidden'); typing.classList.remove('flex'); addBubble(msg, onDone); }, 700 + Math.random() * 400);
        } else {
            addBubble(msg, onDone);
        }
    }

    function play(index) {
        clearAll();
        const myGen = ++gen;
        const sc = scenarios[index];
        card.setAttribute('data-niche', sc.niche);
        label.style.opacity = '0'; footer.style.opacity = '0';
        if (badge) badge.style.opacity = '0';
        t(() => {
            if (myGen !== gen) return;
            label.textContent = sc.label; footer.textContent = sc.footer;
            if (badge) { badge.textContent = sc.badge; badge.style.opacity = '1'; }
            label.style.opacity = '1'; footer.style.opacity = '1';
        }, 220);
        setDot(index);
        inner.innerHTML = '';
        let i = 0;
        const next = () => {
            if (myGen !== gen) return;
            if (i >= sc.messages.length) { t(() => { if (myGen !== gen) return; current = (current + 1) % scenarios.length; play(current); }, 3200); return; }
            const m = sc.messages[i];
            const delay = i === 0 ? 500 : (m.user ? 700 : 200);
            t(() => { if (myGen !== gen) return; addMessage(m, () => { i++; next(); }); }, delay);
        };
        next();
    }
    t(() => play(current), 400);

    let startX = 0, startY = 0;
    chatEl.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
    chatEl.addEventListener('touchend', (e) => {
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            current = (current + (dx < 0 ? 1 : -1) + scenarios.length) % scenarios.length;
            play(current);
        }
    }, { passive: true });
})();

/* Stat countup */
(function () {
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const el = e.target;
            const target = parseInt(el.dataset.countup, 10);
            const suffix = el.querySelector('span')?.outerHTML || '';
            const dur = 1200, start = performance.now();
            const tick = (now) => {
                const p = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - p, 3);
                el.innerHTML = Math.round(target * eased) + suffix;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
            obs.unobserve(el);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-countup]').forEach(el => obs.observe(el));
})();
</script>
@endpush
