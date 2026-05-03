<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="tenant-id" content="{{ auth()->user()->tenant_id }}">
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <title>@yield('title', 'Dashboard') — Sambla</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #FAF7EF; color: #1C1917; -webkit-font-smoothing: antialiased; }
        .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
        .mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }
        [x-cloak] { display: none !important; }

        /* Sidebar nav items: hover + active state borrowed from preview/v2 */
        .nav-item { position: relative; transition: all .14s ease; }
        .nav-item:hover { background: rgba(255,255,255,0.85); }
        .nav-item.active { background: #FFFFFF; box-shadow: inset 3px 0 0 #DC2626, 0 1px 2px rgba(28,25,23,0.06), 0 0 0 1px rgba(231,224,206,0.6); color: #1C1917; }
        .nav-item:focus-visible { outline: 2px solid #DC2626; outline-offset: -2px; background: #FFFFFF; }

        /* Collapsible sidebar sections */
        details > summary::-webkit-details-marker { display: none; }
        details[open] summary .chev { transform: rotate(90deg); }

        /* Card surface — same as preview/v2 */
        .card { background: #FFFFFF; border: 1px solid #EAE7E0; border-radius: 20px; box-shadow: 0 1px 2px rgba(28,25,23,0.03); transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
        .card:hover { box-shadow: 0 12px 28px -12px rgba(28,25,23,0.10); border-color: #D9D2BD; }

        /* Coral pill button (primary CTA) */
        .btn-coral { background: #DC2626; color: #FAF7EF; transition: all .18s ease; }
        .btn-coral:hover { background: #991B1B; transform: translateY(-1px); box-shadow: 0 10px 24px -6px rgba(220,38,38,0.35); }
        .btn-coral:active { transform: translateY(0); transition-duration: .05s; }

        /* Subtle warm grain — same as marketing */
        body::before { content:''; position:fixed; inset:0; pointer-events:none; opacity:.30; z-index:0;
            background-image: radial-gradient(rgba(28,25,23,0.04) 1px, transparent 1px); background-size: 4px 4px; }

        /* Mobile drawer */
        #sidebar { transition: transform .22s ease; }
        @media (max-width: 1023px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }
    </style>
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')
    @include('partials.analytics.enterprise-tracking')
</head>
<body class="antialiased">
    @include('partials.analytics.body')

    <div class="flex h-screen overflow-hidden relative">

        {{-- Mobile backdrop --}}
        <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-ink/40 hidden lg:hidden" onclick="closeSidebar()"></div>

        {{-- ───────────────────────── Sidebar ───────────────────────── --}}
        <aside id="sidebar" class="fixed lg:relative inset-y-0 left-0 z-40 w-64 bg-cream border-r border-line flex flex-col shrink-0">

            {{-- Brand --}}
            <div class="px-4 py-3 border-b border-line flex items-center justify-between shrink-0">
                <a href="/dashboard" class="flex items-center gap-2.5 group">
                    <svg class="w-7 h-7" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="sgDashLogo" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#991b1b"/>
                                <stop offset="100%" stop-color="#dc2626"/>
                            </linearGradient>
                        </defs>
                        <rect x="0" y="0" width="80" height="80" rx="20" fill="url(#sgDashLogo)"/>
                        <rect x="18" y="28" width="44" height="24" rx="12" fill="#FAF7EF"/>
                        <circle cx="32" cy="40" r="4" fill="#991b1b"/>
                        <circle cx="48" cy="40" r="4" fill="#991b1b"/>
                    </svg>
                    <span class="display text-lg font-semibold tracking-tight">Sambla</span>
                </a>
                <button onclick="closeSidebar()" class="lg:hidden w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition" aria-label="Închide meniul">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Tenant card --}}
            @auth
                @php
                    $tenant = auth()->user()->tenant ?? null;
                    $isSuperAdmin = auth()->user()->hasRole('super_admin');
                    $tenantName = $isSuperAdmin && !$tenant ? 'Super Admin' : ($tenant->name ?? 'Organizația mea');
                    $userName = auth()->user()->name ?? 'Utilizator';
                    $tenantInitials = collect(explode(' ', $tenantName))
                        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->take(2)->join('');
                    $userInitials = collect(explode(' ', $userName))
                        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->take(2)->join('');
                    $userRole = $isSuperAdmin ? 'Super Admin' : (auth()->user()->getRoleNames()->first() ?? 'membru');
                    $userRoleLabel = match($userRole) {
                        'super_admin' => 'Super Admin',
                        'tenant_admin' => 'Admin',
                        'tenant_manager' => 'Manager',
                        'tenant_viewer' => 'Viewer',
                        default => ucfirst(str_replace('_',' ', $userRole)),
                    };
                @endphp
                <div class="px-3 py-3 border-b border-line shrink-0">
                    <div class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-paper border border-line">
                        <div class="w-8 h-8 rounded-lg bg-coralsoft border border-coral/20 flex items-center justify-center text-coralh font-semibold text-sm shrink-0">{{ $tenantInitials ?: 'S' }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm leading-tight truncate" title="{{ $tenantName }}">{{ $tenantName }}</div>
                            <div class="text-2xs text-muted leading-tight mt-0.5 truncate">{{ $userName }} · {{ $userRoleLabel }}</div>
                        </div>
                    </div>
                </div>
            @endauth

            {{-- Main nav --}}
            <nav class="px-2 py-3 flex-1 overflow-y-auto text-sm">

                {{-- Dashboard root --}}
                <a href="/dashboard"
                   class="nav-item flex items-center gap-2.5 px-2.5 py-2 rounded-lg font-medium {{ request()->is('dashboard') && !request()->is('dashboard/*') ? 'active' : 'text-inkSoft' }}">
                    <svg class="w-4 h-4 {{ request()->is('dashboard') && !request()->is('dashboard/*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
                    Dashboard
                </a>

                {{-- Inbox unificat --}}
                <a href="{{ route('dashboard.inbox') }}"
                   class="nav-item flex items-center gap-2.5 px-2.5 py-2 rounded-lg font-medium mt-0.5 {{ request()->is('dashboard/inbox*') ? 'active' : 'text-inkSoft' }}">
                    <svg class="w-4 h-4 {{ request()->is('dashboard/inbox*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
                    Inbox
                </a>

                {{-- ── Workspace ── --}}
                <details open class="mt-4">
                    <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
                        <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        Workspace
                    </summary>
                    <div class="mt-1 space-y-0.5">
                        <a href="/dashboard/agenti"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/agenti*') || request()->is('dashboard/boti*') || request()->is('dashboard/workspace*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/agenti*') || request()->is('dashboard/workspace*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
                            Agenți AI
                        </a>
                        <a href="/dashboard/apeluri"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/apeluri*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/apeluri*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
                            Apeluri
                        </a>
                        @if(!empty($sidebarTranscriptChannels) && count($sidebarTranscriptChannels) > 0)
                            @php $transcriptActive = request()->is('dashboard/transcrieri*'); @endphp
                            <details {{ $transcriptActive ? 'open' : '' }}>
                                <summary class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg cursor-pointer list-none {{ $transcriptActive ? 'active' : 'text-inkSoft' }}">
                                    <svg class="w-4 h-4 {{ $transcriptActive ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14l4-4h12a2 2 0 002-2zM7 8h10M7 12h6"/></svg>
                                    Transcrieri
                                    <svg class="chev w-3 h-3 ml-auto transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                                </summary>
                                <div class="ml-5 mt-0.5 space-y-0.5 border-l border-line pl-3">
                                    @foreach($sidebarTranscriptChannels as $ch)
                                        <a href="{{ $ch['url'] }}"
                                           class="nav-item flex items-center gap-2 px-2 py-1.5 rounded-lg text-2xs {{ request()->is($ch['route_match']) ? 'active' : 'text-inkSoft' }}">
                                            {{ $ch['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                        <a href="/dashboard/leads"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/leads*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/leads*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2v6M12 22v-6M2 12h6M22 12h-6"/><circle cx="12" cy="12" r="3"/></svg>
                            Leads
                        </a>
                        <a href="/dashboard/callbacks"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/callbacks*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/callbacks*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Programări
                        </a>
                        <a href="/dashboard/opportunities"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/opportunities*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/opportunities*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Oportunități
                        </a>
                        <a href="/dashboard/conversii"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/conversii*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/conversii*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-8 4a2 2 0 100-4 2 2 0 000 4z"/></svg>
                            Conversii
                        </a>
                        <a href="/dashboard/analiza"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/analiza*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/analiza*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Analiză
                        </a>
                    </div>
                </details>

                {{-- ── Canale ── --}}
                <details open class="mt-3">
                    <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
                        <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        Canale
                    </summary>
                    <div class="mt-1 space-y-0.5">
                        <a href="/dashboard/sites"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/sites*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/sites*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20 15 15 0 010-20"/></svg>
                            Site-uri
                        </a>
                        <a href="/dashboard/numere"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/numere*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/numere*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
                            Numere telefon
                        </a>
                        <a href="/dashboard/mcp-servere"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/mcp-servere*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/mcp-servere*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                            MCP Servers
                        </a>
                    </div>
                </details>

                {{-- ── Cont ── --}}
                <details class="mt-3">
                    <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
                        <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        Cont
                    </summary>
                    <div class="mt-1 space-y-0.5">
                        <a href="/dashboard/echipa"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/echipa*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/echipa*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                            Echipă
                        </a>
                        <a href="/dashboard/activitate"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/activitate*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/activitate*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Activitate
                        </a>
                        <a href="/dashboard/webhooks"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/webhooks*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/webhooks*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Webhooks
                        </a>
                        <a href="/dashboard/facturare"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/facturare*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/facturare*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            Facturare
                        </a>
                        <a href="/dashboard/setari"
                           class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg {{ request()->is('dashboard/setari*') ? 'active' : 'text-inkSoft' }}">
                            <svg class="w-4 h-4 {{ request()->is('dashboard/setari*') ? 'text-coral' : '' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
                            Setări
                        </a>
                    </div>
                </details>

                @auth
                    @if(auth()->user()->hasRole('super_admin'))
                        <div class="mt-4 pt-3 border-t border-line">
                            <a href="/admin"
                               class="nav-item flex items-center gap-2.5 px-2.5 py-2 rounded-lg font-semibold text-coralh">
                                <svg class="w-4 h-4 text-coral" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12.75L11.25 15 15 9.75M21 12c0 5.59-3.82 10.29-9 11.62-5.18-1.33-9-6.03-9-11.62V5l9-3 9 3z"/></svg>
                                Admin platformă
                            </a>
                        </div>
                    @endif
                @endauth
            </nav>

            {{-- Plan usage block --}}
            @auth
                @php
                    $plan = $tenant->plan ?? 'starter';
                    $planLabels = ['starter' => 'Starter', 'professional' => 'Professional', 'enterprise' => 'Enterprise'];
                    $planLabel = $planLabels[$plan] ?? 'Starter';
                @endphp
                <div class="p-3 border-t border-line shrink-0">
                    <div class="rounded-xl p-3 bg-paper border border-line">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-sm">{{ $planLabel }}</span>
                            @if($plan !== 'enterprise')
                                <a href="/dashboard/facturare" class="text-2xs font-medium text-coralh hover:underline">Upgrade</a>
                            @endif
                        </div>
                        <div class="text-2xs text-muted">{{ $tenantName }}</div>
                    </div>
                </div>
            @endauth
        </aside>

        {{-- ───────────────────────── Main ───────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Sticky topbar --}}
            <header class="h-14 bg-paper/85 backdrop-blur border-b border-line flex items-center justify-between px-4 lg:px-8 shrink-0 gap-3 relative z-10">

                <div class="flex items-center gap-3 text-sm min-w-0">
                    {{-- Hamburger pe mobile --}}
                    <button onclick="toggleSidebar()" class="lg:hidden w-9 h-9 -ml-1.5 rounded-lg hover:bg-cream text-inkSoft flex items-center justify-center transition shrink-0" aria-label="Deschide meniul">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    @auth
                        <span class="text-muted truncate hidden sm:inline">{{ $tenantName }}</span>
                        <span class="text-line hidden sm:inline">/</span>
                    @endauth
                    <span class="font-medium truncate">@yield('title', 'Dashboard')</span>
                </div>

                {{-- Right cluster --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- ⌘K palette trigger — vizibil mereu, dă hint pentru shortcut --}}
                    <button type="button"
                            onclick="window.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', metaKey: true}))"
                            class="hidden md:inline-flex items-center gap-2 px-2.5 py-1 rounded-pill bg-paper border border-line hover:bg-cream text-2xs text-muted transition"
                            aria-label="Deschide command palette">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
                        <span>Caută</span>
                        <span class="mono opacity-60">⌘K</span>
                    </button>
                    @auth
                        {{-- Super-admin: View-as widget --}}
                        @if(auth()->user()->isSuperAdmin())
                            @php
                                $asTenantId = session('admin_as_tenant_id');
                                $viewAll = session('admin_view_all', false);
                                $asTenantName = $asTenantId ? optional(\App\Models\Tenant::find($asTenantId))->name : null;
                                if ($asTenantId && $asTenantName) {
                                    $pillLabel = 'Vezi ca: ' . $asTenantName;
                                    $pillClass = 'bg-peach/40 text-coralh border-peach';
                                } elseif ($viewAll) {
                                    $pillLabel = 'Toți tenanții';
                                    $pillClass = 'bg-sun/40 text-amber-800 border-sun';
                                } else {
                                    $pillLabel = 'Doar eu';
                                    $pillClass = 'bg-cream text-inkSoft border-line';
                                }
                            @endphp
                            <div x-data="viewAsWidget()" class="relative">
                                <button type="button" @click="toggle()" class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill border text-2xs font-medium transition-colors {{ $pillClass }}" :aria-expanded="open">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>{{ $pillLabel }}</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open=false" class="absolute right-0 mt-2 w-[28rem] card overflow-hidden flex flex-col z-50" style="max-height: min(85vh, 44rem);">
                                    <div class="p-3 border-b border-line shrink-0">
                                        <input x-ref="search" type="text" x-model="q" @input.debounce.200ms="search()" placeholder="Caută tenant după nume sau slug…" class="w-full px-3 py-2 text-sm rounded-lg border border-line bg-paper focus:outline-none focus:ring-2 focus:ring-coral/30 focus:border-coral">
                                    </div>
                                    <div class="py-1 text-sm shrink-0">
                                        <button type="button" @click="postStop()" class="w-full text-left px-4 py-2 hover:bg-cream flex items-center justify-between">
                                            <span class="font-medium">Doar eu</span>
                                            @if(!$asTenantId && !$viewAll)<span class="text-coral text-lg leading-none">●</span>@endif
                                        </button>
                                        <button type="button" @click="postAll()" class="w-full text-left px-4 py-2 hover:bg-cream flex items-center justify-between">
                                            <span class="font-medium">Toți tenanții (agregat)</span>
                                            @if($viewAll)<span class="text-amber-600 text-lg leading-none">●</span>@endif
                                        </button>
                                    </div>
                                    <div class="border-t border-line flex-1 overflow-y-auto py-1 text-sm min-h-[12rem]">
                                        <template x-if="loading">
                                            <div class="px-4 py-2 text-muted">Se caută…</div>
                                        </template>
                                        <template x-if="!loading && results.length === 0">
                                            <div class="px-4 py-2 text-muted">Niciun tenant.</div>
                                        </template>
                                        <template x-for="t in results" :key="t.id">
                                            <button type="button" @click="postAs(t.id)" class="w-full text-left px-4 py-2 hover:bg-cream flex items-center justify-between gap-3">
                                                <span class="min-w-0 flex-1 truncate">
                                                    <span class="font-medium" x-text="t.name"></span>
                                                    <span class="text-muted text-xs" x-text="' · ' + (t.slug || '#' + t.id) + ' · ' + (t.plan || 'starter')"></span>
                                                </span>
                                                @if($asTenantId)
                                                    <span class="text-coral text-lg leading-none shrink-0" x-show="t.id === {{ (int) $asTenantId }}">●</span>
                                                @endif
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Notifications --}}
                        <button class="relative w-8 h-8 rounded-lg hover:bg-cream text-muted hover:text-ink flex items-center justify-center transition" aria-label="Notificări">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/></svg>
                            @if(($notificationCount ?? 0) > 0)
                                <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-coral"></span>
                            @endif
                        </button>

                        {{-- User menu --}}
                        <div class="relative" id="user-menu-container">
                            <button id="user-menu-toggle" class="flex items-center gap-2 p-1 rounded-lg hover:bg-cream transition" onclick="toggleUserMenu()" aria-label="Meniu utilizator">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-coral to-coralh text-paper text-2xs font-semibold flex items-center justify-center">{{ $userInitials ?: 'U' }}</div>
                                <svg class="hidden sm:block w-3.5 h-3.5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="user-menu-dropdown" class="hidden absolute right-0 top-full mt-1 w-56 card py-1.5 z-50">
                                <div class="px-4 py-2 border-b border-line">
                                    <div class="text-sm font-semibold truncate">{{ $userName }}</div>
                                    <div class="text-2xs text-muted truncate">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="/dashboard/setari" class="flex items-center gap-2.5 px-4 py-2 text-sm text-inkSoft hover:bg-cream hover:text-ink transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profil
                                </a>
                                <a href="/dashboard/echipa" class="flex items-center gap-2.5 px-4 py-2 text-sm text-inkSoft hover:bg-cream hover:text-ink transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                                    Echipă
                                </a>
                                <div class="my-1 border-t border-line"></div>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-inkSoft hover:bg-cream hover:text-ink transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Deconectare
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- Super-admin impersonation banner --}}
            @auth
                @if(auth()->user()->isSuperAdmin() && session('admin_as_tenant_id'))
                    @php($_banner_tenant = \App\Models\Tenant::find(session('admin_as_tenant_id')))
                    @if($_banner_tenant)
                        <div class="bg-peach/30 text-coralh border-b border-peach px-4 py-2 flex items-center justify-between text-sm shrink-0">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Vezi platforma ca <strong>{{ $_banner_tenant->name }}</strong> (tenant #{{ $_banner_tenant->id }}). Acțiunile tale vor fi atribuite acestui tenant.</span>
                            </div>
                            <form method="POST" action="{{ route('admin.viewAs.stop') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 rounded-pill bg-paper border border-line hover:bg-coralsoft text-coralh text-xs font-medium transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                    Ieși din impersonare
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
            @endauth

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto px-4 lg:px-8 py-6 lg:py-10 relative">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- ⌘K command palette — încarcat global pe dashboard --}}
    @include('partials.command-palette')

    {{-- Sambla AI agent widget (bot #66, channel #2) --}}
    <script src="{{ rtrim(config('app.url'), '/') }}/widget/sambla-chat.min.js" data-channel-id="2" data-bot-name="Sambla Assistant" data-color="#991b1b" data-lang="ro" data-greeting="Salut! 👋 Sunt aici să te ajut cu configurarea platformei. Ce ai nevoie?" async defer></script>

    @stack('scripts')

    <script>
        function toggleSidebar() {
            var sb = document.getElementById('sidebar');
            var ov = document.getElementById('sidebar-overlay');
            if (sb.classList.contains('open')) {
                closeSidebar();
            } else {
                sb.classList.add('open');
                ov.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }
        function closeSidebar() {
            var sb = document.getElementById('sidebar');
            var ov = document.getElementById('sidebar-overlay');
            sb.classList.remove('open');
            ov.classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) closeSidebar();
        });

        function toggleUserMenu() {
            document.getElementById('user-menu-dropdown').classList.toggle('hidden');
        }
        document.addEventListener('click', function(e) {
            var c = document.getElementById('user-menu-container');
            var d = document.getElementById('user-menu-dropdown');
            if (c && !c.contains(e.target)) d.classList.add('hidden');
        });

        function viewAsWidget() {
            return {
                open: false, q: '', results: [], loading: false,
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => this.$refs.search?.focus());
                        if (this.results.length === 0) this.search();
                    }
                },
                async search() {
                    this.loading = true;
                    try {
                        const r = await fetch('/admin/tenants/search?q=' + encodeURIComponent(this.q), { headers: { 'Accept': 'application/json' } });
                        const d = await r.json();
                        this.results = d.results || [];
                    } catch (e) { this.results = []; }
                    finally { this.loading = false; }
                },
                _submit(url) {
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = url;
                    const t = document.createElement('input');
                    t.type = 'hidden'; t.name = '_token'; t.value = this.csrf;
                    f.appendChild(t); document.body.appendChild(f); f.submit();
                },
                postAs(id) { this._submit('/admin/view-as/' + id); },
                postStop() { this._submit('/admin/view-as/stop'); },
                postAll() { this._submit('/dashboard/toggle-admin-view'); },
            };
        }
    </script>
    @include('partials.analytics.consent-widget')
</body>
</html>
