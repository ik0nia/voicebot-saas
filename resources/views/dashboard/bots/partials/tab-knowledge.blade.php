    @php
        $kbScore = $kbStats['score'];
        $kbScoreColor = $kbScore >= 80 ? 'text-green-600' : ($kbScore >= 40 ? 'text-amber-500' : 'text-red-600');
        $kbStrokeColor = $kbScore >= 80 ? 'stroke-green-500' : ($kbScore >= 40 ? 'stroke-amber-500' : 'stroke-red-500');
        $kbTrackColor = $kbScore >= 80 ? 'stroke-green-100' : ($kbScore >= 40 ? 'stroke-amber-100' : 'stroke-red-100');
        $circumference = 2 * 3.14159 * 40;
        $dashoffset = $circumference - ($kbScore / 100) * $circumference;
    @endphp

    <div id="section-kb" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50">
                        <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Knowledge Base</h2>
                </div>
                <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">
                    Gestioneaza Knowledge Base
                </a>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- KB Health Score --}}
                    <div class="flex flex-col items-center">
                        <div class="relative w-24 h-24 mb-3">
                            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $kbTrackColor }}" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $kbStrokeColor }}"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $dashoffset }}" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold {{ $kbScoreColor }}">{{ $kbScore }}%</span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wide">Scor</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900">{{ $kbStats['total_documents'] }}</p>
                            <p class="text-xs text-slate-500">documente ({{ $kbStats['total_chunks'] }} chunks)</p>
                        </div>
                    </div>

                    {{-- Recent documents --}}
                    <div class="md:col-span-2">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Documente recente</h3>
                        @if($recentKnowledge->count() > 0)
                            <ul class="space-y-2">
                                @foreach($recentKnowledge as $doc)
                                    <li class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-slate-50 transition-colors">
                                        <div class="flex items-center justify-center w-7 h-7 rounded bg-slate-100 shrink-0">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ $doc->title }}</p>
                                            <p class="text-xs text-slate-400">{{ $doc->source_type ?? 'manual' }} &middot; {{ $doc->created_at->diffForHumans() }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 border-dashed px-4 py-6 text-center">
                                <p class="text-sm text-slate-400">Niciun document in Knowledge Base.</p>
                                <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}" class="text-sm font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Adauga documente &rarr;</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 4.5: AI HEALTH SCORE (from BotHealthScoreService) --}}
    {{-- ============================================================ --}}
    @if(isset($healthScore))
    @php
        $hsScore = $healthScore['score'] ?? 0;
        $hsGrade = $healthScore['grade'] ?? 'F';
        $hsCircumference = 2 * 3.14159 * 40;
        $hsDashoffset = $hsCircumference - ($hsScore / 100) * $hsCircumference;
        // Use concrete Tailwind classes (not dynamic interpolation) for purge safety
        $hsIconBg = $hsScore >= 80 ? 'bg-green-50' : ($hsScore >= 60 ? 'bg-amber-50' : ($hsScore >= 40 ? 'bg-orange-50' : 'bg-red-50'));
        $hsIconText = $hsScore >= 80 ? 'text-green-600' : ($hsScore >= 60 ? 'text-amber-600' : ($hsScore >= 40 ? 'text-orange-600' : 'text-red-600'));
        $hsBadgeBg = $hsScore >= 80 ? 'bg-green-100 text-green-700' : ($hsScore >= 60 ? 'bg-amber-100 text-amber-700' : ($hsScore >= 40 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'));
        $hsScoreText = $hsScore >= 80 ? 'text-green-600' : ($hsScore >= 60 ? 'text-amber-600' : ($hsScore >= 40 ? 'text-orange-600' : 'text-red-600'));
        $hsStroke = $hsScore >= 80 ? 'stroke-green-500' : ($hsScore >= 60 ? 'stroke-amber-500' : ($hsScore >= 40 ? 'stroke-orange-500' : 'stroke-red-500'));
        $hsTrack = $hsScore >= 80 ? 'stroke-green-100' : ($hsScore >= 60 ? 'stroke-amber-100' : ($hsScore >= 40 ? 'stroke-orange-100' : 'stroke-red-100'));
    @endphp
    <div id="section-health" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg {{ $hsIconBg }}">
                    <svg class="w-4 h-4 {{ $hsIconText }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900">Sănătatea Bot-ului</h2>
                <span class="ml-auto px-2.5 py-0.5 rounded-full text-xs font-bold {{ $hsBadgeBg }}">{{ $hsGrade }}</span>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Overall Score --}}
                    <div class="flex flex-col items-center">
                        <div class="relative w-28 h-28 mb-3">
                            <svg class="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $hsTrack }}" />
                                <circle cx="50" cy="50" r="40" fill="none" stroke-width="8" class="{{ $hsStroke }}"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $hsCircumference }}"
                                    stroke-dashoffset="{{ $hsDashoffset }}" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold {{ $hsScoreText }}">{{ $hsScore }}</span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wide">din 100</span>
                            </div>
                        </div>
                        <p class="text-sm font-medium text-slate-700">Scor General</p>
                    </div>

                    {{-- Component Breakdown --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Componente</h3>
                        <div class="space-y-3">
                            @foreach($healthScore['components'] ?? [] as $key => $comp)
                                @php
                                    $pct = $comp['max'] > 0 ? round(($comp['score'] / $comp['max']) * 100) : 0;
                                    $barColor = $pct >= 70 ? 'bg-green-500' : ($pct >= 40 ? 'bg-amber-500' : 'bg-red-500');
                                @endphp
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-slate-600">{{ $comp['label'] }}</span>
                                        <span class="font-medium text-slate-900">{{ $comp['score'] }}/{{ $comp['max'] }}</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full">
                                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Suggestions --}}
                    <div>
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Ce poți îmbunătăți</h3>
                        @forelse($healthScore['suggestions'] ?? [] as $suggestion)
                            <div class="flex items-start gap-2 py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                <span class="text-base mt-0.5 shrink-0">{{ $suggestion['icon'] ?? '💡' }}</span>
                                <div>
                                    <p class="text-xs text-slate-700">{{ $suggestion['message'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-center">
                                <p class="text-sm text-green-700">Totul arată excelent! 🎉</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================================================ --}}
    {{-- SECTION 4.6: KNOWLEDGE GAPS (what the bot can't answer) --}}
    {{-- ============================================================ --}}
    @if(isset($knowledgeGaps) && ($knowledgeGaps['total_failed'] ?? 0) > 0)
    <div id="section-gaps" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-slate-900">Ce nu știe bot-ul</h2>
                <span class="ml-auto text-xs text-slate-500">Ultimele 7 zile &middot; {{ $knowledgeGaps['fail_rate'] }}% rată eșec</span>
            </div>
            <div class="p-5">
                @if(!empty($knowledgeGaps['suggestions']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
                        @foreach($knowledgeGaps['suggestions'] as $suggestion)
                            <div class="rounded-lg border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50/30 transition-colors">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-lg">{{ $suggestion['icon'] ?? '📄' }}</span>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $suggestion['title'] }}</h4>
                                </div>
                                <p class="text-xs text-slate-600 mb-2">{{ $suggestion['description'] }}</p>
                                <p class="text-xs text-slate-400">{{ $suggestion['occurrences'] }} întrebări fără răspuns</p>
                                @if(!empty($suggestion['sample_queries']))
                                    <div class="mt-2 pt-2 border-t border-slate-100">
                                        <p class="text-[10px] uppercase tracking-wide text-slate-400 mb-1">Exemple:</p>
                                        @foreach(array_slice($suggestion['sample_queries'], 0, 2) as $sq)
                                            <p class="text-xs text-slate-500 italic">"{{ $sq }}"</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($knowledgeGaps['gaps']))
                    <details class="group">
                        <summary class="text-sm font-medium text-slate-700 cursor-pointer hover:text-red-800 transition-colors">
                            Top {{ min(10, count($knowledgeGaps['gaps'])) }} întrebări fără răspuns
                            <svg class="w-4 h-4 inline ml-1 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <div class="mt-3 space-y-1">
                            @foreach(array_slice($knowledgeGaps['gaps'], 0, 10) as $gap)
                                <div class="flex items-center justify-between py-1.5 px-3 rounded hover:bg-slate-50 text-xs">
                                    <span class="text-slate-700">"{{ $gap['query'] }}"</span>
                                    <span class="text-slate-400 shrink-0 ml-2">{{ $gap['occurrences'] }}x</span>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </div>
    @endif