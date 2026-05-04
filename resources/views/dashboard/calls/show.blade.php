@extends('layouts.dashboard')

@section('title', "Apel #{$call->id}")

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.calls.index') }}" class="font-medium text-muted hover:text-inkSoft transition-colors">Apeluri</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Apel #{{ $call->id }}</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-ink">Apel #{{ $call->id }}</h1>
            @php
                $statusConfig = [
                    'initiated'   => ['label' => 'Inițiat',       'bg' => 'bg-cream',   'text' => 'text-inkSoft'],
                    'ringing'     => ['label' => 'Sună',          'bg' => 'bg-yellow-100',  'text' => 'text-yellow-800'],
                    'in_progress' => ['label' => 'În curs',       'bg' => 'bg-coralsoft',    'text' => 'text-coralh'],
                    'completed'   => ['label' => 'Completat',     'bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
                    'failed'      => ['label' => 'Eșuat',         'bg' => 'bg-coralsoft',     'text' => 'text-coralh'],
                    'busy'        => ['label' => 'Ocupat',        'bg' => 'bg-orange-100',  'text' => 'text-orange-800'],
                    'no_answer'   => ['label' => 'Fără răspuns',  'bg' => 'bg-cream',   'text' => 'text-inkSoft'],
                    'canceled'    => ['label' => 'Anulat',        'bg' => 'bg-cream',   'text' => 'text-inkSoft'],
                ];
                $cfg = $statusConfig[$call->status] ?? ['label' => $call->status, 'bg' => 'bg-cream', 'text' => 'text-inkSoft'];
            @endphp
            <span class="inline-flex items-center rounded-full {{ $cfg['bg'] }} px-3 py-1 text-sm font-medium {{ $cfg['text'] }}">
                {{ $cfg['label'] }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.calls.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-medium text-inkSoft hover:bg-cream transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Înapoi
            </a>
            <form method="POST" action="{{ route('dashboard.calls.destroy', $call) }}"
                  onsubmit="return confirm('Ești sigur că vrei să ștergi acest apel? Această acțiune este ireversibilă.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-coral/40 bg-white px-4 py-2.5 text-sm font-medium text-coral hover:bg-coralsoft transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Șterge
                </button>
            </form>
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        {{-- Bot --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Agent AI</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $call->bot?->name ?? '—' }}</p>
        </div>

        {{-- Apelant --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Apelant</p>
            <p class="mt-1 text-lg font-semibold text-ink font-mono">{{ $call->caller_number ?? '—' }}</p>
        </div>

        {{-- Durată --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Durată</p>
            <p class="mt-1 text-lg font-semibold text-ink">
                @if($call->duration_seconds)
                    @php
                        $mins = floor($call->duration_seconds / 60);
                        $secs = $call->duration_seconds % 60;
                    @endphp
                    {{ $mins }}m {{ $secs }}s
                @else
                    —
                @endif
            </p>
        </div>

        {{-- Sentiment --}}
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Sentiment client</p>
            @if($call->sentiment_label)
                @php
                    $sentimentConfig = [
                        'positive' => ['color' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
                        'neutral'  => ['color' => 'text-muted',   'bg' => 'bg-cream',   'border' => 'border-line'],
                        'negative' => ['color' => 'text-coral',     'bg' => 'bg-coralsoft',     'border' => 'border-coral/30'],
                    ];
                    $sCfg = $sentimentConfig[$call->sentiment_label] ?? $sentimentConfig['neutral'];
                @endphp
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-2xl">{{ $call->sentimentEmoji() }}</span>
                    <div>
                        <p class="text-lg font-semibold {{ $sCfg['color'] }}">{{ $call->sentimentLabelRo() }}</p>
                        <p class="text-xs text-muted">Scor: {{ number_format($call->sentiment_score, 2) }}</p>
                    </div>
                </div>
            @else
                <p class="mt-1 text-lg font-semibold text-muted">—</p>
                <p class="text-xs text-muted">Se analizează...</p>
            @endif
        </div>

        {{-- Cost (super_admin only) --}}
        @if(auth()->user()->isSuperAdmin())
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Cost</p>
            <p class="mt-1 text-lg font-semibold text-ink">
                @if($call->cost_cents)
                    {{ number_format($call->cost_cents / 100, 4) }} EUR
                @else
                    —
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Transcript --}}
    <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h3 class="text-base font-semibold text-ink">Transcript conversație</h3>
            @if($transcripts->count() > 0)
                <a href="{{ route('dashboard.calls.export-transcript', ['call' => $call, 'format' => 'txt']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white px-3 py-1.5 text-xs font-medium text-inkSoft hover:bg-cream transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export TXT
                </a>
            @endif
        </div>
        <div class="p-5">
            @if($transcripts->count() > 0)
                <div class="space-y-4 max-w-2xl mx-auto">
                    @foreach($transcripts as $t)
                        @if($t->role === 'assistant' || $t->role === 'bot')
                            {{-- Bot message - left aligned --}}
                            <div class="flex justify-start">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tl-sm bg-cream px-4 py-3 text-sm text-ink">
                                        {{ $t->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted ml-1">
                                        Bot
                                        @if($t->timestamp_ms)
                                            &middot; {{ gmdate('i:s', intval($t->timestamp_ms / 1000)) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @else
                            {{-- User message - right aligned --}}
                            <div class="flex justify-end">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tr-sm bg-coral px-4 py-3 text-sm text-white">
                                        {{ $t->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted text-right mr-1">
                                        Client
                                        @if($t->timestamp_ms)
                                            &middot; {{ gmdate('i:s', intval($t->timestamp_ms / 1000)) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-full bg-cream flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm text-muted">Niciun transcript disponibil.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Timeline Events --}}
    <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
        <div class="border-b border-line px-5 py-4">
            <h3 class="text-base font-semibold text-ink">Evenimente</h3>
        </div>
        <div class="p-5">
            @if($events->count() > 0)
                <div class="relative">
                    {{-- Vertical line --}}
                    <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-sand"></div>

                    <div class="space-y-6">
                        @foreach($events as $event)
                            @php
                                $eventColors = [
                                    'call.initiated' => ['dot' => 'bg-coral',    'text' => 'text-coralh',   'bg' => 'bg-coralsoft'],
                                    'call.ringing'   => ['dot' => 'bg-yellow-500',  'text' => 'text-yellow-700', 'bg' => 'bg-yellow-50'],
                                    'call.answered'  => ['dot' => 'bg-green-500',   'text' => 'text-green-700',  'bg' => 'bg-green-50'],
                                    'call.ended'     => ['dot' => 'bg-slate-500',   'text' => 'text-inkSoft',  'bg' => 'bg-cream'],
                                    'error'          => ['dot' => 'bg-red-500',     'text' => 'text-coralh',    'bg' => 'bg-coralsoft'],
                                ];
                                $evtCfg = $eventColors[$event->type] ?? ['dot' => 'bg-slate-400', 'text' => 'text-muted', 'bg' => 'bg-cream'];
                            @endphp
                            <div class="relative flex gap-4 pl-8">
                                {{-- Dot --}}
                                <div class="absolute left-0 top-1 w-[22px] h-[22px] rounded-full border-[3px] border-white {{ $evtCfg['dot'] }} shadow-sm"></div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full {{ $evtCfg['bg'] }} px-2.5 py-0.5 text-xs font-medium {{ $evtCfg['text'] }}">
                                            {{ $event->type }}
                                        </span>
                                        <span class="text-xs text-muted">
                                            {{ $event->occurred_at?->format('d.m.Y H:i:s') ?? '—' }}
                                        </span>
                                    </div>
                                    @if($event->metadata)
                                        <pre class="mt-2 rounded-lg bg-cream border border-line p-3 text-xs text-muted overflow-x-auto"><code>{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-full bg-cream flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-muted">Niciun eveniment înregistrat.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Audio Player --}}
    @if($call->recording_url || $call->local_recording_path || $call->recording_purged_at)
        @php
            // Prefer the local mirror — it survives carrier purges and is
            // served through our auth-gated route. Fall back to the raw
            // carrier URL if the mirror job hasn't run yet (rare; runs
            // within seconds of call.completed). Audio is retired after
            // 14 days; show a clear empty state in that case.
            $hasLocal = !empty($call->local_recording_path) && empty($call->recording_purged_at);
            $audioSrc = $hasLocal ? route('dashboard.calls.audio', $call) : $call->recording_url;
            $isPurged = !empty($call->recording_purged_at);
        @endphp

        <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
            <div class="border-b border-line px-5 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink">Înregistrare audio</h3>
                @if($call->recording_mirrored_at && !$isPurged)
                    @php
                        $purgeDate = $call->recording_mirrored_at->copy()->addDays(14);
                        $daysLeft = max(0, (int) now()->diffInDays($purgeDate, false));
                    @endphp
                    <span class="text-xs text-muted" title="Înregistrările se șterg automat după 14 zile pentru conformitate GDPR. Transcriptul rămâne pentru totdeauna.">
                        Disponibilă încă {{ $daysLeft }} {{ $daysLeft === 1 ? 'zi' : 'zile' }}
                    </span>
                @endif
            </div>
            <div class="p-5">
                @if($isPurged)
                    <div class="text-sm text-muted py-3 px-4 rounded-lg bg-cream border border-line">
                        🗑️ Înregistrarea a fost ștearsă automat după 14 zile (politica de retenție).
                        <span class="text-line">Transcriptul de mai jos rămâne disponibil pentru totdeauna.</span>
                    </div>
                @else
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <audio controls class="w-full sm:flex-1" preload="metadata">
                            <source src="{{ $audioSrc }}" type="audio/mpeg">
                            Browserul tău nu suportă redarea audio.
                        </audio>
                        <a href="{{ $audioSrc }}" download="apel-{{ $call->id }}.mp3"
                           class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-medium text-inkSoft hover:bg-cream transition-colors shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Descarcă
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Call Metadata --}}
    @if($call->metadata)
        <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
            <div class="border-b border-line px-5 py-4">
                <h3 class="text-base font-semibold text-ink">Metadate apel</h3>
            </div>
            <div class="p-5">
                <pre class="rounded-lg bg-cream border border-line p-4 text-sm text-inkSoft overflow-x-auto"><code>{{ json_encode($call->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    @endif
@endsection
