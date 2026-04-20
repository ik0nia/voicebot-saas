<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla — Agenți AI conversaționali și vocali pentru afacerile din România. Răspund clienților 24/7, pe telefon și pe site, în limba română, din documentele tale. Fără halucinații, fără promisiuni false.')">
    <title>@yield('title', 'Sambla — Agenți AI care răspund clienților tăi 24/7, în limba română')</title>
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Favicon + theme --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.svg') }}">
    <meta name="theme-color" content="#C2410C">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Sambla">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="@yield('og_title', 'Sambla — Agenți AI pentru afacerea ta')">
    <meta property="og:description" content="@yield('og_description', 'Răspund clienților pe telefon și pe site, 24/7, în limba română. Din documentele și produsele tale — nu inventează.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/logo-light.svg'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Sambla — Agenți AI pentru afacerea ta')">
    <meta name="twitter:description" content="@yield('og_description', 'Răspund clienților pe telefon și pe site, 24/7, în limba română.')">

    {{-- Preconnect + self-hosted variable Inter (matches the production stack) --}}
    <link rel="preconnect" href="https://cdn.sambla.ro" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.sambla.ro">
    <link rel="preload" as="font" type="font/woff2"
          href="https://cdn.sambla.ro/fonts/inter/inter-latin-ext-variable.woff2" crossorigin>
    <link href="https://cdn.sambla.ro/fonts/inter/inter.css" rel="stylesheet" />

    {{-- Display + mono faces: async via Google Fonts with preconnect --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap"
          rel="stylesheet" media="print" onload="this.media='all'">

    {{-- Reuse the existing production Tailwind bundle (already compiled,
         already cached). No build changes needed for /new — we just add
         warm-specific tokens & components in the inline style below. --}}
    @vite(['resources/css/app.css'])

    {{-- Warm palette + components, isolated to the /new surface. All
         selectors are scoped to `.sambla-new` on <body> so there is no
         chance of bleeding into the legacy site if a user navigates
         between them in the same session. --}}
    <style>
    .sambla-new {
        --cream:       #F5F1E8;
        --paper:       #FAF7EF;
        --sand:        #EFE5D0;
        --sandy:       #E5DCC4;
        --ink:         #1C1917;
        --ink-soft:    #3A3532;
        --muted:       #78716C;
        --line:        #E7E0CE;
        --coral:       #DC2626;
        --coral-h:     #991B1B;
        --coral-soft:  #FEE2E2;
        --sun:         #F2E59A;
        --emerald:     #059669;

        --accent:      var(--coral);
        --accent-dark: var(--coral-h);
        --accent-soft: var(--coral-soft);

        background: var(--cream);
        color: var(--ink);
        font-family: 'Inter', system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
        font-feature-settings: "cv11","ss01";
    }
    .sambla-new[data-niche="medical"]   { --accent:#0284C7; --accent-dark:#0369A1; --accent-soft:#E0F2FE; }
    .sambla-new[data-niche="beauty"]    { --accent:#DB2777; --accent-dark:#BE185D; --accent-soft:#FCE7F3; }
    .sambla-new[data-niche="auto"]      { --accent:#EA580C; --accent-dark:#C2410C; --accent-soft:#FFEDD5; }
    .sambla-new[data-niche="resto"]     { --accent:#059669; --accent-dark:#047857; --accent-soft:#D1FAE5; }
    .sambla-new[data-niche="imob"]      { --accent:#B45309; --accent-dark:#92400E; --accent-soft:#FEF3C7; }
    .sambla-new[data-niche="legal"]     { --accent:#7C3AED; --accent-dark:#6D28D9; --accent-soft:#EDE9FE; }
    .sambla-new[data-niche="education"] { --accent:#4F46E5; --accent-dark:#4338CA; --accent-soft:#E0E7FF; }
    .sambla-new[data-niche="travel"]    { --accent:#0891B2; --accent-dark:#0E7490; --accent-soft:#CFFAFE; }

    .sambla-new .display   { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
    .sambla-new .mono      { font-family: 'JetBrains Mono', ui-monospace, monospace; }

    /* Layout helpers */
    .sambla-new .bg-cream  { background: var(--cream); }
    .sambla-new .bg-paper  { background: var(--paper); }
    .sambla-new .bg-sand   { background: var(--sand); }
    .sambla-new .bg-ink    { background: var(--ink); }
    .sambla-new .text-ink  { color: var(--ink); }
    .sambla-new .text-muted{ color: var(--muted); }
    .sambla-new .text-cream{ color: var(--cream); }
    .sambla-new .border-line { border-color: var(--line); }

    .sambla-new .accent-text     { color: var(--accent); }
    .sambla-new .accent-bg       { background: var(--accent); }
    .sambla-new .accent-soft-bg  { background: var(--accent-soft); }
    .sambla-new .accent-dark-bg  { background: var(--accent-dark); }

    /* Buttons */
    .sambla-new .btn { border-radius: 999px; padding: 14px 24px; font-weight: 600;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
        display: inline-flex; align-items: center; gap: 8px; font-size: 15px; line-height: 1; }
    .sambla-new .btn-primary { background: var(--accent); color:#fff; }
    .sambla-new .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px);
        box-shadow: 0 10px 30px -5px color-mix(in srgb, var(--accent) 35%, transparent); }
    .sambla-new .btn-ink { background: var(--ink); color:#fff; }
    .sambla-new .btn-ink:hover { background: var(--ink-soft); transform: translateY(-1px); }
    .sambla-new .btn-outline { border: 1.5px solid var(--ink); color: var(--ink); background: transparent; padding: 12.5px 22.5px; }
    .sambla-new .btn-outline:hover { background: var(--ink); color:#fff; }
    .sambla-new .btn-ghost { background: transparent; color: var(--ink); padding: 10px 14px; }
    .sambla-new .btn-ghost:hover { color: var(--accent); }

    /* Chips */
    .sambla-new .chip { border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 500;
        display: inline-flex; align-items: center; gap: 6px; }
    .sambla-new .chip-outline { background:#fff; border:1px solid var(--line); color: var(--muted); }
    .sambla-new .chip-soft    { background: var(--accent-soft); color: var(--accent-dark); }
    .sambla-new .chip-filled  { background: var(--accent); color:#fff; }

    /* Hero glow */
    .sambla-new .hero-glow {
        background:
          radial-gradient(ellipse 45% 35% at 18% 12%, color-mix(in srgb, var(--accent) 22%, transparent) 0%, transparent 60%),
          radial-gradient(ellipse 40% 30% at 82% 22%, rgba(242,229,154,0.45) 0%, transparent 60%),
          radial-gradient(ellipse 50% 40% at 70% 90%, rgba(199,184,232,0.28) 0%, transparent 60%);
    }

    /* Fade-up + float animations */
    .sambla-new .fade-up { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
    .sambla-new .fade-up.in { opacity: 1; transform: translateY(0); }
    @media (prefers-reduced-motion: reduce) {
        .sambla-new .fade-up { opacity: 1; transform: none; transition: none; }
        .sambla-new .float   { animation: none !important; }
    }
    .sambla-new .float { animation: sbFloatY 6s ease-in-out infinite; }
    @keyframes sbFloatY { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }

    /* Grain texture (used sparingly for depth) */
    .sambla-new .grain { position: relative; }
    .sambla-new .grain::after {
        content:''; position:absolute; inset:0; pointer-events:none; opacity:.35;
        background-image: radial-gradient(rgba(28,25,23,0.05) 1px, transparent 1px);
        background-size: 3px 3px;
    }

    /* Niche card */
    .sambla-new .niche-card { transition: transform .25s ease, box-shadow .25s ease; }
    .sambla-new .niche-card:hover { transform: translateY(-4px);
        box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--accent) 22%, transparent); }

    /* Typography fluid */
    .sambla-new .h-display-xl { font-size: clamp(2.5rem, 5.5vw, 4.75rem); line-height: 1.02; letter-spacing: -0.025em; font-weight: 500; }
    .sambla-new .h-display-l  { font-size: clamp(2rem, 4vw, 3.5rem);    line-height: 1.05; letter-spacing: -0.02em;  font-weight: 500; }
    .sambla-new .h-display-m  { font-size: clamp(1.5rem, 2.5vw, 2.25rem); line-height: 1.15; letter-spacing: -0.015em; font-weight: 600; }

    /* FAQ accordion */
    .sambla-new details > summary::-webkit-details-marker { display: none; }
    .sambla-new details > summary { list-style: none; }
    .sambla-new details[open] summary .chev { transform: rotate(180deg); }

    /* Scrollbar hide on chat demos */
    .sambla-new .no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
    .sambla-new .no-scrollbar::-webkit-scrollbar { display: none; }

    /* Links */
    .sambla-new a { text-decoration: none; color: inherit; }
    </style>

    {{-- Analytics: Consent Mode v2 defaults + GTM. Identical to the live site
         so we never lose events when visitors land on /new. --}}
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')
    @include('partials.analytics.enterprise-tracking')

    @yield('jsonld')

    {{-- Default JSON-LD for the Organization — mirrors the live site's
         schema so both surfaces present the same business to search
         engines. Individual pages can override via @yield('jsonld'). --}}
    <script type="application/ld+json">
    {
        "@context":"https://schema.org",
        "@graph":[
            {
                "@type":"Organization",
                "@id":"https://sambla.ro/#organization",
                "name":"Sambla",
                "url":"https://sambla.ro",
                "logo":"https://cdn.sambla.ro/images/logo-icon.svg",
                "description":"Platformă SaaS românească de agenți AI conversaționali — agent AI pe site și agent AI vocal pentru telefon. Răspund 24/7, în limba română, din documentele afacerii tale.",
                "areaServed":{"@type":"Country","name":"Romania"},
                "knowsLanguage":["ro","en"],
                "email":"servus@sambla.ro",
                "telephone":"+40775222333",
                "address":{"@type":"PostalAddress","addressCountry":"RO"}
            },
            {
                "@type":"WebSite",
                "@id":"https://sambla.ro/#website",
                "url":"https://sambla.ro",
                "name":"Sambla",
                "publisher":{"@id":"https://sambla.ro/#organization"},
                "inLanguage":"ro-RO"
            }
        ]
    }
    </script>

    @stack('styles')
</head>
<body class="sambla-new min-h-screen flex flex-col" data-niche="{{ $nicheTheme ?? '' }}">
    @include('partials.analytics.body')

    {{-- Top announcement bar — subtle, sans-vendor. --}}
    <div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
        <span class="mono opacity-70">preview /new ·</span>
        <a href="/" class="underline opacity-80 hover:opacity-100">înapoi la site-ul actual</a>
    </div>

    {{-- Navigation --}}
    <nav class="sticky top-0 z-40 backdrop-blur" style="background: color-mix(in srgb, var(--cream) 85%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--line) 60%, transparent);">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('new.home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('images/logo-light.svg') }}" alt="Sambla — Agenți AI" class="h-10 md:h-11 w-auto">
            </a>
            <div class="hidden lg:flex items-center gap-8 text-sm font-medium" style="color: var(--muted);">
                <a href="{{ route('new.functionalitati') }}" class="hover:text-ink transition">Ce face</a>
                <a href="{{ route('new.home') }}#industrii" class="hover:text-ink transition">Industrii</a>
                <a href="{{ route('new.deCeSambla') }}" class="hover:text-ink transition">De ce Sambla</a>
                <a href="{{ route('new.preturi') }}" class="hover:text-ink transition">Prețuri</a>
                <a href="{{ route('new.despre') }}" class="hover:text-ink transition">Despre</a>
                <a href="{{ route('new.contact') }}" class="hover:text-ink transition">Contact</a>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ url('/login') }}" class="hidden sm:inline px-4 py-2 transition hover:text-ink" style="color: var(--muted);">Autentificare</a>
                <a href="{{ url('/register') }}" class="btn btn-primary">
                    Începe gratuit
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-20 border-t border-line" style="background: var(--paper);">
        <div class="max-w-7xl mx-auto px-6 py-16 grid gap-10 md:grid-cols-12">
            <div class="md:col-span-4">
                <a href="{{ route('new.home') }}" class="inline-flex items-center gap-2 mb-5">
                    <img src="{{ asset('images/logo-light.svg') }}" alt="Sambla" class="h-10 w-auto">
                </a>
                <p class="text-sm leading-relaxed mb-6" style="color: var(--muted); max-width: 22rem;">
                    Agenți AI conversaționali pentru afacerile din România. Răspund clienților, preiau programări și captează lead-uri — 24/7, în limba română.
                </p>
                <div class="flex items-center gap-3">
                    <a href="https://facebook.com/sambla.ai" rel="noopener noreferrer" target="_blank" aria-label="Facebook" class="w-9 h-9 rounded-full border border-line flex items-center justify-center hover:text-ink transition" style="color: var(--muted);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.6 9.88v-6.99H7.9V12h2.5V9.8c0-2.46 1.46-3.82 3.71-3.82 1.07 0 2.2.19 2.2.19v2.42h-1.24c-1.22 0-1.6.76-1.6 1.54V12h2.72l-.43 2.89h-2.29v6.99A10 10 0 0022 12z"/></svg>
                    </a>
                    <a href="https://linkedin.com/company/sambla-ai" rel="noopener noreferrer" target="_blank" aria-label="LinkedIn" class="w-9 h-9 rounded-full border border-line flex items-center justify-center hover:text-ink transition" style="color: var(--muted);">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.98 3.5A2.5 2.5 0 002.5 6v.01A2.5 2.5 0 105 6 2.5 2.5 0 004.98 3.5zM3 8.75h4v12h-4v-12zM9 8.75h3.84v1.63h.06c.53-1 1.84-2.07 3.78-2.07 4.05 0 4.8 2.66 4.8 6.13v6.31h-4v-5.6c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.95v5.69h-4v-12z"/></svg>
                    </a>
                </div>
            </div>
            <div class="md:col-span-2">
                <div class="mono text-[11px] uppercase tracking-wider mb-3" style="color: var(--muted);">Produs</div>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('new.functionalitati') }}" class="hover:text-ink" style="color: var(--muted);">Ce face Sambla</a></li>
                    <li><a href="{{ route('new.preturi') }}" class="hover:text-ink" style="color: var(--muted);">Prețuri</a></li>
                    <li><a href="{{ route('new.deCeSambla') }}" class="hover:text-ink" style="color: var(--muted);">De ce Sambla</a></li>
                    <li><a href="{{ url('/register') }}" class="hover:text-ink" style="color: var(--muted);">Cont gratuit</a></li>
                </ul>
            </div>
            <div class="md:col-span-3">
                <div class="mono text-[11px] uppercase tracking-wider mb-3" style="color: var(--muted);">Industrii</div>
                <ul class="space-y-2 text-sm">
                    @foreach(($footerNiches ?? collect()) as $n)
                        <li><a href="{{ route('new.niche', $n->slug) }}" class="hover:text-ink" style="color: var(--muted);">{{ $n->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('new.home') }}#industrii" class="hover:text-ink font-medium" style="color: var(--accent);">Vezi toate →</a></li>
                </ul>
            </div>
            <div class="md:col-span-3">
                <div class="mono text-[11px] uppercase tracking-wider mb-3" style="color: var(--muted);">Companie</div>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('new.despre') }}" class="hover:text-ink" style="color: var(--muted);">Despre</a></li>
                    <li><a href="{{ route('new.blog') }}" class="hover:text-ink" style="color: var(--muted);">Blog</a></li>
                    <li><a href="{{ route('new.contact') }}" class="hover:text-ink" style="color: var(--muted);">Contact</a></li>
                    <li><a href="{{ route('new.legal.termeni') }}" class="hover:text-ink" style="color: var(--muted);">Termeni</a></li>
                    <li><a href="{{ route('new.legal.confidentialitate') }}" class="hover:text-ink" style="color: var(--muted);">Confidențialitate</a></li>
                    <li><a href="{{ route('new.legal.cookies') }}" class="hover:text-ink" style="color: var(--muted);">Cookie-uri</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-line">
            <div class="max-w-7xl mx-auto px-6 py-6 flex flex-wrap items-center justify-between gap-3 text-xs" style="color: var(--muted);">
                <div>© {{ date('Y') }} Sambla · Construit în România · GDPR by default</div>
                <div class="flex items-center gap-4">
                    <a href="mailto:servus@sambla.ro" class="hover:text-ink">servus@sambla.ro</a>
                    <a href="tel:+40775222333" class="hover:text-ink">+40 775 222 333</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Cookie consent (same as live site). --}}
    @include('partials.analytics.consent-widget')

    {{-- Fade-up intersection observer — tiny inline JS, no framework. --}}
    <script>
    (function(){
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('.fade-up').forEach(function(el){ el.classList.add('in'); });
            return;
        }
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.fade-up').forEach(function(el){ io.observe(el); });
    })();
    </script>

    {{-- Chat widget (same config as live site, gated by consent). --}}
    <script>
    (function () {
        var widgetUrl = '{{ rtrim(config('app.cdn_url') ?: config('app.url'), '/') }}/widget/sambla-chat.min.js';
        function loadChatWidget() {
            if (window.__samblaChatLoaded) return;
            window.__samblaChatLoaded = true;
            var s = document.createElement('script');
            s.src = widgetUrl; s.async = true; s.defer = true;
            s.setAttribute('data-channel-id', '1');
            s.setAttribute('data-bot-name', 'Sambla');
            s.setAttribute('data-color', '#991b1b');
            s.setAttribute('data-lang', 'ro');
            s.setAttribute('data-greeting', 'Salut! Sunt Sambla. Pot să îți povestesc cum funcționează agenții AI sau să te ajut să îți configurezi unul pentru afacerea ta.');
            document.body.appendChild(s);
        }
        var decided = false;
        try { decided = !!localStorage.getItem('sambla_consent'); } catch (e) {}
        if (decided) { loadChatWidget(); }
        else { window.addEventListener('sambla:consent-decided', loadChatWidget, { once: true }); }
    })();
    </script>

    @stack('scripts')
</body>
</html>
