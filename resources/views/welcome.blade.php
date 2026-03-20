<!DOCTYPE html>
<html lang="en" class="dark">
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glow {
            box-shadow: 0 0 60px rgba(99, 102, 241, 0.15);
        }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-zinc-950 text-white antialiased min-h-screen">
    {{-- Nav --}}
    <nav class="fixed top-0 w-full z-50 border-b border-zinc-800/50 bg-zinc-950/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <span class="text-lg font-bold">VoiceBot</span>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    <a href="/dashboard" class="text-sm font-medium text-zinc-300 hover:text-white transition">Dashboard</a>
                @else
                    <a href="/login" class="text-sm font-medium text-zinc-400 hover:text-white transition">Sign In</a>
                    <a href="/register" class="text-sm font-medium bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg transition">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-32 pb-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-sm mb-8">
                <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                Platform Active — v1.0
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight leading-tight mb-6">
                Build <span class="gradient-text">AI Voice Agents</span> That Convert
            </h1>
            <p class="text-xl text-zinc-400 max-w-2xl mx-auto mb-10">
                Deploy intelligent voice bots that handle customer calls 24/7.
                Powered by advanced AI, integrated with your existing tools.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-8 py-3.5 rounded-xl transition text-lg">
                    Start Free Trial
                </a>
                <a href="#features" class="border border-zinc-700 hover:border-zinc-500 text-zinc-300 hover:text-white font-semibold px-8 py-3.5 rounded-xl transition text-lg">
                    See Features
                </a>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="pb-20 px-6">
        <div class="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach([
                ['99.9%', 'Uptime SLA'],
                ['< 200ms', 'Response Time'],
                ['24/7', 'Availability'],
                ['50+', 'Integrations'],
            ] as [$value, $label])
            <div class="text-center p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800/50 glow">
                <div class="text-3xl font-bold text-white mb-1">{{ $value }}</div>
                <div class="text-sm text-zinc-500">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="pb-20 px-6">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4">Everything You Need</h2>
            <p class="text-zinc-400 text-center mb-12 max-w-xl mx-auto">Build, deploy, and manage AI voice agents with a complete platform.</p>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach([
                    ['M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z', 'AI Voice Agents', 'Natural conversations powered by GPT. Your bots understand context, handle objections, and close deals.'],
                    ['M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'Call Management', 'Monitor live calls, review transcripts, and analyze performance in real-time.'],
                    ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'Analytics', 'Track conversion rates, call duration, sentiment analysis, and ROI metrics.'],
                    ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'Team Management', 'Invite team members, assign roles, and collaborate on bot configurations.'],
                    ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'Enterprise Security', 'SOC 2 compliant. End-to-end encryption. Role-based access control.'],
                    ['M13 10V3L4 14h7v7l9-11h-7z', 'Instant Deploy', 'Go from configuration to live calls in minutes. No coding required.'],
                ] as [$icon, $title, $desc])
                <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800/50 hover:border-indigo-500/30 transition group">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600/10 flex items-center justify-center mb-4 group-hover:bg-indigo-600/20 transition">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">{{ $title }}</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="pb-20 px-6">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4">Simple Pricing</h2>
            <p class="text-zinc-400 text-center mb-12">Start free. Scale as you grow.</p>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach(config('plans') as $key => $plan)
                <div class="p-8 rounded-2xl border {{ $key === 'pro' ? 'bg-indigo-600/5 border-indigo-500/30' : 'bg-zinc-900/50 border-zinc-800/50' }}">
                    <h3 class="text-lg font-semibold mb-1">{{ $plan['name'] }}</h3>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-bold">${{ number_format($plan['price_monthly'] / 100) }}</span>
                        <span class="text-zinc-500">/mo</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-sm text-zinc-300">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ $plan['features']['bots'] == -1 ? 'Unlimited' : $plan['features']['bots'] }} {{ Str::plural('bot', $plan['features']['bots'] == -1 ? 2 : $plan['features']['bots']) }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-300">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ $plan['features']['calls_per_month'] == -1 ? 'Unlimited' : number_format($plan['features']['calls_per_month']) }} calls/mo
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-300">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ $plan['features']['team_members'] == -1 ? 'Unlimited' : $plan['features']['team_members'] }} team {{ Str::plural('member', $plan['features']['team_members'] == -1 ? 2 : $plan['features']['team_members']) }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-300">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ ucfirst($plan['features']['support']) }} support
                        </li>
                    </ul>
                    <a href="/register" class="block text-center py-3 rounded-xl font-semibold transition {{ $key === 'pro' ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : 'border border-zinc-700 hover:border-zinc-500 text-zinc-300 hover:text-white' }}">
                        {{ $key === 'enterprise' ? 'Contact Sales' : 'Get Started' }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-800/50 py-12 px-6">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded bg-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <span class="text-sm font-semibold">VoiceBot</span>
            </div>
            <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} VoiceBot by Ikonia. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
