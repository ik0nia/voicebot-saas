<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Sambla</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        {{-- Mobile sidebar overlay --}}
        <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden" onclick="closeSidebar()"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-[260px] bg-white border-r border-slate-200 flex flex-col transform -translate-x-full lg:translate-x-0 lg:static lg:z-auto transition-transform duration-200 ease-in-out">

            {{-- Red accent strip at top --}}
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

            {{-- Logo --}}
            <div class="flex items-center h-16 px-6 border-b border-slate-200 shrink-0">
                <a href="/dashboard" class="flex items-center">
                    <img src="/images/logo-light.svg" alt="Sambla" class="h-10 w-auto">
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                {{-- Dashboard --}}
                <a href="/dashboard"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard') && !request()->is('dashboard/*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
                    </svg>
                    Dashboard
                </a>

                {{-- Boți --}}
                <a href="/dashboard/boti"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/boti*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                    </svg>
                    Boți
                </a>

                {{-- Sites --}}
                <a href="/dashboard/sites"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/sites*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    Sites
                </a>

                {{-- Transcrieri --}}
                @if(!empty($sidebarTranscriptChannels) && count($sidebarTranscriptChannels) > 0)
                    @php
                        $transcriptActive = request()->is('dashboard/apeluri*') || request()->is('dashboard/transcrieri*');
                    @endphp
                    <div>
                        <button onclick="document.getElementById('transcript-submenu').classList.toggle('hidden'); document.getElementById('transcript-chevron').classList.toggle('rotate-180');" type="button"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                       {{ $transcriptActive ? 'bg-red-900/20 text-white' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Transcrieri
                            </span>
                            <svg id="transcript-chevron" class="w-4 h-4 shrink-0 transition-transform {{ $transcriptActive ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div id="transcript-submenu" class="ml-5 mt-0.5 space-y-0.5 border-l-2 border-red-200 pl-3 {{ $transcriptActive ? '' : 'hidden' }}">
                            @foreach($sidebarTranscriptChannels as $ch)
                                <a href="{{ $ch['url'] }}"
                                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                          {{ request()->is($ch['route_match']) ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-500 hover:bg-red-50 hover:text-red-800' }}">
                                    @if($ch['icon'] === 'phone')
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    @elseif($ch['icon'] === 'globe')
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    @elseif($ch['icon'] === 'message-circle')
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    @elseif($ch['icon'] === 'facebook')
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    @elseif($ch['icon'] === 'instagram')
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <rect x="2" y="2" width="20" height="20" rx="5" />
                                            <circle cx="12" cy="12" r="5" />
                                            <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    @endif
                                    {{ $ch['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- V2: Programări --}}
                <a href="/dashboard/callbacks"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/callbacks*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Programări
                </a>

                {{-- V2: Leads --}}
                <a href="/dashboard/leads"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/leads*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Leads
                </a>

                {{-- V2: Oportunități --}}
                <a href="/dashboard/opportunities"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/opportunities*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Oportunități
                </a>

                {{-- V2: Conversii --}}
                <a href="/dashboard/conversii"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/conversii*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                    Conversii
                </a>

                {{-- Analiză --}}
                <a href="/dashboard/analiza"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/analiza*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Analiză
                </a>

                {{-- Numere Telefon --}}
                <a href="/dashboard/numere"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/numere*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                    Numere Telefon
                </a>

                {{-- Echipă --}}
                <a href="/dashboard/echipa"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/echipa*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Echipă
                </a>

                @if(auth()->user()->hasRole('super_admin'))
                {{-- Super Admin Section --}}
                <div class="my-3 border-t border-slate-200"></div>
                <p class="px-3 py-1 text-xs font-semibold text-red-700 uppercase tracking-wider">Admin Platforma</p>

                <a href="/admin"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-red-700 to-red-600 text-white hover:from-red-800 hover:to-red-700 transition-all shadow-sm shadow-red-700/20">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    Admin Panel
                </a>

                @endif

                {{-- Separator --}}
                <div class="my-3 border-t border-slate-200"></div>

                {{-- Facturare --}}
                <a href="/dashboard/facturare"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/facturare*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Facturare
                </a>

                {{-- Setări --}}
                <a href="/dashboard/setari"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('dashboard/setari*') ? 'bg-red-50 text-red-800 border-l-[3px] border-red-700 pl-[9px] font-semibold' : 'text-slate-600 hover:bg-red-50 hover:text-red-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Setări
                </a>
            </nav>

            {{-- Tenant info at bottom --}}
            <div class="shrink-0 border-t border-slate-200 px-4 py-4">
                @auth
                    @php
                        $tenant = auth()->user()->tenant ?? null;
                        $isSuperAdmin = auth()->user()->hasRole('super_admin');
                        $plan = $tenant->plan ?? 'starter';
                        $tenantName = $isSuperAdmin && !$tenant ? 'Super Admin' : ($tenant->name ?? 'Organizația mea');
                        $planLabels = ['starter' => 'Starter', 'professional' => 'Profesional', 'enterprise' => 'Enterprise'];
                        $planColors = [
                            'starter' => 'bg-white/10 text-slate-300',
                            'professional' => 'bg-red-900/30 text-red-300',
                            'enterprise' => 'bg-red-900/30 text-red-300',
                        ];
                    @endphp
                    @php
                        $planColorsLight = [
                            'starter' => 'bg-slate-100 text-slate-600',
                            'professional' => 'bg-red-100 text-red-700',
                            'enterprise' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $tenantName }}</p>
                            @if($isSuperAdmin && !$tenant)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1 bg-red-100 text-red-700">
                                Platforma
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1 {{ $planColorsLight[$plan] ?? $planColorsLight['starter'] }}">
                                {{ $planLabels[$plan] ?? 'Starter' }}
                            @endif
                            </span>
                        </div>
                        @if(($plan ?? 'starter') !== 'enterprise')
                            <a href="/dashboard/facturare"
                               class="shrink-0 text-xs font-semibold text-red-700 hover:text-red-900 transition-colors">
                                Upgrade
                            </a>
                        @endif
                    </div>
                @endauth
            </div>

        </aside>

        {{-- Main content area --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
                {{-- Left: hamburger + breadcrumb --}}
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button id="sidebar-toggle" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors" onclick="toggleSidebar()">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Breadcrumb --}}
                    <div class="text-sm text-slate-500">
                        @yield('breadcrumb')
                    </div>
                </div>

                {{-- Right: admin toggle + notifications + user --}}
                <div class="flex items-center gap-2">
                    {{-- Super Admin: View All Toggle --}}
                    @if(auth()->user()->isSuperAdmin())
                    <form method="POST" action="{{ route('dashboard.toggleAdminView') }}" class="flex items-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ session('admin_view_all', false) ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" title="{{ session('admin_view_all', false) ? 'Vizualizezi toate datele' : 'Vizualizezi doar datele tale' }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            {{ session('admin_view_all', false) ? 'Toti' : 'Doar eu' }}
                        </button>
                    </form>
                    @endif

                    {{-- Notifications --}}
                    <button class="relative p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {{-- Badge count - show conditionally --}}
                        @if(($notificationCount ?? 0) > 0)
                            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                            </span>
                        @endif
                    </button>

                    {{-- User menu --}}
                    <div class="relative" id="user-menu-container">
                        <button id="user-menu-toggle" class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-100 transition-colors" onclick="toggleUserMenu()">
                            @auth
                                @php
                                    $userName = auth()->user()->name ?? 'Utilizator';
                                    $initials = collect(explode(' ', $userName))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
                                @endphp
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <span class="text-xs font-semibold text-red-800">{{ $initials }}</span>
                                </div>
                                <span class="hidden sm:block text-sm font-medium text-slate-700 max-w-[140px] truncate">{{ $userName }}</span>
                                <svg class="hidden sm:block w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            @endauth
                        </button>

                        {{-- Dropdown --}}
                        <div id="user-menu-dropdown" class="hidden absolute right-0 top-full mt-1 w-48 bg-white rounded-xl border border-slate-200 shadow-lg py-1.5 z-50">
                            <a href="/dashboard/setari" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profil
                            </a>
                            <div class="my-1 border-t border-slate-100"></div>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Deconectare
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Chatbot widget: Sambla - Asistent Dashboard (bot 35, channel 3) --}}
    <script src="{{ rtrim(config('app.url'), '/') }}/api/v1/chatbot/embed" data-channel-id="3" async defer></script>

    @stack('scripts')

    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            var isOpen = !sidebar.classList.contains('-translate-x-full');
            if (isOpen) {
                closeSidebar();
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // User dropdown toggle
        function toggleUserMenu() {
            var dropdown = document.getElementById('user-menu-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            var container = document.getElementById('user-menu-container');
            var dropdown = document.getElementById('user-menu-dropdown');
            if (container && !container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Close sidebar on window resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>
