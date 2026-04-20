<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla — Agenți AI multi-canal (voce + chat) pentru afaceri românești. Automatizează comunicarea, răspunde 24/7 pe telefon, chat, WhatsApp, Facebook, Instagram.')">
    <title>@yield('title', 'Sambla — Agenți AI pentru afacerea ta')</title>
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.svg') }}">
    <meta name="theme-color" content="#DC2626">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Sambla">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:title" content="@yield('og_title', 'Sambla — Agenți AI pentru afacerea ta')">
    <meta property="og:description" content="@yield('og_description', 'Răspund clienților 24/7, în limba română, din documentele afacerii tale.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/logo-light.svg'))">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Fonturi — preconnect + preload faces folosite --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Tailwind CDN — aceeași configurație ca în preview-urile aprobate,
         ca toate utility-urile warm (bg-cream, bg-paper, text-ink, etc.)
         să se rezolve identic cu V5. --}}
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans:    ['Inter','system-ui','sans-serif'],
                    display: ['Instrument Sans','Inter','sans-serif'],
                    mono:    ['JetBrains Mono','ui-monospace','monospace']
                },
                colors: {
                    cream:     '#F5F1E8',
                    paper:     '#FAF7EF',
                    sand:      '#EFE5D0',
                    sandy:     '#E5DCC4',
                    ink:       '#1C1917',
                    inkSoft:   '#3A3532',
                    muted:     '#78716C',
                    line:      '#E7E0CE',
                    coral:     '#DC2626',
                    coralh:    '#991B1B',
                    coralsoft: '#FEE2E2',
                    peach:     '#FDBA8C',
                    sun:       '#F2E59A',
                    lilac:     '#C7B8E8',
                    sky:       '#A7C7F0'
                }
            }
        }
    }
    </script>

    <style>
        /* -------- Per-niche CSS custom properties (swappable runtime) -------- */
        :root { --accent: #DC2626; --accent-soft: #FEE2E2; --accent-dark: #991B1B; }
        [data-niche="medical"]   { --accent: #3B82F6; --accent-soft: #DBEAFE; --accent-dark: #2563EB; }
        [data-niche="beauty"]    { --accent: #F43F5E; --accent-soft: #FFE4E6; --accent-dark: #E11D48; }
        [data-niche="auto"]      { --accent: #F97316; --accent-soft: #FFEDD5; --accent-dark: #EA580C; }
        [data-niche="resto"]     { --accent: #10B981; --accent-soft: #D1FAE5; --accent-dark: #059669; }
        [data-niche="imob"]      { --accent: #F59E0B; --accent-soft: #FEF3C7; --accent-dark: #D97706; }
        [data-niche="legal"]     { --accent: #A855F7; --accent-soft: #F3E8FF; --accent-dark: #9333EA; }
        [data-niche="education"] { --accent: #4F46E5; --accent-soft: #E0E7FF; --accent-dark: #4338CA; }
        [data-niche="travel"]    { --accent: #06B6D4; --accent-soft: #CFFAFE; --accent-dark: #0891B2; }
        [data-niche="red"]       { --accent: #DC2626; --accent-soft: #FEE2E2; --accent-dark: #991B1B; }
        [data-niche="emerald"]   { --accent: #10B981; --accent-soft: #D1FAE5; --accent-dark: #059669; }
        [data-niche="blue"]      { --accent: #3B82F6; --accent-soft: #DBEAFE; --accent-dark: #2563EB; }
        [data-niche="amber"]     { --accent: #F59E0B; --accent-soft: #FEF3C7; --accent-dark: #D97706; }
        [data-niche="rose"]      { --accent: #F43F5E; --accent-soft: #FFE4E6; --accent-dark: #E11D48; }
        [data-niche="purple"]    { --accent: #A855F7; --accent-soft: #F3E8FF; --accent-dark: #9333EA; }
        [data-niche="indigo"]    { --accent: #6366F1; --accent-soft: #E0E7FF; --accent-dark: #4F46E5; }
        [data-niche="teal"]      { --accent: #14B8A6; --accent-soft: #CCFBF1; --accent-dark: #0D9488; }
        [data-niche="cyan"]      { --accent: #06B6D4; --accent-soft: #CFFAFE; --accent-dark: #0891B2; }
        [data-niche="orange"]    { --accent: #F97316; --accent-soft: #FFEDD5; --accent-dark: #EA580C; }

        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; font-feature-settings: "cv11","ss01"; }
        .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
        .mono    { font-family: 'JetBrains Mono', ui-monospace, monospace; }

        /* Buttons */
        .btn-primary { background: var(--accent); color: #fff; border-radius: 999px; padding: 14px 24px; font-weight: 600;
            transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px);
            box-shadow: 0 10px 30px color-mix(in srgb, var(--accent) 30%, transparent); }
        .btn-ghost   { background: #1C1917; color: #fff; border-radius: 999px; padding: 14px 24px; font-weight: 600;
            transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-ghost:hover { background: #3A3532; transform: translateY(-1px); }
        .btn-outline { border: 1.5px solid #1C1917; color: #1C1917; background: transparent; border-radius: 999px;
            padding: 13px 23px; font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-outline:hover { background: #1C1917; color: #fff; }

        /* Chips */
        .chip { border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
        .chip-outline { background: #fff; border: 1px solid #E7E0CE; color: #78716C; }
        .chip-filled  { background: var(--accent); color: #fff; }

        /* Animations */
        .fade-up { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; }
        .fade-up.in { opacity: 1; transform: translateY(0); }
        @media (prefers-reduced-motion: reduce) {
            .fade-up { opacity: 1; transform: none; transition: none; }
            .float, .ticker { animation: none !important; }
        }
        .float { animation: sbFloatY 6s ease-in-out infinite; }
        @keyframes sbFloatY { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-8px) } }

        .ticker { animation: sbTickerScroll 50s linear infinite; }
        @keyframes sbTickerScroll { 0% { transform: translateX(0) } 100% { transform: translateX(-50%) } }

        .dots span { animation: sbDot 1.4s infinite; }
        .dots span:nth-child(2) { animation-delay: .2s }
        .dots span:nth-child(3) { animation-delay: .4s }
        @keyframes sbDot { 0%,60%,100% { opacity: .3 } 30% { opacity: 1 } }

        /* Grain texture */
        .grain { position: relative; }
        .grain::after { content:''; position:absolute; inset:0; pointer-events:none; opacity:.4;
            background-image: radial-gradient(rgba(28,25,23,0.05) 1px, transparent 1px); background-size: 3px 3px; }

        /* Hero glow */
        .hero-glow {
            background:
              radial-gradient(ellipse 45% 35% at 20% 15%, color-mix(in srgb, var(--accent) 18%, transparent) 0%, transparent 60%),
              radial-gradient(ellipse 40% 30% at 85% 20%, rgba(247,213,147,0.35) 0%, transparent 60%),
              radial-gradient(ellipse 50% 40% at 70% 90%, rgba(199,184,232,0.25) 0%, transparent 60%);
            transition: background .8s ease;
        }

        /* Niche card */
        .niche-card { transition: transform .25s ease, box-shadow .25s ease; }
        .niche-card:hover { transform: translateY(-4px);
            box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--accent) 22%, transparent); }

        /* Accent helpers (CSS vars cascade) */
        .accent-text    { color: var(--accent); }
        .accent-bg      { background: var(--accent); }
        .accent-soft-bg { background: var(--accent-soft); }
        .accent-dark-bg { background: var(--accent-dark); }

        .stat-num { font-feature-settings: "tnum"; }

        /* FAQ caret */
        details > summary::-webkit-details-marker { display: none; }
        details[open] summary .chev { transform: rotate(180deg); }

        /* Forms */
        .form-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #E7E0CE; background: #FAF7EF; font-size: 14px; transition: all .2s ease; }
        .form-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        .form-label { font-size: 12px; font-weight: 600; color: #1C1917; margin-bottom: 6px; display: block; }

        /* Niche tabs (niche switcher on landing pages) */
        .niche-tab { transition: all .2s ease; border: 1px solid transparent; }
        .niche-tab:hover { background: #fff; border-color: #E7E0CE; }
        .niche-tab.active { background: #fff; border-color: transparent; box-shadow: 0 0 0 2px currentColor; }

        /* Scrollbar hide */
        .no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>

    {{-- Analytics: Consent Mode v2 defaults + GTM — identic cu site-ul live. --}}
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')
    @include('partials.analytics.enterprise-tracking')

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
            <div class="hidden lg:flex items-center gap-8 text-sm font-medium text-muted">
                <a href="{{ route('new.functionalitati') }}" class="hover:text-ink transition">Funcționalități</a>
                <a href="{{ route('new.industrii') }}" class="hover:text-ink transition">Industrii</a>
                <a href="{{ route('new.deCeSambla') }}" class="hover:text-ink transition">De ce Sambla</a>
                <a href="{{ route('new.preturi') }}" class="hover:text-ink transition">Prețuri</a>
                <a href="{{ route('new.despre') }}" class="hover:text-ink transition">Despre</a>
                <a href="{{ route('new.blog') }}" class="hover:text-ink transition">Blog</a>
                <a href="{{ route('new.contact') }}" class="hover:text-ink transition">Contact</a>
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

        {{-- Mobile slide-down menu --}}
        <div id="sbMobileNav" class="lg:hidden hidden border-t border-line bg-cream">
            <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col gap-1">
                <a href="{{ route('new.functionalitati') }}" class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">Funcționalități</a>
                <a href="{{ route('new.industrii') }}" class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">Industrii</a>
                <a href="{{ route('new.deCeSambla') }}"      class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">De ce Sambla</a>
                <a href="{{ route('new.preturi') }}"         class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">Prețuri</a>
                <a href="{{ route('new.despre') }}"          class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">Despre</a>
                <a href="{{ route('new.contact') }}"         class="block px-4 py-3 rounded-xl text-base font-medium text-ink hover:bg-sand transition">Contact</a>
                <div class="h-px bg-line my-3"></div>
                <a href="{{ url('/login') }}" class="block px-4 py-3 rounded-xl text-base font-medium text-muted hover:text-ink hover:bg-sand transition">Autentificare</a>
                <a href="{{ url('/register') }}" class="btn-primary justify-center mt-2" style="width:100%;">
                    Începe gratuit
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </nav>

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
                <div>© {{ date('Y') }} Sambla · <a href="mailto:servus@sambla.ro" class="hover:text-ink">servus@sambla.ro</a> · <a href="tel:+40775222333" class="hover:text-ink">0775 222 333</a></div>
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
        /* Mobile hamburger menu */
        var btn  = document.getElementById('sbNavToggle');
        var menu = document.getElementById('sbMobileNav');
        var iOpen  = document.getElementById('sbNavIconOpen');
        var iClose = document.getElementById('sbNavIconClose');
        if (btn && menu) {
            btn.addEventListener('click', function(){
                var open = menu.classList.toggle('hidden') === false;
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (iOpen && iClose) {
                    iOpen.classList.toggle('hidden',  open);
                    iClose.classList.toggle('hidden', !open);
                }
                document.body.style.overflow = open ? 'hidden' : '';
            });
            /* Close on any link click */
            menu.querySelectorAll('a').forEach(function(a){
                a.addEventListener('click', function(){
                    menu.classList.add('hidden');
                    btn.setAttribute('aria-expanded', 'false');
                    if (iOpen && iClose) { iOpen.classList.remove('hidden'); iClose.classList.add('hidden'); }
                    document.body.style.overflow = '';
                });
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
