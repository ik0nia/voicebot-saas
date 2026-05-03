<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Sambla Admin</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#dc2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sambla Admin">
    <link rel="apple-touch-icon" href="/images/logo-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #FAF7EF; color: #1C1917; -webkit-font-smoothing: antialiased; }
        .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
        .mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }
        [x-cloak] { display: none !important; }

        .nav-item { position: relative; transition: all .14s ease; }
        .nav-item:hover { background: rgba(255,255,255,0.85); }
        .nav-item.active { background: #FFFFFF; box-shadow: inset 3px 0 0 #DC2626, 0 1px 2px rgba(28,25,23,0.06), 0 0 0 1px rgba(231,224,206,0.6); color: #1C1917; }
        .nav-item:focus-visible { outline: 2px solid #DC2626; outline-offset: -2px; background: #FFFFFF; }

        .card { background: #FFFFFF; border: 1px solid #EAE7E0; border-radius: 20px; box-shadow: 0 1px 2px rgba(28,25,23,0.03); }
        .btn-coral { background: #DC2626; color: #FAF7EF; transition: all .18s ease; }
        .btn-coral:hover { background: #991B1B; transform: translateY(-1px); box-shadow: 0 10px 24px -6px rgba(220,38,38,0.35); }

        body::before { content:''; position:fixed; inset:0; pointer-events:none; opacity:.30; z-index:0;
            background-image: radial-gradient(rgba(28,25,23,0.04) 1px, transparent 1px); background-size: 4px 4px; }

        #sidebar { transition: transform .22s ease; }
        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }
    </style>
    @stack('styles')
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')
    @include('partials.analytics.enterprise-tracking')
</head>
<body class="antialiased">
    @include('partials.analytics.body')

    {{-- Admin badge strip — semnal vizual că ești în modul super_admin --}}
    <div class="bg-ink text-cream text-2xs px-4 py-1.5 flex items-center justify-between gap-3 sticky top-0 z-30">
        <div class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-coral"></span>
            <strong>Admin platformă</strong>
            <span class="text-cream/60 hidden sm:inline">· acces super_admin</span>
        </div>
        <a href="/dashboard" class="text-cream/80 hover:text-cream underline underline-offset-2">Înapoi la dashboard →</a>
    </div>

    <div class="flex h-[calc(100vh-29px)] overflow-hidden relative">

        <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-ink/40 hidden lg:hidden" onclick="closeSidebar()"></div>

        <aside id="sidebar" class="fixed lg:relative inset-y-0 left-0 z-40 w-64 bg-cream border-r border-line flex flex-col shrink-0">

            <div class="px-4 py-3 border-b border-line flex items-center justify-between shrink-0">
                <a href="/admin" class="flex items-center gap-2.5">
                    <svg class="w-7 h-7" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="sgAdminLogo" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1C1917"/>
                                <stop offset="100%" stop-color="#3A3532"/>
                            </linearGradient>
                        </defs>
                        <rect x="0" y="0" width="80" height="80" rx="20" fill="url(#sgAdminLogo)"/>
                        <rect x="18" y="28" width="44" height="24" rx="12" fill="#FAF7EF"/>
                        <circle cx="32" cy="40" r="4" fill="#DC2626"/>
                        <circle cx="48" cy="40" r="4" fill="#DC2626"/>
                    </svg>
                    <div class="flex flex-col leading-none">
                        <span class="display text-base font-semibold tracking-tight">Sambla</span>
                        <span class="text-2xs font-mono tracking-wider text-coralh uppercase mt-0.5">admin</span>
                    </div>
                </a>
                <button onclick="closeSidebar()" class="lg:hidden w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition" aria-label="Închide meniul">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="px-2 py-3 flex-1 overflow-y-auto text-sm">
                @php
                    $adminLinks = [
                        ['url' => '/admin',                  'label' => 'Dashboard',          'match' => 'admin', 'exact' => true,
                            'svg' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
                        ['url' => '/admin/tenanti',          'label' => 'Tenanți',            'match' => 'admin/tenanti*',
                            'svg' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5'],
                        ['url' => '/admin/boti',             'label' => 'Agenți AI',          'match' => 'admin/boti*',
                            'svg' => 'M12 2a3 3 0 013 3v4a3 3 0 11-6 0V5a3 3 0 013-3zm0 14a4 4 0 00-4 4v1h8v-1a4 4 0 00-4-4z'],
                        ['url' => '/admin/apeluri',          'label' => 'Apeluri',            'match' => 'admin/apeluri*',
                            'svg' => 'M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z'],
                        ['url' => '/admin/conversatii',      'label' => 'Conversații',        'match' => 'admin/conversatii*',
                            'svg' => 'M21 15V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14l4-4h12a2 2 0 002-2zM7 8h10M7 12h6'],
                        ['url' => '/admin/lead-uri',         'label' => 'Lead-uri SaaS',      'match' => 'admin/lead-uri*',
                            'svg' => 'M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'],
                        ['url' => '/admin/demo',             'label' => 'Demo funnel',        'match' => 'admin/demo*',
                            'svg' => 'M11 5l-3 3-3-3M11 19l-3-3-3 3M21 5l-3 3-3-3M21 19l-3-3-3 3'],
                    ];
                    $adminMonetization = [
                        ['url' => '/admin/venituri',         'label' => 'Venituri',           'match' => 'admin/venituri*',
                            'svg' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['url' => '/admin/costuri',          'label' => 'Costuri',            'match' => 'admin/costuri*',
                            'svg' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['url' => '/admin/rapoarte',         'label' => 'Rapoarte',           'match' => 'admin/rapoarte*',
                            'svg' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2'],
                        ['url' => '/admin/pachete',          'label' => 'Pachete',            'match' => 'admin/pachete*',
                            'svg' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['url' => '/admin/preturi-modele',   'label' => 'Prețuri modele AI',  'match' => 'admin/preturi-modele*',
                            'svg' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['url' => '/admin/twilio/consum',    'label' => 'Consum Twilio',      'match' => 'admin/twilio*',
                            'svg' => 'M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z'],
                    ];
                    $adminContent = [
                        ['url' => '/admin/social',           'label' => 'Social media',       'match' => 'admin/social*',
                            'svg' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                        ['url' => '/admin/niches',           'label' => 'Nișe',               'match' => 'admin/niches*',
                            'svg' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                        ['url' => '/admin/setari',           'label' => 'Setări platformă',   'match' => 'admin/setari*',
                            'svg' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0'],
                        ['url' => '/admin/system',           'label' => 'Sistem',             'match' => 'admin/system*',
                            'svg' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2'],
                    ];
                    $adminGroups = [
                        'Operațional' => $adminLinks,
                        'Monetizare' => $adminMonetization,
                        'Conținut & sistem' => $adminContent,
                    ];
                @endphp

                @foreach($adminGroups as $groupTitle => $links)
                    <details open class="{{ $loop->first ? '' : 'mt-3' }}">
                        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
                            <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            {{ $groupTitle }}
                        </summary>
                        <div class="mt-1 space-y-0.5">
                            @foreach($links as $link)
                                @php
                                    $isActive = isset($link['exact']) && $link['exact']
                                        ? request()->is($link['url'] ? ltrim($link['url'], '/') : 'admin') && !request()->is(trim($link['url'], '/') . '/*')
                                        : request()->is($link['match']);
                                @endphp
                                <a href="{{ $link['url'] }}"
                                   class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ $isActive ? 'active' : 'text-inkSoft' }}">
                                    <svg class="w-4 h-4 {{ $isActive ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="{{ $link['svg'] }}"/></svg>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach

                <style>details > summary::-webkit-details-marker { display: none; } details[open] summary .chev { transform: rotate(90deg); }</style>
            </nav>

            <div class="p-3 border-t border-line shrink-0">
                <a href="/dashboard" class="block w-full px-3 py-2 rounded-xl bg-paper border border-line text-center text-2xs font-medium text-inkSoft hover:bg-cream hover:text-ink transition">
                    ← Înapoi la dashboard
                </a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="h-14 bg-paper/85 backdrop-blur border-b border-line flex items-center justify-between px-4 lg:px-8 shrink-0 gap-3 relative z-10">
                <div class="flex items-center gap-3 text-sm min-w-0">
                    <button onclick="toggleSidebar()" class="lg:hidden w-9 h-9 -ml-1.5 rounded-lg hover:bg-cream text-inkSoft flex items-center justify-center transition shrink-0" aria-label="Deschide meniul">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <span class="text-muted hidden sm:inline">Admin</span>
                    <span class="text-line hidden sm:inline">/</span>
                    <span class="font-medium truncate">@yield('title', 'Dashboard')</span>
                </div>

                <div class="flex items-center gap-2">
                    @auth
                        @php
                            $adminUserName = auth()->user()->name ?? 'Admin';
                            $adminInitials = collect(explode(' ', $adminUserName))
                                ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                ->take(2)->join('');
                        @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-ink to-inkSoft text-cream text-2xs font-semibold flex items-center justify-center">{{ $adminInitials }}</div>
                            <span class="hidden sm:block text-sm font-medium">{{ $adminUserName }}</span>
                        </div>
                        <form method="POST" action="/logout" class="ml-2">
                            @csrf
                            <button type="submit" class="w-8 h-8 rounded-lg hover:bg-cream text-muted hover:text-ink flex items-center justify-center transition" aria-label="Deconectare">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <main class="flex-1 overflow-y-auto px-4 lg:px-8 py-6 lg:py-10 relative">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
        function toggleSidebar() {
            var sb = document.getElementById('sidebar');
            var ov = document.getElementById('sidebar-overlay');
            if (sb.classList.contains('open')) closeSidebar();
            else { sb.classList.add('open'); ov.classList.remove('hidden'); document.body.style.overflow='hidden'; }
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.add('hidden');
            document.body.style.overflow='';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
        window.addEventListener('resize', () => { if (window.innerWidth >= 1024) closeSidebar(); });

        // PWA — register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch((err) => console.warn('SW registration failed:', err));
            });
        }
    </script>
    @include('partials.analytics.consent-widget')
</body>
</html>
