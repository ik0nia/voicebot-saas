<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla — Agenți AI multi-canal (voce + chat) pentru afaceri românești. Automatizează comunicarea, răspunde 24/7 pe telefon, chat, WhatsApp, Facebook, Instagram.')">
    <title>@yield('title', 'Sambla — Agenți AI pentru afacerea ta')</title>
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#DC2626">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Sambla">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="@yield('og_title', 'Sambla — Agenți AI pentru afacerea ta')">
    <meta property="og:description" content="@yield('og_description', 'Răspund clienților 24/7, în limba română, din documentele afacerii tale.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @php
        // The short form @section('og_image', value) runs e() on the value
        // when storing it, so $ogImage is already HTML-safe. Re-escaping
        // with {{ }} would double-encode ampersands (&amp; → &amp;amp;)
        // and break OG image URL params for FB/Twitter/LinkedIn crawlers.
        $ogImage = trim($__env->yieldContent('og_image', asset('images/logo-light.svg')));
    @endphp
    <meta property="og:image" content="{!! $ogImage !!}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @if(str_ends_with($ogImage, '.svg') || str_contains($ogImage, 'og.svg'))
        <meta property="og:image:type" content="image/svg+xml">
    @endif
    <meta name="twitter:image" content="{!! $ogImage !!}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Fonturi — preconnect + preload faces folosite --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Tailwind build real (a înlocuit cdn.tailwindcss.com/3.4.1 care
         avea ~320KB JS blocking + warning console în prod).
         Conține TAILWIND JIT PURGED + toate stilurile custom warm
         (fade-up, hero-glow, chip, btn, niche-card, accent-* vars). --}}
    @vite('resources/css/new.css')

    <style>
        /* Stiluri mici rămase care depind de values dinamice din blade
           sau sunt exclusiv pe această pagină. Restul a migrat în
           resources/css/new.css. */
        .stat-num { font-feature-settings: "tnum"; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] summary .chev { transform: rotate(180deg); }
        .form-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E7E0CE; background: #FAF7EF; font-size: 14px; transition: all .2s ease; }
        .form-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        .form-label { font-size: 12px; font-weight: 600; color: #1C1917; margin-bottom: 6px; display: block; }
        .niche-tab { transition: all .2s ease; border: 1px solid transparent; }
        .niche-tab:hover { background: #fff; border-color: #E7E0CE; }
        .niche-tab.active { background: #fff; border-color: transparent; box-shadow: 0 0 0 2px currentColor; }
    </style>

    {{-- Analytics: Consent Mode v2 defaults + GTM — identic cu site-ul live. --}}
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')
    @include('partials.analytics.enterprise-tracking')

    {{-- Organization + WebSite schema globale, o dată per request.
         Per-page @yield('jsonld') adaugă tipuri specifice (FAQPage,
         Product, BreadcrumbList, etc.). --}}
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "Organization",
          "@id": "https://sambla.ro/#organization",
          "name": "Sambla",
          "alternateName": "Sambla.ro",
          "url": "https://sambla.ro",
          "logo": "{{ asset('images/logo-light.svg') }}",
          "email": "servus@sambla.ro",
          "telephone": "+40775222333",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "Bd. Dacia nr. 31",
            "addressLocality": "Oradea",
            "addressCountry": "RO"
          },
          "description": "Platformă românească de agenți AI conversaționali pe chat, telefon și canale sociale. Hosting în RO, GDPR nativ."
        },
        {
          "@type": "WebSite",
          "@id": "https://sambla.ro/#website",
          "url": "https://sambla.ro",
          "name": "Sambla",
          "inLanguage": "ro-RO",
          "publisher": { "@id": "https://sambla.ro/#organization" }
        }
      ]
    }
    </script>

    @yield('jsonld')
    @stack('styles')
</head>
<body class="antialiased flex flex-col min-h-screen" data-niche="{{ $nicheTheme ?? '' }}">
    @include('partials.analytics.body')

    {{-- NAV --}}
    <nav class="bg-cream/80 backdrop-blur sticky top-0 z-40 border-b border-line/60">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('new.home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('images/logo-light.svg') }}" alt="Sambla — Agenți AI" class="h-10 md:h-11 w-auto">
            </a>
            @php
                /* Active helper for desktop links. A link is active if the current
                   route name matches, or — for „Industrii" — if we're on a niche page. */
                $navActive = function (string ...$routes): bool {
                    foreach ($routes as $r) {
                        if (request()->routeIs($r)) return true;
                    }
                    return false;
                };
                $activeDesk = 'text-ink font-semibold relative after:absolute after:left-0 after:-bottom-1 after:h-0.5 after:w-full after:rounded-full after:bg-[var(--accent)]';
                $idleDesk   = 'text-muted hover:text-ink transition';
            @endphp
            <div class="hidden lg:flex items-center gap-7 text-sm font-medium">
                <a href="{{ route('new.despre') }}"          class="{{ $navActive('new.despre') ? $activeDesk : $idleDesk }}">Despre</a>
                <a href="{{ route('new.functionalitati') }}" class="{{ $navActive('new.functionalitati') ? $activeDesk : $idleDesk }}">Funcționalități</a>

                {{-- Mega menu Industrii — hover/focus-within deschide panoul cu nișe pe categorii --}}
                <div class="relative group" data-sb-mega>
                    <a href="{{ route('new.industrii') }}"
                       class="inline-flex items-center gap-1 {{ $navActive('new.industrii', 'new.niche') ? $activeDesk : $idleDesk }}"
                       aria-haspopup="true"
                       aria-expanded="false">
                        Industrii
                        <svg class="w-3 h-3 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </a>

                    @if(!empty($megaMenuNiches))
                        <div class="absolute left-1/2 -translate-x-1/2 top-full pt-4 invisible opacity-0 translate-y-1 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 focus-within:visible focus-within:opacity-100 focus-within:translate-y-0 transition-all duration-200 z-50" role="menu">
                            <div class="w-[880px] rounded-3xl bg-paper border border-line overflow-hidden" style="box-shadow: 0 30px 60px -20px rgba(28,25,23,0.22);">

                                {{-- Header: titlu + tagline pe un rând vizibil --}}
                                <div class="px-6 py-4 bg-cream border-b border-line flex items-end justify-between">
                                    <div>
                                        <div class="mono text-[10px] uppercase tracking-[0.2em]" style="color: var(--muted);">◇ industrii</div>
                                        <div class="display text-lg font-semibold leading-tight">17 verticale deservite</div>
                                    </div>
                                    <a href="{{ route('new.industrii') }}" class="text-xs font-semibold accent-text hover:underline shrink-0">Vezi toate →</a>
                                </div>

                                {{-- Conținut: 5 categorii curg natural pe 3 coloane CSS,
                                     break-inside-avoid ține fiecare categorie întreagă. --}}
                                <div class="p-6" style="column-count: 3; column-gap: 1.75rem;">
                                    @foreach($megaMenuNiches as $catKey => $cat)
                                        <div class="break-inside-avoid mb-5">
                                            <div class="mono text-[10px] uppercase tracking-[0.15em] pb-2 mb-2 border-b border-line/70" style="color: var(--muted);">{{ $cat['label'] }}</div>
                                            <ul class="space-y-0.5">
                                                @foreach($cat['items'] as $n)
                                                    <li>
                                                        <a href="{{ route('new.niche', $n['slug']) }}"
                                                           data-niche="{{ $n['theme'] }}"
                                                           class="mega-niche-link flex items-center gap-2.5 px-2 py-1.5 -mx-2 rounded-lg text-sm text-ink hover:bg-sand transition"
                                                           role="menuitem">
                                                            <span class="mega-niche-icon w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors">
                                                                @if($n['icon'])
                                                                    <svg class="mega-niche-svg w-3.5 h-3.5 transition-colors" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $n['icon'] !!}</svg>
                                                                @else
                                                                    <span class="w-1.5 h-1.5 rounded-full" style="background: var(--accent);"></span>
                                                                @endif
                                                            </span>
                                                            <span class="leading-tight">{{ $n['name'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Footer subtil --}}
                                <div class="px-6 py-3.5 bg-cream border-t border-line flex items-center justify-between">
                                    <span class="text-xs" style="color: var(--muted);">Nu găsești domeniul tău?</span>
                                    <a href="{{ route('new.contact') }}" class="text-xs font-semibold accent-text hover:underline">Vorbește cu noi →</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <a href="{{ route('new.deCeSambla') }}"      class="{{ $navActive('new.deCeSambla') ? $activeDesk : $idleDesk }}">De ce Sambla</a>
                <a href="{{ route('new.preturi') }}"         class="{{ $navActive('new.preturi') ? $activeDesk : $idleDesk }}">Prețuri</a>
                <a href="{{ route('new.blog') }}"            class="{{ $navActive('new.blog') ? $activeDesk : $idleDesk }}">Blog</a>
                <a href="{{ route('new.contact') }}"         class="{{ $navActive('new.contact') ? $activeDesk : $idleDesk }}">Contact</a>
            </div>
            <div class="hidden lg:flex items-center gap-2 text-sm">
                <a href="{{ url('/login') }}" class="hidden sm:inline px-4 py-2 text-muted hover:text-ink transition">Autentificare</a>
                <a href="{{ url('/register') }}" class="btn-primary">
                    Începe gratuit
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
            {{-- Mobile hamburger --}}
            <button type="button" id="sbNavToggle" class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-full border border-line bg-white hover:bg-sand transition" aria-label="Meniu" aria-expanded="false" aria-controls="sbMobileNav">
                <svg id="sbNavIconOpen"  class="w-5 h-5 text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg id="sbNavIconClose" class="w-5 h-5 text-ink hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

    </nav>

    {{-- Mobile menu — fixed overlay ca SIBLING al <nav>, nu copil.
         Pe iOS, fixed inside sticky/transformed parent e buggy, de
         aceea îl plasăm aici, afară din <nav>. JS-ul îi face add/
         remove explicit pe clasa hidden, nu toggle, ca starea să
         rămână deterministă chiar dacă se apasă butonul rapid. --}}
    <div id="sbMobileNav" class="lg:hidden hidden fixed inset-x-0 top-20 bottom-0 bg-cream border-t border-line overflow-y-auto overscroll-contain z-[60]" style="-webkit-overflow-scrolling: touch;">
        @php
            $activeMob = 'block px-4 py-3 rounded-xl text-base font-semibold text-ink accent-soft-bg border-l-4 border-[var(--accent)] transition';
            $idleMob   = 'block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition';
        @endphp
        <div class="max-w-7xl mx-auto px-6 py-5 pb-24 flex flex-col gap-1">
                <a href="{{ route('new.despre') }}"          class="{{ $navActive('new.despre') ? $activeMob : $idleMob }}">Despre</a>
                <a href="{{ route('new.functionalitati') }}" class="{{ $navActive('new.functionalitati') ? $activeMob : $idleMob }}">Funcționalități</a>

                {{-- Mobile Industrii — accordion cu categoriile, icon per link --}}
                @if(!empty($megaMenuNiches))
                    <details class="group/ind rounded-xl overflow-hidden {{ $navActive('new.industrii', 'new.niche') ? 'accent-soft-bg border-l-4 border-[var(--accent)]' : '' }}">
                        <summary class="flex items-center justify-between px-4 py-3 cursor-pointer text-base font-medium text-ink hover:bg-sand transition list-none">
                            <span class="{{ $navActive('new.industrii', 'new.niche') ? 'font-semibold' : '' }}">Industrii</span>
                            <svg class="w-4 h-4 transition-transform group-open/ind:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="px-2 pb-3 pt-1 space-y-4">
                            <a href="{{ route('new.industrii') }}" class="block px-4 py-2 text-sm font-semibold accent-text hover:bg-sand rounded-lg">Vezi toate industriile →</a>
                            @foreach($megaMenuNiches as $cat)
                                <div>
                                    <div class="mono text-[10px] uppercase tracking-[0.15em] px-4 pb-1.5" style="color: var(--muted);">{{ $cat['label'] }}</div>
                                    <ul class="space-y-0.5">
                                        @foreach($cat['items'] as $n)
                                            <li>
                                                <a href="{{ route('new.niche', $n['slug']) }}" data-niche="{{ $n['theme'] }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink hover:bg-sand rounded-lg">
                                                    <span class="w-7 h-7 rounded-lg accent-soft-bg flex items-center justify-center shrink-0">
                                                        @if($n['icon'])
                                                            <svg class="w-3.5 h-3.5 accent-text" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $n['icon'] !!}</svg>
                                                        @else
                                                            <span class="w-1.5 h-1.5 rounded-full accent-bg"></span>
                                                        @endif
                                                    </span>
                                                    <span>{{ $n['name'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @else
                    <a href="{{ route('new.industrii') }}" class="{{ $navActive('new.industrii', 'new.niche') ? $activeMob : $idleMob }}">Industrii</a>
                @endif
                <a href="{{ route('new.deCeSambla') }}"      class="{{ $navActive('new.deCeSambla') ? $activeMob : $idleMob }}">De ce Sambla</a>
                <a href="{{ route('new.preturi') }}"         class="{{ $navActive('new.preturi') ? $activeMob : $idleMob }}">Prețuri</a>
                <a href="{{ route('new.blog') }}"            class="{{ $navActive('new.blog') ? $activeMob : $idleMob }}">Blog</a>
                <a href="{{ route('new.contact') }}"         class="{{ $navActive('new.contact') ? $activeMob : $idleMob }}">Contact</a>
                <div class="h-px bg-line my-3"></div>
                <a href="{{ url('/login') }}" class="block px-4 py-3 rounded-xl text-base font-medium text-muted hover:text-ink hover:bg-sand transition">Autentificare</a>
                <a href="{{ url('/register') }}" class="btn-primary justify-center mt-2" style="width:100%;">
                    Începe gratuit
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
        </div>
    </div>

    <main class="flex-1">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="py-14 bg-paper border-t border-line mt-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-5 gap-8 pb-10 border-b border-line">
                <div class="md:col-span-2">
                    <img src="{{ asset('images/logo-light.svg') }}" alt="Sambla" class="h-10 w-auto mb-4">
                    <p class="text-sm text-muted leading-relaxed max-w-sm">Angajatul tău AI care știe totul despre afacerea ta. Voce naturală, chat inteligent, auto-îmbunătățire continuă.</p>
                </div>
                <div>
                    <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Produs</h4>
                    <ul class="space-y-2 text-sm text-muted">
                        <li><a href="{{ route('new.functionalitati') }}" class="hover:text-ink">Funcționalități</a></li>
                        <li><a href="{{ route('new.preturi') }}" class="hover:text-ink">Prețuri</a></li>
                        <li><a href="{{ route('new.deCeSambla') }}" class="hover:text-ink">De ce Sambla</a></li>
                        <li><a href="{{ url('/register') }}" class="hover:text-ink">Cont gratuit</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Industrii</h4>
                    <ul class="space-y-2 text-sm text-muted">
                        @foreach(($footerNiches ?? collect()) as $n)
                            <li><a href="{{ route('new.niche', $n->slug) }}" class="hover:text-ink">{{ $n->name }}</a></li>
                        @endforeach
                        <li><a href="{{ route('new.industrii') }}" class="hover:text-ink font-medium accent-text">Vezi toate →</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mono text-[11px] uppercase tracking-wider font-semibold mb-3">Companie</h4>
                    <ul class="space-y-2 text-sm text-muted">
                        <li><a href="{{ route('new.despre') }}" class="hover:text-ink">Despre</a></li>
                        <li><a href="{{ route('new.blog') }}" class="hover:text-ink">Blog</a></li>
                        <li><a href="{{ route('new.contact') }}" class="hover:text-ink">Contact</a></li>
                        <li><a href="{{ route('new.legal.termeni') }}" class="hover:text-ink">Termeni</a></li>
                        <li><a href="{{ route('new.legal.confidentialitate') }}" class="hover:text-ink">Confidențialitate</a></li>
                        <li><a href="{{ route('new.legal.cookies') }}" class="hover:text-ink">Cookie-uri</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 pt-6 text-xs mono text-muted">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>© {{ date('Y') }} {{ config('company.legal_name') }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="whitespace-nowrap">CUI {{ config('company.vat_prefix') }}{{ config('company.cui') }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="whitespace-nowrap">{{ config('company.reg_com') }}</span>
                    <span aria-hidden="true">·</span>
                    <a href="mailto:{{ config('company.contact.email') }}" class="hover:text-ink">{{ config('company.contact.email') }}</a>
                    <span aria-hidden="true">·</span>
                    <a href="tel:+40775222333" class="hover:text-ink">0775 222 333</a>
                    <span aria-hidden="true">·</span>
                    <a href="https://wa.me/40775222333" target="_blank" rel="noopener" class="hover:text-ink inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.413c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.595 5.392l-.999 3.648 3.893-1.022zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        WhatsApp
                    </a>
                </div>
                <div class="flex gap-4">
                    <span>🇷🇴 Construit în România</span>
                    <span>✓ GDPR compliant</span>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.analytics.consent-widget')

    {{-- Mobile menu toggle + fade-up observer --}}
    <script>
    (function(){
        /* Mobile hamburger menu — stare explicită, add/remove în loc de toggle */
        var btn  = document.getElementById('sbNavToggle');
        var menu = document.getElementById('sbMobileNav');
        var iOpen  = document.getElementById('sbNavIconOpen');
        var iClose = document.getElementById('sbNavIconClose');
        if (btn && menu) {
            var isOpen = false;
            function setOpen(open) {
                isOpen = !!open;
                if (isOpen) {
                    menu.classList.remove('hidden');
                } else {
                    menu.classList.add('hidden');
                }
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                if (iOpen)  iOpen.classList[isOpen  ? 'add' : 'remove']('hidden');
                if (iClose) iClose.classList[isOpen ? 'remove' : 'add']('hidden');
                document.body.style.overflow = isOpen ? 'hidden' : '';
            }
            btn.addEventListener('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                setOpen(!isOpen);
            });
            /* Close on any link click (dar nu pe summary / interior details) */
            menu.addEventListener('click', function(e){
                var a = e.target.closest('a');
                if (a && menu.contains(a)) setOpen(false);
            });
            /* Close pe Escape */
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && isOpen) setOpen(false);
            });
        }

        /* Fade-up on scroll */
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('.fade-up').forEach(function(el){ el.classList.add('in'); });
            return;
        }
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
        document.querySelectorAll('.fade-up').forEach(function(el){ io.observe(el); });
    })();
    </script>

    {{-- Chat widget (consent-gated, same as live site) --}}
    <script>
    (function () {
        var widgetUrl = '@samblaWidgetUrl';
        function loadChatWidget() {
            if (window.__samblaChatLoaded) return;
            window.__samblaChatLoaded = true;
            var s = document.createElement('script');
            s.src = widgetUrl; s.async = true; s.defer = true;
            s.setAttribute('data-channel-id', '1');
            s.setAttribute('data-bot-name', 'Sambla');
            s.setAttribute('data-color', '#991b1b');
            s.setAttribute('data-lang', 'ro');
            s.setAttribute('data-greeting', 'Salut! Sunt Sambla. Pot să îți povestesc cum funcționează agenții AI sau să te ajut să îți configurezi unul.');
            document.body.appendChild(s);
        }
        var decided = false;
        try { decided = !!localStorage.getItem('sambla_consent'); } catch (e) {}
        if (decided) loadChatWidget();
        else window.addEventListener('sambla:consent-decided', loadChatWidget, { once: true });
    })();
    </script>

    @stack('scripts')
</body>
</html>
