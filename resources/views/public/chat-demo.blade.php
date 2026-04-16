<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo {{ $bot->name }} — Sambla</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 font-sans antialiased min-h-screen flex flex-col">

    {{-- Simple header (matches voice demo) --}}
    <header class="bg-white border-b border-slate-200 py-3 px-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-2" aria-label="Sambla - pagina principala">
                <svg width="28" height="28" viewBox="0 0 36 36" fill="none" class="shrink-0" aria-hidden="true">
                    <rect width="36" height="36" rx="8" fill="#991b1b"/>
                    <path d="M18 6L28 18L18 30L8 18Z" fill="white" fill-opacity="0.15"/>
                    <path d="M18 10L24 18L18 26L12 18Z" fill="white" fill-opacity="0.3"/>
                    <path d="M18 14L20.5 18L18 22L15.5 18Z" fill="white"/>
                </svg>
                <span class="text-lg font-extrabold text-slate-900">Sambla</span>
            </a>
            <span class="text-xs text-slate-400 hidden sm:block">Demo Agent AI</span>
        </div>
    </header>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8" role="main">

        {{-- Bot info --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Conversație text în timp real cu agentul AI</p>
        </div>

        {{-- Phone Simulator --}}
        <div class="w-full max-w-sm">
            <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl shadow-slate-900/30">
                {{-- Notch --}}
                <div class="flex justify-center mb-1">
                    <div class="w-24 h-5 bg-slate-800 rounded-full"></div>
                </div>

                <div class="bg-white rounded-[2rem] overflow-hidden flex flex-col" style="height: 620px;">
                    {{-- Chat header bar (brand colored) --}}
                    <div class="shrink-0 flex items-center gap-3 px-4 py-3 text-white" style="background: linear-gradient(135deg, #991b1b, #dc2626);">
                        <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate">{{ $bot->name }}</div>
                            <div class="flex items-center gap-1.5 text-[11px] text-white/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>
                                Online
                            </div>
                        </div>
                    </div>

                    {{-- Chat iframe --}}
                    <iframe
                        src="{{ route('agent AI.frame', $channel->id) }}"
                        class="flex-1 w-full border-0"
                        title="{{ $bot->name }} Chat"
                        allow="microphone"
                        sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
                </div>
            </div>
        </div>

        {{-- Footer blurb --}}
        <p class="text-xs text-slate-400 mt-6 text-center">
            Powered by <a href="/" class="text-slate-500 hover:text-slate-700 font-medium">Sambla</a> — Agenți AI pentru afacerea ta
        </p>
    </main>
</body>
</html>
