<!DOCTYPE html>
<html lang="ro" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VoiceBot - AI-Powered Voice Agents</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-900 text-white antialiased min-h-screen">

    {{-- Navbar --}}
    <nav class="fixed top-0 w-full z-50 bg-slate-900/80 backdrop-blur-lg border-b border-slate-700/50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <span class="text-xl font-extrabold tracking-tight">Voice<span class="text-violet-400">Bot</span></span>
            <div class="flex items-center gap-4">
                @auth
                    <a href="/dashboard" class="text-sm font-medium text-slate-300 hover:text-white transition">Dashboard</a>
                @else
                    <a href="/login" class="text-sm font-medium text-slate-400 hover:text-white transition">Login</a>
                    <a href="/register" class="text-sm font-medium bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg transition">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-36 pb-24 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight leading-tight mb-6">
                Agenți vocali <span class="gradient-text">AI</span> pentru afacerea ta
            </h1>
            <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Automatizează apelurile cu clienții 24/7. Conversații naturale, integrare rapidă, rezultate măsurabile.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="bg-violet-600 hover:bg-violet-500 text-white font-semibold px-8 py-3.5 rounded-xl transition text-lg">
                    Începe acum
                </a>
                <a href="#features" class="border border-slate-600 hover:border-slate-400 text-slate-300 hover:text-white font-semibold px-8 py-3.5 rounded-xl transition text-lg">
                    Vezi funcționalități
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="pb-24 px-6">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4">Tot ce ai nevoie</h2>
            <p class="text-slate-400 text-center mb-14 max-w-xl mx-auto">O platformă completă pentru agenți vocali inteligenți.</p>
            <div class="grid md:grid-cols-3 gap-6">

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">🎙</div>
                    <h3 class="text-lg font-semibold mb-2">Conversații naturale</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Agenți vocali care înțeleg contextul, gestionează obiecții și finalizează vânzări.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">📞</div>
                    <h3 class="text-lg font-semibold mb-2">Management apeluri</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Monitorizare live, transcrieri și analiză a performanței în timp real.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">📊</div>
                    <h3 class="text-lg font-semibold mb-2">Analiză avansată</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Rată de conversie, durată apeluri, analiza sentimentului și metrici ROI.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">👥</div>
                    <h3 class="text-lg font-semibold mb-2">Echipă & roluri</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Invită membrii echipei, atribuie roluri și colaborează pe configurații.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">🔒</div>
                    <h3 class="text-lg font-semibold mb-2">Securitate enterprise</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Criptare end-to-end, control acces bazat pe roluri, conformitate deplină.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700/50 hover:border-violet-500/40 transition">
                    <div class="w-10 h-10 rounded-lg bg-violet-600/20 flex items-center justify-center mb-4 text-violet-400 text-lg font-bold">⚡</div>
                    <h3 class="text-lg font-semibold mb-2">Deploy instant</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">De la configurare la apeluri live în câteva minute. Fără cod necesar.</p>
                </div>

            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="pb-24 px-6">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4">Prețuri simple</h2>
            <p class="text-slate-400 text-center mb-14">Alege planul potrivit pentru afacerea ta.</p>
            <div class="grid md:grid-cols-3 gap-6">

                {{-- Starter --}}
                <div class="p-8 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                    <h3 class="text-lg font-semibold mb-1">Starter</h3>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-4xl font-bold">99€</span>
                        <span class="text-slate-500">/lună</span>
                    </div>
                    <p class="text-sm text-slate-400 mb-6">500 minute incluse</p>
                    <ul class="space-y-3 mb-8 text-sm text-slate-300">
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 1 bot vocal</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 500 minute / lună</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 2 membri echipă</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Suport email</li>
                    </ul>
                    <a href="/register" class="block text-center py-3 rounded-xl font-semibold border border-slate-600 hover:border-slate-400 text-slate-300 hover:text-white transition">
                        Începe acum
                    </a>
                </div>

                {{-- Pro --}}
                <div class="p-8 rounded-2xl bg-violet-600/10 border border-violet-500/30 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-violet-600 text-white text-xs font-bold px-3 py-1 rounded-full">Popular</div>
                    <h3 class="text-lg font-semibold mb-1">Pro</h3>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-4xl font-bold">299€</span>
                        <span class="text-slate-500">/lună</span>
                    </div>
                    <p class="text-sm text-slate-400 mb-6">2000 minute incluse</p>
                    <ul class="space-y-3 mb-8 text-sm text-slate-300">
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 10 boți vocali</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 2000 minute / lună</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> 10 membri echipă</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Suport prioritar</li>
                    </ul>
                    <a href="/register" class="block text-center py-3 rounded-xl font-semibold bg-violet-600 hover:bg-violet-500 text-white transition">
                        Începe acum
                    </a>
                </div>

                {{-- Enterprise --}}
                <div class="p-8 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                    <h3 class="text-lg font-semibold mb-1">Enterprise</h3>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-4xl font-bold">Custom</span>
                    </div>
                    <p class="text-sm text-slate-400 mb-6">Minute nelimitate</p>
                    <ul class="space-y-3 mb-8 text-sm text-slate-300">
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Boți nelimitați</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Minute nelimitate</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Echipă nelimitată</li>
                        <li class="flex items-center gap-2"><span class="text-violet-400">✓</span> Suport dedicat</li>
                    </ul>
                    <a href="/register" class="block text-center py-3 rounded-xl font-semibold border border-slate-600 hover:border-slate-400 text-slate-300 hover:text-white transition">
                        Contactează-ne
                    </a>
                </div>

            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-700/50 py-10 px-6">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <span class="text-sm font-bold">Voice<span class="text-violet-400">Bot</span></span>
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} VoiceBot by Ikonia. Toate drepturile rezervate.</p>
        </div>
    </footer>

</body>
</html>
