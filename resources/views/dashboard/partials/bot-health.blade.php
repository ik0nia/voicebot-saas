{{-- Bot Health Cards --}}
@if($botHealth->isNotEmpty())
<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-semibold text-slate-900">Starea botilor</h3>
        <a href="/dashboard/boti" class="text-xs font-medium text-red-700 hover:underline">Gestioneaza &rarr;</a>
    </div>
    <div class="grid grid-cols-1 gap-4 {{ $botHealth->count() > 1 ? 'lg:grid-cols-2' : '' }}">
        @foreach($botHealth as $bh)
        @php
            $score = $bh['health_score'];
            $bot = $bh['bot'];
            $scoreColor = $score >= 80 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-600');

            // Card background gradient based on health
            if ($score >= 80) {
                $cardBg = 'bg-gradient-to-br from-green-50/80 to-white';
                $cardBorder = 'border-green-200/60';
            } elseif ($score >= 50) {
                $cardBg = 'bg-gradient-to-br from-amber-50/80 to-white';
                $cardBorder = 'border-amber-200/60';
            } else {
                $cardBg = 'bg-gradient-to-br from-red-50/80 to-white';
                $cardBorder = 'border-red-200/60';
            }

            // Ring gradient IDs (unique per bot)
            $ringId = 'ring-' . $bot->id;
        @endphp
        <div class="rounded-xl border {{ $cardBorder }} {{ $cardBg }} shadow-sm overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5">
            <div class="p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5 min-w-0">
                        {{-- Health ring with red gradient track --}}
                        <div class="relative w-14 h-14 shrink-0">
                            <svg class="w-14 h-14 -rotate-90" viewBox="0 0 36 36">
                                <defs>
                                    <linearGradient id="{{ $ringId }}-track" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#fca5a5"/>
                                        <stop offset="100%" stop-color="#fecaca"/>
                                    </linearGradient>
                                    @if($score >= 80)
                                    <linearGradient id="{{ $ringId }}-fill" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#10b981"/>
                                        <stop offset="100%" stop-color="#34d399"/>
                                    </linearGradient>
                                    @elseif($score >= 50)
                                    <linearGradient id="{{ $ringId }}-fill" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#f59e0b"/>
                                        <stop offset="100%" stop-color="#fbbf24"/>
                                    </linearGradient>
                                    @else
                                    <linearGradient id="{{ $ringId }}-fill" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#ef4444"/>
                                        <stop offset="100%" stop-color="#f87171"/>
                                    </linearGradient>
                                    @endif
                                </defs>
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#{{ $ringId }}-track)" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15.5" fill="none" stroke="url(#{{ $ringId }}-fill)" stroke-width="3" stroke-dasharray="{{ $score }} {{ 100 - $score }}" stroke-linecap="round"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-sm font-extrabold {{ $scoreColor }}">{{ $score }}</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-sm font-bold text-slate-900 truncate">{{ $bot->name }}</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $bot->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="/dashboard/boti/{{ $bot->id }}" class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Vezi detalii">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                </div>

                {{-- Recent conversations - prominent --}}
                <div class="mt-4 flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg bg-white/80 border border-slate-200/50 shadow-sm">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-red-50">
                        <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-slate-900">{{ $bh['recent_conversations'] }}</span>
                        <span class="text-xs text-slate-500 ml-1">conversatii in ultimele 7 zile</span>
                    </div>
                </div>

                {{-- Status indicators --}}
                <div class="mt-3.5 grid grid-cols-2 gap-2">
                    <div class="flex items-center gap-2 text-xs">
                        @if($bh['has_prompt'])
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
                        @endif
                        <span class="text-slate-600">System prompt</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        @if($bh['has_greeting'])
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
                        @endif
                        <span class="text-slate-600">Greeting</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        @if($bh['kb_total'] > 0)
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
                        @endif
                        <span class="text-slate-600">Knowledge ({{ $bh['kb_ready'] }}/{{ $bh['kb_total'] }})</span>
                        @if($bh['kb_failed'] > 0)
                            <span class="text-[10px] text-red-600 font-semibold">{{ $bh['kb_failed'] }} esuate</span>
                        @endif
                        @if($bh['kb_pending'] > 0)
                            <span class="text-[10px] text-amber-600 font-semibold">{{ $bh['kb_pending'] }} in curs</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        @if($bh['active_channels'] > 0)
                            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
                        @endif
                        <span class="text-slate-600">{{ $bh['active_channels'] }} canal{{ $bh['active_channels'] !== 1 ? 'e' : '' }} activ{{ $bh['active_channels'] !== 1 ? 'e' : '' }}</span>
                    </div>
                </div>

                {{-- Issues --}}
                @if(!empty($bh['issues']))
                <div class="mt-3.5 space-y-1.5">
                    @foreach($bh['issues'] as $issue)
                    <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg {{ $score >= 50 ? 'bg-amber-50 border border-amber-200/50' : 'bg-red-50 border border-red-200/50' }}">
                        <svg class="w-3 h-3 shrink-0 {{ $score >= 50 ? 'text-amber-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        <p class="text-[11px] font-medium {{ $score >= 50 ? 'text-amber-700' : 'text-red-700' }}">{{ $issue }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
