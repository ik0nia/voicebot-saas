@extends('layouts.dashboard')

@section('title', "Apel #{$call->id}")

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.calls.index') }}" class="font-medium text-slate-500 hover:text-slate-700 transition-colors">Apeluri</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Apel #{{ $call->id }}</span>
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
            <h1 class="text-2xl font-bold text-slate-900">Apel #{{ $call->id }}</h1>
            @php
                $statusConfig = [
                    'initiated'   => ['label' => 'Inițiat',       'bg' => 'bg-slate-100',   'text' => 'text-slate-700'],
                    'ringing'     => ['label' => 'Sună',          'bg' => 'bg-yellow-100',  'text' => 'text-yellow-800'],
                    'in_progress' => ['label' => 'În curs',       'bg' => 'bg-blue-100',    'text' => 'text-blue-800'],
                    'completed'   => ['label' => 'Completat',     'bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
                    'failed'      => ['label' => 'Eșuat',         'bg' => 'bg-red-100',     'text' => 'text-red-800'],
                    'busy'        => ['label' => 'Ocupat',        'bg' => 'bg-orange-100',  'text' => 'text-orange-800'],
                    'no_answer'   => ['label' => 'Fără răspuns',  'bg' => 'bg-slate-100',   'text' => 'text-slate-700'],
                    'canceled'    => ['label' => 'Anulat',        'bg' => 'bg-slate-100',   'text' => 'text-slate-700'],
                ];
                $cfg = $statusConfig[$call->status] ?? ['label' => $call->status, 'bg' => 'bg-slate-100', 'text' => 'text-slate-700'];
            @endphp
            <span class="inline-flex items-center rounded-full {{ $cfg['bg'] }} px-3 py-1 text-sm font-medium {{ $cfg['text'] }}">
                {{ $cfg['label'] }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.calls.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
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
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
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
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Bot</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $call->bot?->name ?? '—' }}</p>
        </div>

        {{-- Apelant --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Apelant</p>
            <p class="mt-1 text-lg font-semibold text-slate-900 font-mono">{{ $call->caller_number ?? '—' }}</p>
        </div>

        {{-- Durată --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Durată</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
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

        {{-- Cost --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Cost</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
                @if($call->cost !== null)
                    {{ number_format($call->cost, 4) }} EUR
                @else
                    —
                @endif
            </p>
        </div>
    </div>

    {{-- Transcript --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Transcript conversație</h3>
            @if($transcripts->count() > 0)
                <a href="{{ route('dashboard.calls.export-transcript', ['call' => $call, 'format' => 'txt']) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
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
                                    <div class="rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-sm text-slate-800">
                                        {{ $t->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 ml-1">
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
                                    <div class="rounded-2xl rounded-tr-sm bg-blue-600 px-4 py-3 text-sm text-white">
                                        {{ $t->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 text-right mr-1">
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
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">Niciun transcript disponibil.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Timeline Events --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Evenimente</h3>
        </div>
        <div class="p-5">
            @if($events->count() > 0)
                <div class="relative">
                    {{-- Vertical line --}}
                    <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-slate-200"></div>

                    <div class="space-y-6">
                        @foreach($events as $event)
                            @php
                                $eventColors = [
                                    'call.initiated' => ['dot' => 'bg-blue-500',    'text' => 'text-blue-700',   'bg' => 'bg-blue-50'],
                                    'call.ringing'   => ['dot' => 'bg-yellow-500',  'text' => 'text-yellow-700', 'bg' => 'bg-yellow-50'],
                                    'call.answered'  => ['dot' => 'bg-green-500',   'text' => 'text-green-700',  'bg' => 'bg-green-50'],
                                    'call.ended'     => ['dot' => 'bg-slate-500',   'text' => 'text-slate-700',  'bg' => 'bg-slate-50'],
                                    'error'          => ['dot' => 'bg-red-500',     'text' => 'text-red-700',    'bg' => 'bg-red-50'],
                                ];
                                $evtCfg = $eventColors[$event->type] ?? ['dot' => 'bg-slate-400', 'text' => 'text-slate-600', 'bg' => 'bg-slate-50'];
                            @endphp
                            <div class="relative flex gap-4 pl-8">
                                {{-- Dot --}}
                                <div class="absolute left-0 top-1 w-[22px] h-[22px] rounded-full border-[3px] border-white {{ $evtCfg['dot'] }} shadow-sm"></div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full {{ $evtCfg['bg'] }} px-2.5 py-0.5 text-xs font-medium {{ $evtCfg['text'] }}">
                                            {{ $event->type }}
                                        </span>
                                        <span class="text-xs text-slate-400">
                                            {{ $event->occurred_at?->format('d.m.Y H:i:s') ?? '—' }}
                                        </span>
                                    </div>
                                    @if($event->metadata)
                                        <pre class="mt-2 rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 overflow-x-auto"><code>{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">Niciun eveniment înregistrat.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Audio Player --}}
    @if($call->recording_url)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900">Înregistrare audio</h3>
            </div>
            <div class="p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <audio controls class="w-full sm:flex-1" preload="metadata">
                        <source src="{{ $call->recording_url }}" type="audio/mpeg">
                        Browserul tău nu suportă redarea audio.
                    </audio>
                    <a href="{{ $call->recording_url }}" download
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Descarcă
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Call Metadata --}}
    @if($call->metadata)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900">Metadate apel</h3>
            </div>
            <div class="p-5">
                <pre class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700 overflow-x-auto"><code>{{ json_encode($call->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    @endif
@endsection
