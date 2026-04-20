@extends('layouts.new')

@section('title', 'Sambla — Agenți AI care răspund clienților tăi 24/7, în limba română')
@section('meta_description', 'Agenți AI care răspund la telefon, pe site și pe WhatsApp — 24/7, în limba română, din documentele afacerii tale. Programări automate, lead-uri calificate, răspunsuri fără halucinații. 7 zile gratuit.')
@section('og_title', 'Agenții AI care preiau apelurile și scriu clienților în locul tău')
@section('og_description', 'Răspund 24/7, în limba română, din documentele afacerii tale. Fac programări, recomandă produse, preiau lead-uri.')
@section('canonical', url('/new'))

@section('og_image', route('new.og', ['title' => 'Angajatul tău AI care nu iese din tură', 'sub' => 'Răspunde pe chat, telefon și WhatsApp. 24/7, în română.', 'eyebrow' => 'sambla.ro']))

@section('jsonld')
<script type="application/ld+json">
{
    "@context":"https://schema.org",
    "@type":"SoftwareApplication",
    "name":"Sambla",
    "applicationCategory":"BusinessApplication",
    "operatingSystem":"Web (Cloud SaaS)",
    "offers":{"@type":"AggregateOffer","priceCurrency":"RON","lowPrice":"0","url":"https://sambla.ro/new/preturi"},
    "description":"Agenți AI conversaționali — pe site, pe telefon, pe WhatsApp — antrenați pe documentele afacerii tale.",
    "inLanguage":"ro-RO",
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
                <span id="heroRotator" class="italic font-normal accent-text inline-block transition-opacity duration-500 ease-out" style="opacity:1; min-height: 1.1em;">nu iese din tură.</span>
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
                    <div class="px-5 py-3 flex items-center gap-3 border-b border-line bg-paper">
                        <div class="relative">
                            <div class="w-9 h-9 rounded-full accent-bg flex items-center justify-center transition-colors duration-500">
                                <span class="text-white display text-sm font-semibold">S</span>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-paper">
                                <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60"></span>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">Sambla</div>
                            <div class="text-[11px] text-muted flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online · răspunde instant
                            </div>
                        </div>
                    </div>

                    {{-- Bandă industrie prominentă — accent soft bg, text mare,
                         pulse dot. Scenariul curent e clar vizibil. --}}
                    <div class="px-5 py-3 border-b border-line accent-soft-bg transition-colors duration-500 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="relative flex h-2 w-2 shrink-0">
                                <span class="absolute inline-flex h-full w-full rounded-full accent-bg opacity-60 animate-ping"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 accent-bg"></span>
                            </span>
                            <span class="mono text-[10px] uppercase tracking-[0.15em] accent-dark">demo live</span>
                            <span class="text-muted">·</span>
                            <span id="heroScenarioLabel" class="text-sm font-semibold transition-all duration-500 truncate" style="color: var(--accent-dark);">🦷 Cabinet stomatologic</span>
                        </div>
                    </div>

                    {{-- Înălțime fixă: conversațiile mai lungi scrollează în interior
                         (overflow-y-auto), casuța nu se mută. --}}
                    <div id="heroChat" class="px-5 py-4 h-[500px] overflow-y-auto relative no-scrollbar">
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

                    {{-- Strip de rezultat — între conversație și footer-ul cu dots.
                         Se actualizează la fiecare scenariu (heroBadgeTitle). --}}
                    <div class="px-4 py-2 border-t border-line bg-cream flex items-center justify-center gap-2">
                        <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" style="background:#D1FAE5;">
                            <svg class="w-2.5 h-2.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span id="heroBadgeTitle" class="text-xs font-semibold transition-all duration-300">Programare confirmată</span>
                        <span class="text-[10px] mono" style="color: var(--muted);">· automat, fără operator</span>
                    </div>

                    <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
                            <span id="heroFooterText" class="text-xs text-muted font-medium transition-opacity duration-300 truncate">Răspuns din baza de cunoștințe</span>
                        </div>
                        <div id="heroDotsContainer" class="flex gap-1.5 shrink-0"></div>
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

{{-- 3 mari diferențiatori — tl;dr de ce Sambla e diferit --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ de ce sambla e diferit</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Trei lucruri pe care <em class="italic accent-text">doar noi</em> le facem.</h2>
            <p class="text-lg text-muted leading-relaxed">Tot ce e important despre Sambla, în trei fraze. Restul paginii le probează concret.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">

            {{-- Card 1: Învață din datele tale --}}
            <div class="fade-up rounded-3xl p-8 relative overflow-hidden" style="background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);">
                <div class="absolute -top-6 -right-6 text-7xl opacity-20">📚</div>
                <div class="relative">
                    <div class="mono text-[10px] uppercase tracking-wider mb-3" style="color: #991B1B;">◇ diferențiator 01</div>
                    <h3 class="display text-2xl font-semibold mb-3 leading-tight">Învață din <em class="italic">datele tale</em></h3>
                    <p class="leading-relaxed mb-4" style="color: #7F1D1D;">Nu e un agent generic cu „prompt bun". Urci documentele, produsele și politicile tale — agentul răspunde <strong>doar</strong> din ele. Fiecare răspuns poate cita sursa.</p>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">PDF + DOCX</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">sync site</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">catalog live</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Se auto-îmbunătățește --}}
            <div class="fade-up rounded-3xl p-8 relative overflow-hidden" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); transition-delay: .1s;">
                <div class="absolute -top-6 -right-6 text-7xl opacity-20">🔄</div>
                <div class="relative">
                    <div class="mono text-[10px] uppercase tracking-wider mb-3" style="color: #92400E;">◇ diferențiator 02</div>
                    <h3 class="display text-2xl font-semibold mb-3 leading-tight">Se <em class="italic">auto-îmbunătățește</em></h3>
                    <p class="leading-relaxed mb-4" style="color: #78350F;">Detectează singur întrebările la care nu a răspuns bine și generează un draft de răspuns. Tu îl aprobi în 30 de secunde. În câteva săptămâni, acoperă 90%+ din întrebările reale.</p>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">knowledge gaps</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">draft AI</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">tu aprobi</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Voce + Chat, același creier --}}
            <div class="fade-up rounded-3xl p-8 relative overflow-hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); transition-delay: .2s;">
                <div class="absolute -top-6 -right-6 text-7xl opacity-20">🧠</div>
                <div class="relative">
                    <div class="mono text-[10px] uppercase tracking-wider mb-3" style="color: #1E40AF;">◇ diferențiator 03</div>
                    <h3 class="display text-2xl font-semibold mb-3 leading-tight">Voce + Chat, <em class="italic">același creier</em></h3>
                    <p class="leading-relaxed mb-4" style="color: #1E3A8A;">Clientul scrie pe site, continuă pe WhatsApp, sună după două ore. Agentul ține minte contextul — nu începi de la zero pe fiecare canal. Un dashboard, un istoric per client.</p>
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">5 canale</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">context păstrat</span>
                        <span class="px-2.5 py-1 rounded-full bg-white/60 font-medium">istoric unificat</span>
                    </div>
                </div>
            </div>

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
                    <li class="flex gap-2"><span class="text-coral">✦</span> Numere românești dedicate, voce feminină sau masculină</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Răspunde instant, clientul poate întrerupe în orice moment</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Transcriere live și detecție sentiment pentru fiecare apel</li>
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
                    <li class="flex gap-2"><span class="text-coral">✦</span> Carduri produse cu preț și stoc live</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Culori și logo pe brand-ul tău, ecran întunecat pentru vizitatorii seara</li>
                    <li class="flex gap-2"><span class="text-coral">✦</span> Izolat de restul paginii — nu poate strica nimic pe site</li>
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
                ['01','Ascultă ce vrea clientul','Înțelege dacă cumpără, întreabă, reclamă sau compară. Adaptează răspunsul, nu aplică un scenariu fix.','🎯'],
                ['02','Caută în documentele tale','Răspunde doar din ce ai încărcat tu — fișiere, pagini, cataloage. Fără ghiceli, fără umplutură.','🔎'],
                ['03','Verifică înainte să spună','Dacă nu e sigur pe răspuns, îți cere clarificare sau transferă la un om. Nu improvizează prețuri sau termene.','🛡️'],
                ['04','Răspunde în tonul tău','Empatie la frustrare. Recomandare la interes. Scurt dacă vrea rapid. Oficial dacă așa vorbește brandul tău.','💬'],
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
                <div class="mt-2 text-sm" style="color:#D7D3CA;">timp până la răspuns</div>
                <div class="mono text-[10px] mt-1" style="color:#A8A29E;">cât îi ia omului să citească</div>
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

{{-- DEMO LIVE — real bot, sugestii în stânga, chat în dreapta --}}
<section id="demo" class="py-24">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ demo live</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">
                Vorbește cu <em class="italic accent-text">Sambla AI</em>.
            </h2>
            <p class="text-lg text-muted leading-relaxed">Agent real, conectat live la platformă. Întreabă orice despre Sambla sau alege o sugestie de mai jos.</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-start">
            {{-- Left: sugestii --}}
            <div class="space-y-3 fade-up">
                <button type="button" onclick="askDemo('Ce funcționalități are Sambla?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-paper border border-line hover:border-ink hover:shadow-md transition-all duration-300">
                    <span class="text-2xl shrink-0">💡</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-ink">Ce funcționalități are?</p>
                        <p class="text-xs text-muted mt-0.5">Descoperă tot ce poate face platforma</p>
                    </div>
                    <svg class="w-4 h-4 text-muted shrink-0 group-hover:accent-text group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>

                <button type="button" onclick="askDemo('Cât costă platforma?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-paper border border-line hover:border-ink hover:shadow-md transition-all duration-300">
                    <span class="text-2xl shrink-0">💰</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-ink">Cât costă?</p>
                        <p class="text-xs text-muted mt-0.5">Planuri și prețuri transparente</p>
                    </div>
                    <svg class="w-4 h-4 text-muted shrink-0 group-hover:accent-text group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>

                <button type="button" onclick="askDemo('Cum se integrează cu magazinul meu?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-paper border border-line hover:border-ink hover:shadow-md transition-all duration-300">
                    <span class="text-2xl shrink-0">🔗</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-ink">Cum se integrează?</p>
                        <p class="text-xs text-muted mt-0.5">WooCommerce, WordPress, Google Calendar</p>
                    </div>
                    <svg class="w-4 h-4 text-muted shrink-0 group-hover:accent-text group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>

                <button type="button" onclick="askDemo('Funcționează și pe telefon?')" class="w-full text-left group flex items-center gap-4 px-5 py-4 rounded-2xl bg-paper border border-line hover:border-ink hover:shadow-md transition-all duration-300">
                    <span class="text-2xl shrink-0">📞</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-ink">Merge și pe telefon?</p>
                        <p class="text-xs text-muted mt-0.5">Voce AI naturală în limba română</p>
                    </div>
                    <svg class="w-4 h-4 text-muted shrink-0 group-hover:accent-text group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>
            </div>

            {{-- Right: live chat widget --}}
            <div class="fade-up" style="transition-delay:.1s;">
                <div class="relative">
                    <div class="absolute -inset-4 accent-soft-bg rounded-[28px] blur-2xl opacity-60"></div>
                    <div class="relative rounded-[20px] overflow-hidden bg-white" style="box-shadow: 0 20px 60px rgba(0,0,0,0.1), 0 4px 20px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.04);">
                        <div class="accent-bg px-6 py-4 relative" style="background: linear-gradient(135deg, var(--accent), var(--accent-dark));">
                            <div class="relative flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full ring-2 ring-white/20 flex items-center justify-center bg-white/15 shrink-0">
                                    <span class="display text-white text-base font-semibold">S</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-bold text-[15px]">Sambla AI</p>
                                    <p class="text-white/70 text-xs flex items-center gap-1.5">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                                        </span>
                                        Online — răspunde live
                                    </p>
                                </div>
                                <span class="ml-auto text-[10px] font-bold text-white/80 bg-white/15 px-3 py-1 rounded-full">DEMO LIVE</span>
                            </div>
                        </div>

                        <div id="sbDemoMessages" class="h-[380px] overflow-y-auto px-5 py-5 bg-white space-y-3.5">
                            <div class="flex gap-2.5">
                                <div class="bg-sand rounded-2xl rounded-tl-md px-5 py-3 max-w-[85%]">
                                    <p class="text-sm text-ink leading-relaxed">Bună! 👋 Sunt Sambla AI. Întreabă-mă orice despre platformă sau alege o sugestie din stânga.</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-line px-5 py-4 bg-paper">
                            <form id="sbDemoForm" class="flex gap-2">
                                <input type="text" id="sbDemoInput" placeholder="Scrie un mesaj..." class="flex-1 rounded-full border border-line bg-white px-5 py-3 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:border-transparent transition-all" style="--tw-ring-color: var(--accent);" autocomplete="off"/>
                                <button type="submit" aria-label="Trimite mesaj" class="w-11 h-11 flex items-center justify-center accent-bg text-white rounded-full hover:opacity-90 transition-all shadow-md active:scale-95">
                                    <svg class="w-[18px] h-[18px] -rotate-45" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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
                        <span class="text-4xl">{{ $nicheEmojiMap[$n->slug] ?? '✦' }}</span>
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
                            <span class="text-4xl">{{ $ind[0] }}</span>
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

{{-- Se îmbunătățește singur — non-technical "learns from gaps" story --}}
<section class="py-24 bg-paper border-t border-line">
    <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-6 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ se îmbunătățește singur</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-6">
                Pe măsură ce vorbește cu clienții,<br>
                <span class="italic accent-text">devine mai bun.</span>
            </h2>
            <p class="text-lg text-muted leading-relaxed mb-5">Agentul observă întrebările la care n-a răspuns sigur — le grupează pe subiecte și îți propune în dashboard variante de răspuns pe care <span class="text-ink font-medium">tu le aprobi într-un click</span>.</p>
            <p class="text-lg text-muted leading-relaxed mb-8">Nu învață singur fără supervizare — asta ar fi periculos. Învață asistat: AI face munca de identificare și redactare, tu validezi în 30 de secunde. În câteva săptămâni, acoperă 90–95% din întrebările reale.</p>
            <div class="flex flex-wrap gap-3">
                <div class="chip chip-soft text-sm"><span class="accent-text font-semibold">→</span> Detectează întrebări fără răspuns bun</div>
                <div class="chip chip-soft text-sm"><span class="accent-text font-semibold">→</span> Redactează draft de FAQ</div>
                <div class="chip chip-soft text-sm"><span class="accent-text font-semibold">→</span> Tu aprobi, AI publică</div>
            </div>
        </div>

        <div class="lg:col-span-6 fade-up" style="transition-delay:.1s;">
            <div class="rounded-3xl bg-cream border border-line p-6 md:p-8 shadow-sm">
                <div class="mono text-[10px] uppercase tracking-wider text-muted mb-4">◇ sugestii în dashboard · săptămâna 3</div>

                <div class="space-y-3">
                    <div class="rounded-2xl bg-paper border border-line p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="text-sm font-semibold">Clienții întreabă des despre <em class="italic accent-text">parcare</em></div>
                            <span class="chip accent-soft-bg mono text-[10px] accent-text shrink-0">24 întrebări</span>
                        </div>
                        <div class="text-xs text-muted mb-3">Sugestie draft răspuns:</div>
                        <div class="text-sm bg-sand rounded-xl px-3 py-2 mb-3 italic">„Avem parcare gratuită pentru clienți în curtea interioară. Intrarea se face din strada Principală 23."</div>
                        <div class="flex gap-2">
                            <button type="button" class="chip bg-emerald-50 text-emerald-700 mono text-[10px] font-semibold">✓ aprobă</button>
                            <button type="button" class="chip bg-white border border-line mono text-[10px]">editează</button>
                            <button type="button" class="chip bg-white border border-line mono text-[10px]">respinge</button>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-paper border border-line p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="text-sm font-semibold">Întrebări despre <em class="italic accent-text">plată în rate</em></div>
                            <span class="chip accent-soft-bg mono text-[10px] accent-text shrink-0">17 întrebări</span>
                        </div>
                        <div class="text-xs text-muted mb-3">Sugestie draft răspuns:</div>
                        <div class="text-sm bg-sand rounded-xl px-3 py-2 italic">„Acceptăm plata în 3 rate fără dobândă pentru comenzi peste 500 lei, prin card."</div>
                    </div>

                    <div class="rounded-2xl bg-paper border border-line p-4 opacity-70">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-sm font-semibold">Alte 5 sugestii în așteptare</div>
                            <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </div>
                </div>
            </div>
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

{{-- Voci multiple — mici testimoniale + rezultate măsurabile --}}
<section class="py-24 bg-paper border-y border-line">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] text-muted mb-4">◇ ce spun cei care-l folosesc</div>
            <h2 class="display text-5xl md:text-6xl font-medium leading-[1.05] mb-5">Rezultate, <em class="italic accent-text">nu promisiuni.</em></h2>
            <p class="text-lg text-muted">Feedback real de la afaceri românești, grupat pe domeniu. Fără fotografii cu stoc, fără nume inventate — doar rezultate măsurabile.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-6xl mx-auto">
            @foreach([
                [
                    'quote' => 'Am redus timpul de răspuns cu peste 80%. Clienții primesc confirmare imediat, inclusiv la 3 dimineața. Echipa vine dimineața la muncă cu programările deja făcute.',
                    'role'  => 'Fondator · service auto',
                    'initials' => 'DP',
                    'metric' => '−80% timp răspuns',
                ],
                [
                    'quote' => 'ROI vizibil din prima lună. Programările neconfirmate au scăzut cu jumătate. Echipa de suport se ocupă acum doar de cazurile complexe.',
                    'role'  => 'Director operațiuni · clinică medicală',
                    'initials' => 'AM',
                    'metric' => '−50% no-shows',
                ],
                [
                    'quote' => 'Calitatea vocii e remarcabilă. Câțiva clienți ne-au întrebat dacă am angajat oameni noi — nu realizau că vorbesc cu un agent AI. Pronunția diacriticelor e corectă.',
                    'role'  => 'Manager · pensiune HORECA',
                    'initials' => 'IR',
                    'metric' => '24/7 acoperire',
                ],
                [
                    'quote' => 'Ne-a eliberat aproape 40 de ore pe săptămână din suport manual. Programări, FAQ, tracking comenzi — toate automate. Putem lucra la ce contează.',
                    'role'  => 'Fondator · platformă educație',
                    'initials' => 'MC',
                    'metric' => '+40h/săpt.',
                ],
                [
                    'quote' => 'Funcția care îmi propune răspunsuri noi în fiecare săptămână e genială. Vedem exact ce întreabă clienții și acoperim lacunele rapid.',
                    'role'  => 'E-commerce manager · retail',
                    'initials' => 'GC',
                    'metric' => '90% întrebări acoperite',
                ],
                [
                    'quote' => 'Detectarea frustrării și transferul automat către operator a salvat câteva vânzări mari. Când clientul se enervează, cineva de la noi preia imediat.',
                    'role'  => 'VP vânzări · logistică',
                    'initials' => 'RS',
                    'metric' => '+12% conversie',
                ],
            ] as $t)
                <div class="fade-up rounded-3xl p-6 bg-cream border border-line flex flex-col">
                    <div class="flex gap-0.5 mb-4">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-3.5 h-3.5" style="color: #F4C430;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-[15px] leading-relaxed mb-5 flex-1">„{{ $t['quote'] }}"</p>
                    <div class="flex items-center justify-between gap-3 pt-4 border-t border-line">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-full accent-bg flex items-center justify-center shrink-0">
                                <span class="text-white text-[11px] font-bold">{{ $t['initials'] }}</span>
                            </div>
                            <p class="text-[11px] text-muted leading-tight">{{ $t['role'] }}</p>
                        </div>
                        <span class="chip accent-soft-bg mono text-[10px] accent-text font-semibold shrink-0">{{ $t['metric'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-center mt-8 text-muted mono">Menționările nominale, logo-urile și cifrele exacte sunt disponibile în conversații private, după semnarea NDA.</p>
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
            { user:false, text:'Adăugat în coș. Total 178 lei + transport gratuit peste 150 lei.' },
            { user:false, text:'✓ Comandă plasată. Primești AWB pe email mâine dimineață.' },
          ]},
        { niche:'auto', label:'🔧 Service auto', footer:'✓ Istoric VIN verificat automat', badge:'Programare service',
          messages:[
            { user:true,  text:'Cât costă schimbul de ulei la BMW X3?' },
            { user:false, text:'Ce an? Pentru X3 2020+: schimb ulei + filtru — 420 lei, durează ~1h.' },
            { user:true,  text:'2022. Marți dimineața?' },
            { user:false, text:'Marți 9:00 rezervat ✓ Vă aștept în service la prima oră. Trimit reminder cu o zi înainte.' },
          ]},
        { niche:'resto', label:'🏨 Pensiune', footer:'✓ Disponibilitate iCal live', badge:'Rezervare trimisă',
          messages:[
            { user:true,  text:'Aveți cameră dublă pentru acest weekend?' },
            { user:false, text:'Da! Camera Alba liberă 22-23 apr · 280 lei/noapte, mic-dejun inclus.' },
            { user:true,  text:'Perfect, rezerv 2 nopți.' },
            { user:false, text:'✓ Rezervare confirmată — 2 nopți × 280 = 560 lei. Link de plată trimis pe email, valabil 24h.' },
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
            { user:false, text:'✓ Rezervat pentru joi 17:00 — consultație 30 min cu dr. Petrescu. Primești reamintire pe SMS cu o zi înainte.' },
          ]},
        { niche:'legal', label:'⚖️ Cabinet avocatură', footer:'✓ Programare în calendar partener', badge:'Consultație rezervată',
          messages:[
            { user:true,  text:'Am nevoie de consultanță pentru un litigiu comercial.' },
            { user:false, text:'Cabinetul acoperă litigii comerciale, inclusiv recuperări creanțe. Primul slot liber: miercuri 14:00, 45 min cu av. Popescu.' },
            { user:true,  text:'Da, cât mai repede.' },
            { user:false, text:'✓ Programat pentru miercuri 14:00. Confirmarea a plecat pe email, cu detalii despre documente utile.' },
          ]},
        { niche:'legal', label:'📊 Contabilitate', footer:'✓ Pachet adaptat tipului de firmă', badge:'Ofertă trimisă',
          messages:[
            { user:true,  text:'Am un SRL nou, aveți pachet de contabilitate lunară?' },
            { user:false, text:'Felicitări! Pachetul SRL micro pornește de la 500 lei/lună — include contabilitate + declarații + consultanță.' },
            { user:true,  text:'Ce documente îmi trebuie la început?' },
            { user:false, text:'Certificat înregistrare, act constitutiv și buletinele asociaților.' },
            { user:false, text:'✓ Am programat o întâlnire pentru joi la 11:00. Îți trimit oferta detaliată pe email.' },
          ]},
        { niche:'auto', label:'💻 Magazin electronice', footer:'✓ Produse cu preț și reducere live', badge:'Produs recomandat',
          messages:[
            { user:true,  text:'Caut un laptop bun până în 4000 lei pentru lucru de acasă.' },
            { user:false, text:'Am 3 opțiuni în buget. Cea mai echilibrată — procesor de ultimă generație, SSD rapid, autonomie mare:' },
            { user:false, product:{ emoji:'💻', name:'Lenovo ThinkPad E15 · 16GB RAM · 512GB SSD', meta:'Stoc 8 buc · livrare mâine', price:'3.749 lei', old:'4.299 lei', discount:'−13%' } },
            { user:true,  text:'Pot vedea recenzii?' },
            { user:false, text:'4,7★ din 284 recenzii reale.' },
            { user:false, text:'✓ Ți-am trimis pe email link-ul cu specificațiile complete și 12 poze de la clienți.' },
          ]},
        { niche:'beauty', label:'🧴 Drugstore cosmetice', footer:'✓ Stoc sincronizat WooCommerce', badge:'În coș',
          messages:[
            { user:true,  text:'Am ten uscat sensibil. Ce cremă recomanzi pentru iarnă?' },
            { user:false, text:'Pentru ten uscat sensibil, cea mai căutată din catalog e:' },
            { user:false, product:{ emoji:'🧴', name:'CeraVe Moisturising Cream · 340g', meta:'Cu ceramide + acid hialuronic', price:'89 lei', old:'109 lei', discount:'−18%' } },
            { user:true,  text:'Adaugă în coș.' },
            { user:false, text:'✓ Adăugat. Total: 89 lei · livrare gratuită peste 150 lei. Notificare cu AWB-ul în 2 ore.' },
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

        if (msg.product) {
            // Inline product card (magazin online / cosmetice) — emoji art + nume + preț + reducere
            const card = document.createElement('div');
            card.className = 'max-w-[85%] rounded-2xl rounded-bl-sm overflow-hidden border border-line';
            card.style.background = '#FFF';
            const p = msg.product;
            card.innerHTML =
                '<div class="flex gap-3 p-3">' +
                  '<div class="w-16 h-16 rounded-xl flex items-center justify-center text-3xl shrink-0" style="background:#F5F1E8;">' + (p.emoji || '🛍️') + '</div>' +
                  '<div class="flex-1 min-w-0">' +
                    '<div class="text-[13px] font-semibold leading-tight mb-0.5 truncate">' + p.name + '</div>' +
                    '<div class="text-[11px] text-muted mb-1 truncate">' + (p.meta || '') + '</div>' +
                    '<div class="flex items-baseline gap-2">' +
                      '<span class="text-sm font-bold accent-text">' + p.price + '</span>' +
                      (p.old ? '<span class="text-[11px] line-through text-muted">' + p.old + '</span>' : '') +
                      (p.discount ? '<span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:#D1FAE5; color:#047857;">' + p.discount + '</span>' : '') +
                    '</div>' +
                  '</div>' +
                '</div>';
            row.appendChild(card);
            inner.appendChild(row);
            requestAnimationFrame(() => {
                row.style.opacity = '1'; row.style.transform = 'translateY(0)';
                chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
            });
            t(onDone, 320);
            return;
        }

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
        t(onDone, 320);
    }

    function addMessage(msg, onDone) {
        if (!msg.user) {
            typing.classList.remove('hidden'); typing.classList.add('flex');
            chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
            t(() => { typing.classList.add('hidden'); typing.classList.remove('flex'); addBubble(msg, onDone); }, 450 + Math.random() * 250);
        } else {
            addBubble(msg, onDone);
        }
    }

    // Pause auto-advance când user-ul e cu mouse-ul peste card sau touch-activ
    // pe mobile — cititul trebuie să-i aparțină, nu să fie întrerupt.
    let hovered = false;
    card.addEventListener('mouseenter', () => { hovered = true; });
    card.addEventListener('mouseleave', () => { hovered = false; });
    card.addEventListener('touchstart', () => { hovered = true; }, { passive: true });
    // pe touch nu avem mouseleave; resetăm dupa 4s de la ultimul tap
    let touchResetTimer = null;
    card.addEventListener('touchend', () => {
        clearTimeout(touchResetTimer);
        touchResetTimer = setTimeout(() => { hovered = false; }, 4000);
    }, { passive: true });

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
        }, 180);
        setDot(index);
        inner.innerHTML = '';
        let i = 0;
        const advanceWhenIdle = () => {
            if (myGen !== gen) return;
            if (hovered) {
                // Așteaptă până user-ul pleacă — poll la 500ms
                t(advanceWhenIdle, 500);
                return;
            }
            current = (current + 1) % scenarios.length;
            play(current);
        };
        const next = () => {
            if (myGen !== gen) return;
            if (i >= sc.messages.length) { t(advanceWhenIdle, 2400); return; }
            const m = sc.messages[i];
            const delay = i === 0 ? 350 : (m.user ? 500 : 150);
            t(() => { if (myGen !== gen) return; addMessage(m, () => { i++; next(); }); }, delay);
        };
        next();
    }
    t(() => play(current), 300);

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

/* Demo Live — real bot call on channel 2 (same as live site) */
(function () {
    var msgs = document.getElementById('sbDemoMessages');
    var form = document.getElementById('sbDemoForm');
    var inp  = document.getElementById('sbDemoInput');
    if (!msgs || !form || !inp) return;
    var channelId = 2;

    window.askDemo = function (q) { inp.value = q; form.dispatchEvent(new Event('submit', { cancelable: true })); };

    function escapeHtml(t) { var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }

    function appendBubble(text, isUser) {
        var row = document.createElement('div');
        row.className = isUser ? 'flex gap-2.5 justify-end' : 'flex gap-2.5';
        row.style.opacity = '0'; row.style.transform = 'translateY(6px)';
        row.style.transition = 'opacity .3s ease, transform .3s ease';
        if (isUser) {
            row.innerHTML = '<div class="rounded-2xl rounded-tr-md px-5 py-3 max-w-[85%] shadow-sm accent-bg"><p class="text-sm text-white leading-relaxed">' + escapeHtml(text) + '</p></div>';
        } else {
            row.innerHTML = '<div class="bg-sand rounded-2xl rounded-tl-md px-5 py-3 max-w-[85%]"><p class="text-sm text-ink leading-relaxed">' + text + '</p></div>';
        }
        msgs.appendChild(row);
        requestAnimationFrame(function(){ row.style.opacity = '1'; row.style.transform = 'translateY(0)'; });
        msgs.scrollTop = msgs.scrollHeight;
    }

    function showTyping() {
        var t = document.createElement('div');
        t.id = 'sbDemoTyping'; t.className = 'flex gap-2.5';
        t.innerHTML = '<div class="bg-sand rounded-2xl rounded-tl-md px-5 py-3"><div class="flex gap-1.5 h-5 items-center"><span class="w-2 h-2 rounded-full animate-bounce" style="background:#A8A29E;animation-delay:0ms"></span><span class="w-2 h-2 rounded-full animate-bounce" style="background:#A8A29E;animation-delay:150ms"></span><span class="w-2 h-2 rounded-full animate-bounce" style="background:#A8A29E;animation-delay:300ms"></span></div></div>';
        msgs.appendChild(t); msgs.scrollTop = msgs.scrollHeight;
    }

    function callBot(text, cb) {
        fetch('/api/v1/chatbot/' + channelId + '/message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ message: text })
        }).then(function(r){ return r.json(); })
          .then(function(d){ cb(d.reply || d.response || d.message || 'Mulțumesc pentru mesaj!'); })
          .catch(function(){ cb('Momentan sunt ocupat. Încearcă din nou peste câteva secunde.'); });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (inp.value || '').trim();
        if (!text) return;
        appendBubble(text, true);
        inp.value = '';
        inp.focus();
        showTyping();
        callBot(text, function (reply) {
            var t = document.getElementById('sbDemoTyping');
            if (t) t.remove();
            appendBubble(reply, false);
        });
    });
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

/* ------------------------------------------------------------------ */
/* Hero rotating headline — 10 phrases cycled with fade transition    */
/* ------------------------------------------------------------------ */
(function () {
    const el = document.getElementById('heroRotator');
    if (!el) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const phrases = [
        'nu iese din tură.',
        'știe totul despre afacerea ta.',
        'răspunde clienților 24/7.',
        'vorbește natural în română.',
        'nu uită niciodată un detaliu.',
        'vinde în locul tău, non-stop.',
        'învață singur din conversații.',
        'îți crește vânzările în somn.',
        'știe fiecare produs și preț.',
        'preia apeluri ca un profesionist.',
        'transformă vizitatori în clienți.',
    ];
    let idx = 0;

    function rotate() {
        el.style.opacity = '0';
        setTimeout(() => {
            idx = (idx + 1) % phrases.length;
            el.textContent = phrases[idx];
            el.style.opacity = '1';
        }, 500);
    }

    setInterval(rotate, 3200);
})();
</script>
@endpush
