@php
    $hasPrompt = !empty($bot->system_prompt);
    $hasKnowledge = ($kbStats['total_documents'] ?? 0) > 0;
    $hasWordpress = isset($wcConnector) && $wcConnector;
    $setupComplete = $hasPrompt && $hasKnowledge && $hasWordpress;

    $score = $healthScore['score'] ?? 0;
    $grade = $healthScore['grade'] ?? 'F';
    $suggestions = $healthScore['suggestions'] ?? [];
    $activeChannels = $bot->channels->where('is_active', true)->count();
    $kbDocs = $kbStats['total_documents'] ?? 0;
    $convMonth = $conversationsThisMonth ?? 0;
    $convTotal = $conversationsTotal ?? 0;

    $toneMap = ['professional' => 'Profesional', 'friendly' => 'Prietenos', 'casual' => 'Relaxat', 'technical' => 'Tehnic'];
    $verbMap = ['concise' => 'Scurt', 'detailed' => 'Detaliat', 'verbose' => 'Elaborat'];
@endphp

<div class="space-y-5">

    {{-- ========== HERO CARD ========== --}}
    <div class="relative rounded-2xl overflow-hidden shadow-xl" style="background: linear-gradient(135deg, #991b1b 0%, #b91c1c 40%, #dc2626 100%);">
        {{-- Romanian diamond motif overlay --}}
        <svg class="absolute inset-0 w-full h-full pointer-events-none" style="opacity:.035" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="diamond-motif" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M24 4l10 10-10 10-10-10z" fill="none" stroke="#fff" stroke-width="1"/>
                    <path d="M24 8l6 6-6 6-6-6z" fill="#fff"/>
                    <circle cx="24" cy="4" r="1.5" fill="#fff"/>
                    <circle cx="24" cy="24" r="1.5" fill="#fff"/>
                    <circle cx="4" cy="24" r="1" fill="#fff"/>
                    <circle cx="44" cy="24" r="1" fill="#fff"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#diamond-motif)"/>
        </svg>

        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                {{-- Left: Health ring + bot info --}}
                <div class="flex items-center gap-6">
                    {{-- Health Score Ring --}}
                    <div class="relative w-24 h-24 shrink-0">
                        <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15" fill="rgba(255,255,255,.06)" stroke="none"/>
                            <circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="2.5"/>
                            <circle cx="18" cy="18" r="15" fill="none" stroke="#fff" stroke-width="2.5"
                                stroke-dasharray="{{ $score * 94.25 / 100 }} {{ 94.25 - ($score * 94.25 / 100) }}"
                                stroke-linecap="round"
                                style="filter: drop-shadow(0 0 8px rgba(255,255,255,.4))"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black text-white" style="text-shadow: 0 2px 10px rgba(0,0,0,.25)">{{ $score }}</span>
                            <span class="text-[10px] font-bold text-white/70 uppercase tracking-widest -mt-0.5">{{ $grade }}</span>
                        </div>
                    </div>

                    {{-- Bot name & status --}}
                    <div>
                        <h2 class="text-2xl font-bold text-white" style="text-shadow: 0 1px 6px rgba(0,0,0,.15)">{{ $bot->name }}</h2>
                        <div class="flex items-center gap-2 mt-2">
                            @if($bot->is_active)
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400" style="box-shadow: 0 0 8px rgba(52,211,153,.6)"></span>
                                </span>
                                <span class="text-sm font-semibold text-emerald-200">Activ</span>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-white/30"></span>
                                <span class="text-sm font-semibold text-white/50">Inactiv</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right: Stats boxes --}}
                <div class="flex gap-3">
                    @foreach([
                        ['v' => $convMonth, 'l' => 'Conversatii'],
                        ['v' => $kbDocs, 'l' => 'Documente'],
                        ['v' => $activeChannels, 'l' => 'Canale'],
                    ] as $s)
                    <div class="text-center px-5 py-3.5 rounded-xl backdrop-blur-sm border border-white/[.08]" style="background: rgba(255,255,255,.10); min-width: 95px;">
                        <p class="text-2xl font-black text-white" style="text-shadow: 0 1px 4px rgba(0,0,0,.15)">{{ $s['v'] }}</p>
                        <p class="text-[10px] text-white/70 font-semibold mt-1 uppercase tracking-wider">{{ $s['l'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ========== SETUP STEPS (only if incomplete) ========== --}}
    @if(!$setupComplete)
    <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <p class="text-sm text-amber-800 font-semibold flex-1">Configureaza botul:</p>
        <div class="flex items-center gap-2">
            @foreach([
                ['done' => $hasPrompt, 'label' => 'Instructiuni', 'tab' => 'instructions'],
                ['done' => $hasKnowledge, 'label' => 'Knowledge', 'tab' => 'knowledge'],
                ['done' => $hasWordpress, 'label' => 'WordPress', 'tab' => 'channels'],
            ] as $step)
            <button @click="$dispatch('set-tab', '{{ $step['tab'] }}')"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200
                {{ $step['done']
                    ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                    : 'bg-white text-amber-800 border border-amber-300 hover:bg-amber-100 hover:border-amber-400 shadow-sm' }}">
                @if($step['done'])
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                @endif
                {{ $step['label'] }}
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== SUGGESTIONS ========== --}}
    @if(!empty($suggestions))
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                </svg>
            </span>
            <span class="text-sm font-bold text-slate-800">Ce poti imbunatati</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach(array_slice($suggestions, 0, 3) as $sug)
            <div class="px-5 py-3.5 flex items-start gap-3">
                <span class="mt-0.5 w-5 h-5 rounded-full {{ ($sug['type'] ?? '') === 'critical' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <p class="text-sm text-slate-700 leading-relaxed flex-1">{{ $sug['message'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== QUICK ACTIONS + SETTINGS ========== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Quick Actions (2/3) --}}
        <div class="lg:col-span-2 grid grid-cols-2 gap-3">
            @foreach([
                ['tab' => 'instructions', 'label' => 'Instructiuni', 'desc' => 'Prompt & greeting', 'emoji' => "\u{1F4DD}", 'bg' => 'bg-red-50', 'border' => 'border-red-200', 'hoverBorder' => 'hover:border-red-400', 'iconBg' => 'bg-red-100'],
                ['tab' => 'knowledge', 'label' => 'Knowledge Base', 'desc' => $kbDocs . ' documente', 'emoji' => "\u{1F4DA}", 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'hoverBorder' => 'hover:border-emerald-400', 'iconBg' => 'bg-emerald-100'],
                ['tab' => 'personality', 'label' => 'Personalitate', 'desc' => $toneMap[$policy->tone ?? 'professional'] ?? '-', 'emoji' => "\u{1F3AD}", 'bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'hoverBorder' => 'hover:border-purple-400', 'iconBg' => 'bg-purple-100'],
                ['tab' => 'channels', 'label' => 'Canale', 'desc' => $activeChannels . ' active', 'emoji' => "\u{1F517}", 'bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'hoverBorder' => 'hover:border-blue-400', 'iconBg' => 'bg-blue-100'],
            ] as $a)
            <button @click="$dispatch('set-tab', '{{ $a['tab'] }}')"
                class="group flex items-center gap-4 p-4 rounded-xl border {{ $a['bg'] }} {{ $a['border'] }} {{ $a['hoverBorder'] }} hover:shadow-md transition-all duration-200 text-left">
                <span class="text-2xl shrink-0 w-10 h-10 rounded-lg {{ $a['iconBg'] }} flex items-center justify-center">{{ $a['emoji'] }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-800">{{ $a['label'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $a['desc'] }}</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>
            @endforeach
        </div>

        {{-- Settings Preview (1/3) --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                <span class="text-sm font-bold text-slate-800">Setari curente</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach([
                    ['l' => 'Ton', 'v' => $toneMap[$policy->tone ?? 'professional'] ?? '-'],
                    ['l' => 'Raspunsuri', 'v' => $verbMap[$policy->verbosity ?? 'concise'] ?? '-'],
                    ['l' => 'Emoji', 'v' => ($policy->emoji_allowed ?? false) ? 'Da' : 'Nu'],
                    ['l' => 'Limba', 'v' => strtoupper($bot->language ?? 'ro')],
                    ['l' => 'Voce', 'v' => ucfirst($bot->voice ?? 'alloy')],
                ] as $setting)
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="text-xs text-slate-500">{{ $setting['l'] }}</span>
                    <span class="text-xs font-bold text-slate-800">{{ $setting['v'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                <button @click="$dispatch('set-tab', 'personality')" class="text-xs text-red-700 font-bold hover:underline">
                    Modifica setarile &rarr;
                </button>
            </div>
        </div>
    </div>

    {{-- ========== RECENT ACTIVITY ========== --}}
    @if(isset($recentConversations) && $recentConversations->isNotEmpty())
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-800">Activitate recenta</span>
            <span class="text-xs text-slate-400 font-medium">{{ $convTotal }} total</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($recentConversations->take(5) as $conv)
            <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50/60 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    @php $initial = mb_strtoupper(mb_substr($conv->contact_name ?: ($conv->contact_identifier ?: 'V'), 0, 1)); @endphp
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-xs font-bold text-white" style="background: linear-gradient(135deg, #991b1b, #dc2626);">
                        {{ $initial }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $conv->contact_name ?: ($conv->contact_identifier ?: 'Vizitator') }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $conv->messages_count ?? 0 }} mesaje
                            &middot;
                            @if($conv->status === 'active')
                                <span class="text-emerald-500">activa</span>
                            @else
                                incheiata
                            @endif
                        </p>
                    </div>
                </div>
                <span class="text-xs text-slate-400 shrink-0 ml-4">{{ $conv->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
