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

{{-- ============================================================
     HERO — titlu puternic, chat mockup animat, trust row.
     Nicio menționare de furnizor. „Agenți AI", nu „chatbot".
============================================================ --}}
<section class="hero-glow relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 pt-16 pb-20 lg:pt-20 lg:pb-28 grid lg:grid-cols-12 gap-12 items-start relative">
        <div class="lg:col-span-6 fade-up">
            <div class="chip chip-outline mb-7">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping" style="background: var(--accent);"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2" style="background: var(--accent);"></span>
                </span>
                <span class="mono text-[11px] uppercase tracking-wider">online · răspunde acum</span>
            </div>

            <h1 class="display h-display-xl mb-7">
                Angajatul tău AI care<br>
                <span class="italic accent-text" style="font-weight: 400;">nu iese din tură.</span>
            </h1>

            <p class="text-lg md:text-xl leading-relaxed mb-9 max-w-xl" style="color: var(--muted);">
                Răspunde la telefon, pe site și pe WhatsApp — la 3 dimineața, duminica, în concediu. Din documentele, produsele și politicile afacerii tale.
                <span class="font-medium" style="color: var(--ink);">Nu inventează. Nu ghicește. Știe.</span>
            </p>

            <div class="flex flex-wrap gap-3 mb-9">
                <a href="{{ url('/register') }}" class="btn btn-primary">
                    Începe gratuit 7 zile
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="#demo" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.27l9.34-5.89a1.5 1.5 0 000-2.54L6.3 2.84z"/></svg>
                    Ascultă o conversație
                </a>
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm" style="color: var(--muted);">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Fără card la înregistrare
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Setup în 10 minute
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    GDPR nativ
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" style="color: var(--emerald);"><path d="M16.71 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.42 0l-4-4a1 1 0 111.42-1.42L8 12.59l7.29-7.3a1 1 0 011.42 0z"/></svg>
                    Construit în România
                </span>
            </div>
        </div>

        {{-- Chat mockup — static, lightweight, niche-colored --}}
        <div class="lg:col-span-6 fade-up" style="transition-delay: .15s">
            <div class="relative">
                <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%);"></div>
                <div class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid var(--line); box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-line accent-soft-bg">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full accent-bg flex items-center justify-center">
                                <span class="display text-base font-semibold" style="color:#fff;">S</span>
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-paper" style="background: var(--emerald);"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">Agentul AI</div>
                            <div class="text-xs flex items-center gap-1.5" style="color: var(--muted);">
                                <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                                Răspunde instant
                            </div>
                        </div>
                        <span class="chip accent-soft-bg mono text-[10px]" style="color: var(--accent-dark);">🦷 Cabinet stomatologic</span>
                    </div>

                    <div class="px-5 py-4 space-y-3" style="min-height: 380px;">
                        <div class="flex">
                            <div class="max-w-[82%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-sm bg-white border border-line">
                                Bună seara! Mă doare o măsea, puteți să îmi dați o programare mâine?
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="max-w-[82%] px-4 py-2.5 rounded-2xl rounded-br-sm text-sm accent-bg" style="color:#fff;">
                                Bună! Îmi pare rău că vă doare. Mâine avem două sloturi disponibile cu Dr. Popescu: <strong>10:30</strong> sau <strong>16:00</strong>. Care vă convine?
                            </div>
                        </div>
                        <div class="flex">
                            <div class="max-w-[82%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-sm bg-white border border-line">
                                16:00, vă rog. Și cât costă o consultație?
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="max-w-[82%] px-4 py-2.5 rounded-2xl rounded-br-sm text-sm accent-bg" style="color:#fff;">
                                Consultația cu radiografie este <strong>150 lei</strong>. V-am rezervat mâine la 16:00 cu Dr. Popescu. Vă trimit acum confirmarea pe SMS. 🦷
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <div class="max-w-[82%] px-3 py-2 rounded-2xl rounded-br-sm text-xs" style="background: var(--accent-soft); color: var(--accent-dark);">
                                <span class="mono text-[10px] uppercase tracking-wider">✓ Programare confirmată · sincronizată în calendar</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 accent-text shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.86-9.81a.75.75 0 00-1.21-.88l-3.48 4.79-1.88-1.88a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.14-.09l4-5.5z"/></svg>
                            <span class="text-xs font-medium" style="color: var(--muted);">Răspuns din baza de cunoștințe a cabinetului</span>
                        </div>
                    </div>
                </div>

                <div class="absolute -left-4 -bottom-4 bg-white rounded-2xl shadow-xl p-4 pr-5 flex items-center gap-3 border border-line max-w-[260px] float" style="animation-delay:.5s;">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#D1FAE5; color:#047857;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold leading-tight">Programare confirmată</div>
                        <div class="text-xs" style="color: var(--muted);">automat · fără operator</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     TRUST STRIP — generic, no fake client names
============================================================ --}}
<section class="border-y border-line bg-paper">
    <div class="max-w-7xl mx-auto px-6 py-10">
        <p class="text-center mono text-[11px] uppercase tracking-[0.2em] mb-7" style="color: var(--muted);">Afaceri din România care folosesc agenți AI</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
            <div class="text-center">
                <div class="display text-3xl md:text-4xl font-semibold accent-text">24/7</div>
                <div class="text-xs mt-1" style="color: var(--muted);">răspuns instant<br>zi & noapte</div>
            </div>
            <div class="text-center">
                <div class="display text-3xl md:text-4xl font-semibold accent-text">&lt;2s</div>
                <div class="text-xs mt-1" style="color: var(--muted);">timp mediu<br>de răspuns</div>
            </div>
            <div class="text-center">
                <div class="display text-3xl md:text-4xl font-semibold accent-text">RO</div>
                <div class="text-xs mt-1" style="color: var(--muted);">limba română<br>nativă</div>
            </div>
            <div class="text-center">
                <div class="display text-3xl md:text-4xl font-semibold accent-text">GDPR</div>
                <div class="text-xs mt-1" style="color: var(--muted);">conform<br>by default</div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     CE FACE AGENTUL — 3 comportamente cheie, scurt
============================================================ --}}
<section id="demo" class="py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ ce face agentul tău AI</div>
            <h2 class="display h-display-l mb-5">
                Răspunde. Programează.<br>
                <span class="italic accent-text">Transformă în clienți.</span>
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--muted);">Fiecare conversație se termină cu o acțiune concretă — o programare, un lead captat, o vânzare, un transfer către echipă când e nevoie.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div class="niche-card rounded-3xl p-8 bg-paper border border-line fade-up">
                <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h5m-5 8l-4-4h3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
                </div>
                <h3 class="display text-xl font-semibold mb-2">Răspunde întrebările repetitive</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">„Cât costă?", „Aveți loc mâine?", „Lucrați sâmbătă?". Din documentele, politicile și prețurile tale reale — nu inventează.</p>
            </div>

            <div class="niche-card rounded-3xl p-8 bg-ink fade-up" style="transition-delay: .1s;">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,.08);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color: var(--sun);"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="display text-xl font-semibold mb-2" style="color: var(--cream);">Ia programări în calendarul tău</h3>
                <p class="text-sm leading-relaxed" style="color: #D7D3CA;">Vede slot-urile libere în timp real. Rezervă pe loc. Confirmă pe SMS sau e-mail. Sincronizare directă cu Google Calendar.</p>
            </div>

            <div class="niche-card rounded-3xl p-8 bg-paper border border-line fade-up" style="transition-delay: .2s;">
                <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <h3 class="display text-xl font-semibold mb-2">Captează lead-urile, nu le scapă</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">Nume, telefon, context — extrase automat din conversație, cu scoring de intenție. Toate într-un pipeline pe care îl vezi în dashboard.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     CELE 3 CANALE — telefon, chat pe site, mesagerie
============================================================ --}}
<section class="py-20 md:py-28 bg-paper">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ un singur creier, trei canale</div>
            <h2 class="display h-display-l mb-5">
                Telefon. Site. Mesagerie.<br>
                <span class="italic">Același răspuns expert.</span>
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--muted);">Clientul sună sau scrie — primește aceeași informație, în același ton. O singură bază de cunoștințe pentru toate canalele. Un singur dashboard.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            {{-- Telefon --}}
            <div class="niche-card rounded-3xl p-8 bg-cream border border-line fade-up">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
                    </div>
                    <span class="chip mono text-[10px]" style="background:#D1FAE5; color:#047857;">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                        live
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3">Telefon</h3>
                <p class="leading-relaxed mb-5" style="color: var(--muted);">Agentul vocal răspunde la numărul tău. Vorbește natural în română, face programări, escaladează când e cazul. Voce naturală, barge-in real-time.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><span class="accent-text">✦</span> Numere românești dedicate</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Transcriere automată + analiză ton</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Voce clonată AI premium (opțional)</li>
                </ul>
            </div>

            {{-- Chat pe site --}}
            <div class="niche-card rounded-3xl p-8 bg-ink fade-up" style="transition-delay: .1s;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background:#3A3532;">
                        <svg class="w-6 h-6 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4m-4 4l-4-4h4a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-7l-2 2z"/></svg>
                    </div>
                    <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.1); color: var(--sun);">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: var(--sun);"></span>
                        cel mai folosit
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3" style="color: var(--cream);">Chat pe site</h3>
                <p class="leading-relaxed mb-5" style="color:#D7D3CA;">Widget premium, o linie de cod. Răspunde instant, recomandă produse din magazin, preia lead-uri, trimite link-uri direct către paginile relevante.</p>
                <ul class="space-y-2 text-sm" style="color:#D7D3CA;">
                    <li class="flex gap-2"><span class="accent-text">✦</span> Carduri produse și preview link</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Branding custom, dark mode, mobile-first</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Asistență proactivă pe pagini cheie</li>
                </ul>
            </div>

            {{-- WhatsApp & social --}}
            <div class="niche-card rounded-3xl p-8 bg-cream border border-line fade-up" style="transition-delay: .2s;">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center border border-line">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l-2-5-5-2 5-2 2-5 2 5 5 2-5 2-2 5z"/></svg>
                    </div>
                    <span class="chip chip-outline text-[10px]">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                        multi-canal
                    </span>
                </div>
                <h3 class="display text-2xl font-semibold mb-3">WhatsApp · Messenger · Instagram</h3>
                <p class="leading-relaxed mb-5" style="color: var(--muted);">Un singur inbox pentru toate canalele sociale. Sincronizare cu magazinul WooCommerce pentru produse și stoc live.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><span class="accent-text">✦</span> WhatsApp Business conectat nativ</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Facebook Messenger + Instagram DM</li>
                    <li class="flex gap-2"><span class="accent-text">✦</span> Istoric unificat per client</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     INDUSTRII — grid dinamic cu toate nișele active
============================================================ --}}
<section id="industrii" class="py-20 md:py-28">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ identitate adaptivă</div>
            <h2 class="display h-display-l mb-5">
                Nu toate afacerile sunt la fel.<br>
                <span class="italic accent-text">Nici agenții tăi nu sunt.</span>
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--muted);">Fiecare vertical vine cu prompt-uri, ton, întrebări frecvente și integrări adaptate. De la stomatologie, la ecommerce, la avocatură — agentul arată ca <em>afacerea ta</em>, nu ca un template.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($niches as $n)
                <a href="{{ route('new.niche', $n->slug) }}" class="niche-card block rounded-2xl p-5 bg-paper border border-line group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-11 h-11 rounded-xl accent-soft-bg flex items-center justify-center">
                            @if(!empty($n->icon_svg))
                                <span class="accent-text w-6 h-6 inline-flex">{!! $n->icon_svg !!}</span>
                            @else
                                <svg class="w-5 h-5 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            @endif
                        </div>
                        <span class="w-2 h-2 rounded-full accent-bg opacity-70"></span>
                    </div>
                    <div class="font-semibold text-sm mb-1 leading-snug">{{ $n->name }}</div>
                    <div class="mono text-[10px] uppercase tracking-wider group-hover:text-ink transition" style="color: var(--muted);">Vezi detalii →</div>
                </a>
            @empty
                <div class="col-span-full text-center text-sm" style="color: var(--muted);">
                    Industriile se încarcă în curând.
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ============================================================
     ÎNCREDERE — anti-halucinare, fără a da rețeta
============================================================ --}}
<section class="py-20 md:py-28 bg-paper">
    <div class="max-w-6xl mx-auto px-6">
        <div class="max-w-3xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ încredere</div>
            <h2 class="display h-display-l mb-5">
                Dacă nu știe,<br>
                <span class="italic accent-text">spune că nu știe.</span>
            </h2>
            <p class="text-lg leading-relaxed" style="color: var(--muted);">
                Agenții tăi răspund <strong>doar</strong> din conținutul pe care îl alimentezi — PDF-uri, pagini web, politici, fișe produse. Când nu are răspunsul, recunoaște cinstit și trimite spre echipă, împreună cu contextul. Fără promisiuni inventate. Fără prețuri ghicite.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div class="fade-up rounded-3xl p-7 bg-cream border border-line">
                <div class="display text-5xl font-semibold accent-text mb-4">01</div>
                <h3 class="display text-xl font-semibold mb-2">Antrenat pe documentele tale</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">PDF-uri, pagini web, catalog de produse, politici de retur. Fiecare răspuns vine cu o sursă concretă pe care o poți verifica.</p>
            </div>
            <div class="fade-up rounded-3xl p-7 bg-cream border border-line" style="transition-delay:.1s;">
                <div class="display text-5xl font-semibold accent-text mb-4">02</div>
                <h3 class="display text-xl font-semibold mb-2">Verificat înainte să răspundă</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">Politici de confidence și reguli de prudență pe subiecte sensibile — prețuri, promisiuni, date medicale sau juridice.</p>
            </div>
            <div class="fade-up rounded-3xl p-7 bg-cream border border-line" style="transition-delay:.2s;">
                <div class="display text-5xl font-semibold accent-text mb-4">03</div>
                <h3 class="display text-xl font-semibold mb-2">Transferă când e cazul</h3>
                <p class="text-sm leading-relaxed" style="color: var(--muted);">Detectează frustrare, urgențe, întrebări complexe — și escaladează la operator uman cu tot contextul conversației.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     ONBOARDING 3 PAȘI — fără a menționa vendori
============================================================ --}}
<section class="py-20 md:py-28 grain relative">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="max-w-xl mb-14 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ onboarding</div>
            <h2 class="display h-display-l mb-5">
                De la <em class="italic accent-text">pagină goală</em><br>
                la agent live, într-o oră.
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
                    <p class="leading-relaxed" style="color: var(--muted);">Descrie în câteva propoziții ce face afacerea ta. Primești automat un prompt inițial, personalitate și set de setări — tu doar ajustezi ce simți că trebuie.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl bg-cream p-4 border border-line">
                        <div class="mono text-[10px] uppercase mb-2" style="color: var(--muted);">Exemplu</div>
                        <p class="text-sm italic leading-relaxed">„Clinică stomatologică în București, sector 2. Acceptăm programări online, avem plată cu cardul, asigurări parțiale."</p>
                    </div>
                </div>
            </div>

            <div class="fade-up rounded-3xl p-7 md:p-8 bg-paper border border-line grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-1"><div class="display text-6xl accent-text font-semibold">02</div></div>
                <div class="md:col-span-7">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="display text-2xl font-semibold">Hrănește baza de cunoștințe</h3>
                        <span class="chip chip-outline mono text-[10px]">~15 min</span>
                    </div>
                    <p class="leading-relaxed" style="color: var(--muted);">Încarci PDF-uri, scanezi site-ul, conectezi magazinul WooCommerce sau un folder din Google Drive. Agentul învață fiecare pagină, fiecare politică, fiecare produs.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl bg-cream p-3 border border-line space-y-1.5">
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded flex items-center justify-center text-[10px] font-bold" style="background:#FEE2E2; color:#B91C1C;">PDF</span>
                            <span class="flex-1 truncate">Tarife 2026.pdf</span>
                            <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded flex items-center justify-center text-[10px] font-bold" style="background:#FEF3C7; color:#92400E;">WEB</span>
                            <span class="flex-1 truncate">site-ul tău · 32 pagini</span>
                            <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background:#F59E0B;"></span>
                        </div>
                        <div class="flex items-center gap-2 text-xs bg-white rounded-lg px-3 py-1.5 border border-line">
                            <span class="w-6 h-6 rounded flex items-center justify-center text-[10px] font-bold" style="background:#EDE9FE; color:#6D28D9;">WOO</span>
                            <span class="flex-1 truncate">247 produse · sync live</span>
                            <span class="w-1.5 h-1.5 rounded-full" style="background: var(--emerald);"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fade-up rounded-3xl p-7 md:p-8 bg-ink grid md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-1"><div class="display text-6xl font-semibold" style="color: var(--sun);">03</div></div>
                <div class="md:col-span-7">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="display text-2xl font-semibold" style="color: var(--cream);">Pune-l la treabă</h3>
                        <span class="chip mono text-[10px]" style="background:rgba(255,255,255,.08); color:#D7D3CA;">~5 min</span>
                    </div>
                    <p class="leading-relaxed" style="color:#D7D3CA;">O linie de cod pe site. Un număr românesc dedicat pentru apeluri. WhatsApp-ul afacerii conectat. Și gata — agentul începe să răspundă de la primul minut.</p>
                </div>
                <div class="md:col-span-4">
                    <div class="rounded-2xl p-4 mono text-xs leading-relaxed" style="background:#0F0E0C; color: var(--sun);">
                        <div style="color: var(--muted);">// adaugă în &lt;head&gt;</div>
                        <div>&lt;script src=<span style="color:#A7C7F0;">"https://cdn.sambla.ro/w.js"</span><br>&nbsp;&nbsp;data-bot=<span style="color:#A7C7F0;">"afacerea-ta"</span>&gt;&lt;/script&gt;</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 text-center fade-up">
            <a href="{{ url('/register') }}" class="btn btn-primary">
                Încearcă tu în 10 minute
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ============================================================
     PREȚURI PREVIEW — scurt, redirectează spre /new/preturi
============================================================ --}}
<section class="py-20 md:py-28 bg-paper">
    <div class="max-w-5xl mx-auto px-6">
        <div class="max-w-xl mx-auto text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ prețuri</div>
            <h2 class="display h-display-l mb-5"><em class="italic accent-text">Simple.</em> În lei.<br>Fără surprize.</h2>
            <p class="text-lg" style="color: var(--muted);">7 zile gratuit. Fără card. Anulezi oricând.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div class="fade-up rounded-3xl p-7 bg-cream border border-line">
                <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">Starter</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-5xl font-medium">29</span>
                    <span style="color: var(--muted);">lei / lună</span>
                </div>
                <p class="text-sm mb-5" style="color: var(--muted);">Un agent AI pe un singur site.</p>
                <a href="{{ route('new.preturi') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Vezi detalii</a>
            </div>

            <div class="fade-up rounded-3xl p-7 bg-ink relative" style="transition-delay:.1s;">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold" style="color:#fff;">Recomandat</div>
                <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--sun);">Professional</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-5xl font-medium" style="color: var(--cream);">79</span>
                    <span style="color:#A8A29E;">lei / lună</span>
                </div>
                <p class="text-sm mb-5" style="color:#D7D3CA;">Multi-canal + CRM lead pipeline.</p>
                <a href="{{ route('new.preturi') }}" class="btn btn-primary w-full justify-center" style="background: var(--sun); color: var(--ink); width:100%;">Vezi detalii</a>
            </div>

            <div class="fade-up rounded-3xl p-7 bg-cream border border-line" style="transition-delay:.2s;">
                <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">Business</div>
                <div class="flex items-baseline gap-1 mb-3">
                    <span class="display text-5xl font-medium">199</span>
                    <span style="color: var(--muted);">lei / lună</span>
                </div>
                <p class="text-sm mb-5" style="color: var(--muted);">Volum mare, toate canalele, voce AI.</p>
                <a href="{{ route('new.preturi') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Vezi detalii</a>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     TESTIMONIAL — generic, verificabil
============================================================ --}}
<section class="py-20 md:py-28 grain relative">
    <div class="max-w-6xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink p-10 md:p-16 overflow-hidden relative grid md:grid-cols-12 gap-10 items-center fade-up">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 30%, transparent) 0%, transparent 70%);"></div>

            <div class="md:col-span-8 relative">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-6" style="color: var(--sun);">◇ experiență reală</div>
                <p class="display text-2xl md:text-4xl leading-[1.15] font-normal mb-8" style="color: var(--cream);">
                    „Pierdeam jumătate din apelurile de după program. Acum
                    <span class="italic" style="color: var(--sun);">fiecare e preluat</span>
                    — iar programările se fac în timp ce dormim."
                </p>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold" style="background: var(--sun); color: var(--ink);">AM</div>
                    <div>
                        <div class="font-semibold" style="color: var(--cream);">Medic stomatolog</div>
                        <div class="text-sm" style="color:#A8A29E;">Cabinet din București</div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-4 relative">
                <div class="rounded-2xl p-5 space-y-4" style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);">
                    <div>
                        <div class="text-xs mb-1" style="color:#A8A29E;">Apeluri pierdute / săpt.</div>
                        <div class="flex items-baseline gap-2">
                            <span class="display text-2xl line-through" style="color:#78716C;">42</span>
                            <span class="display text-3xl accent-text font-semibold">3</span>
                        </div>
                    </div>
                    <div class="border-t" style="border-color: rgba(255,255,255,.08);"></div>
                    <div>
                        <div class="text-xs mb-1" style="color:#A8A29E;">Programări / lună</div>
                        <div class="flex items-baseline gap-2">
                            <span class="display text-2xl line-through" style="color:#78716C;">120</span>
                            <span class="display text-3xl font-semibold" style="color: var(--sun);">188</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     FAQ — onest, fără jargon
============================================================ --}}
<section class="py-20 md:py-28 bg-paper">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12 fade-up">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-4" style="color: var(--muted);">◇ întrebări</div>
            <h2 class="display h-display-l">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
        </div>
        <div class="space-y-3 fade-up">
            @foreach([
                ['Cum garantați că agentul nu inventează răspunsuri?', 'Agentul răspunde doar pe baza conținutului pe care îl alimentezi tu — PDF-uri, pagini web, fișe produse. Fiecare răspuns are o sursă citabilă. Când nu e sigur, spune onest „nu am această informație" și te transferă.'],
                ['Cât timp durează setup-ul pentru afacerea mea?', 'Un agent pe chat funcțional poate fi gata în 10 minute — încarci site-ul, confirmi tonul, îl pui live. Pentru voce + WhatsApp + integrare cu calendarul, durează tipic între 3 și 5 zile lucrătoare, inclusiv testele.'],
                ['În ce limbi răspunde?', 'Nativ în română. Detectează automat când clientul scrie în engleză și răspunde în engleză — fără accent robotic, fără traduceri stângace.'],
                ['Ce integrări sunt disponibile astăzi?', 'WooCommerce și WordPress (plugin oficial), Google Calendar pentru programări, WhatsApp Business pentru mesagerie, Facebook Messenger și Instagram DM pentru inbound social. Adăugăm integrări noi la cerere.'],
                ['Cum respectați GDPR?', 'Datele sunt stocate în UE, conversațiile pot fi șterse la cerere, iar tu rămâi operator de date. Avem DPA gata de semnat, iar clienții sunt informați la începutul interacțiunii că discuția e procesată de un asistent AI.'],
                ['Ce se întâmplă dacă depășesc limita de conversații?', 'Te anunțăm înainte. Alegi fie un pachet suplimentar la unitate, fie treci pe un plan superior. Serviciul nu se oprește brusc niciodată.'],
            ] as $f)
                <details class="rounded-2xl bg-cream border border-line px-5 py-4 fade-up">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-semibold pr-6">{{ $f[0] }}</span>
                        <svg class="chev w-4 h-4 shrink-0 transition" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 leading-relaxed text-sm" style="color: var(--muted);">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     CTA FINAL
============================================================ --}}
<section class="py-20 md:py-28">
    <div class="max-w-4xl mx-auto px-6 text-center fade-up">
        <h2 class="display h-display-l mb-5">
            Angajatul tău AI te așteaptă.<br>
            <span class="italic accent-text">Începe gratuit.</span>
        </h2>
        <p class="text-lg mb-8" style="color: var(--muted);">7 zile fără card. Setup în 10 minute. Anulezi oricând.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ url('/register') }}" class="btn btn-primary">
                Creează cont gratuit
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="{{ route('new.contact') }}" class="btn btn-outline">
                Vorbește cu echipa
            </a>
        </div>
    </div>
</section>

@endsection
