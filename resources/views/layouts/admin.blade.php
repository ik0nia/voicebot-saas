<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Sambla Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden" onclick="closeSidebar()"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-[260px] bg-slate-900 border-r border-slate-800 flex flex-col transform -translate-x-full lg:translate-x-0 lg:static lg:z-auto transition-transform duration-200 ease-in-out">

            <div class="flex items-center h-16 px-6 border-b border-slate-800 shrink-0">
                <a href="/admin" class="flex items-center gap-2.5">
                    <img src="/images/logo-dark.svg" alt="Sambla" class="h-10 w-auto">
                    <span class="text-[10px] font-semibold text-red-400 uppercase tracking-wider">Admin</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @php
                    $adminLinks = [
                        ['url' => '/admin', 'label' => 'Dashboard', 'match' => 'admin', 'exact' => true, 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z'],
                        ['url' => '/admin/boti', 'label' => 'Boti', 'match' => 'admin/boti*', 'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6'],
                        ['url' => '/admin/apeluri', 'label' => 'Apeluri', 'match' => 'admin/apeluri*', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                        ['url' => '/admin/conversatii', 'label' => 'Conversatii', 'match' => 'admin/conversatii*', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                        ['url' => '/admin/tenanti', 'label' => 'Tenanti', 'match' => 'admin/tenanti*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['url' => '/admin/preturi-modele', 'label' => 'Prețuri Modele AI', 'match' => 'admin/preturi-modele*', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['url' => '/admin/pachete', 'label' => 'Pachete & Prețuri', 'match' => 'admin/pachete*', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['url' => '/admin/setari', 'label' => 'Setari Platforma', 'match' => 'admin/setari*', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0'],
                        ['url' => '/admin/system', 'label' => 'Sistem', 'match' => 'admin/system*', 'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'],
                        ['url' => '/admin/rapoarte', 'label' => 'Rapoarte', 'match' => 'admin/rapoarte*', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp
                @foreach($adminLinks as $link)
                    @php
                        $isActive = isset($link['exact']) ? request()->is($link['match']) : request()->is($link['match']);
                    @endphp
                    <a href="{{ $link['url'] }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $isActive ? 'bg-red-600/20 text-red-400 border-l-[3px] border-red-500 pl-[9px]' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <div class="my-4 border-t border-slate-800"></div>

                <a href="/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/></svg>
                    Inapoi la Dashboard
                </a>
            </nav>

            <div class="shrink-0 border-t border-slate-800 px-4 py-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-sm font-medium text-slate-400">Super Admin</span>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100" onclick="toggleSidebar()">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="text-sm text-slate-500">@yield('breadcrumb')</div>
                </div>
                <div class="flex items-center gap-2">
                    @auth
                        @php $userName = auth()->user()->name ?? 'Admin'; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                <span class="text-xs font-semibold text-red-800">{{ mb_strtoupper(mb_substr($userName, 0, 1)) }}</span>
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-slate-700">{{ $userName }}</span>
                        </div>
                        <form method="POST" action="/logout" class="ml-2">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></button>
                        </form>
                    @endauth
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    </script>
</body>
</html>
