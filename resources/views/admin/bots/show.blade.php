@extends('layouts.admin')
@section('title', $bot->name . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.bots.index') }}" class="text-muted hover:text-muted"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-ink">{{ $bot->name }}</h1>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-cream text-muted' }}">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span>
    </div>
    <p class="text-sm text-muted">Tenant: <span class="font-medium text-inkSoft">{{ $bot->tenant?->name ?? '-' }}</span> | Limba: {{ strtoupper($bot->language) }} | Voce: {{ $bot->voice }}</p>
    <div class="mt-3">
        <a href="{{ route('admin.prompt-versions.index', $bot->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-coralsoft text-coralh text-sm font-medium rounded-lg hover:bg-coralsoft transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Prompt Versions (A/B)
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm text-muted">Apeluri luna asta</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $callsThisMonth }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm text-muted">Durata medie</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $avgDuration ? gmdate('i:s', (int)$avgDuration) : '-' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm text-muted">Knowledge Base</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $kbStats['total_documents'] }} docs / {{ $kbStats['total_chunks'] }} chunks</p>
    </div>
</div>

@if($bot->system_prompt)
<div class="bg-white rounded-xl border border-line shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-line"><h2 class="text-base font-semibold text-ink">Prompt sistem</h2></div>
    <div class="p-5"><div class="rounded-lg bg-cream border border-line px-4 py-3 text-sm text-inkSoft whitespace-pre-wrap font-mono leading-relaxed max-h-48 overflow-y-auto">{{ $bot->system_prompt }}</div></div>
</div>
@endif

<div class="bg-white rounded-xl border border-line shadow-sm">
    <div class="px-5 py-4 border-b border-line"><h2 class="text-base font-semibold text-ink">Apeluri recente</h2></div>
    @if($recentCalls->count())
    <table class="w-full text-sm">
        <thead><tr class="border-b border-line"><th class="px-5 py-3 text-left text-xs font-medium uppercase text-muted">ID</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-muted">Apelant</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-muted">Status</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-muted">Durata</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-muted">Data</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($recentCalls as $call)
            <tr class="hover:bg-cream"><td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="text-coralh hover:underline">#{{ $call->id }}</a></td><td class="px-5 py-3 text-muted">{{ $call->caller_number ?? '-' }}</td><td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-cream text-muted' }}">{{ $call->status }}</span></td><td class="px-5 py-3 text-muted">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</td><td class="px-5 py-3 text-muted text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="px-5 py-8 text-center text-sm text-muted">Niciun apel.</div>
    @endif
</div>
@endsection
