@extends('layouts.admin')
@section('title', $bot->name . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.bots.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span>
    </div>
    <p class="text-sm text-slate-500">Tenant: <span class="font-medium text-slate-700">{{ $bot->tenant?->name ?? '-' }}</span> | Limba: {{ strtoupper($bot->language) }} | Voce: {{ $bot->voice }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Apeluri luna asta</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $callsThisMonth }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Durata medie</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $avgDuration ? gmdate('i:s', (int)$avgDuration) : '-' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Knowledge Base</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $kbStats['total_documents'] }} docs / {{ $kbStats['total_chunks'] }} chunks</p>
    </div>
</div>

@if($bot->system_prompt)
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Prompt sistem</h2></div>
    <div class="p-5"><div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700 whitespace-pre-wrap font-mono leading-relaxed max-h-48 overflow-y-auto">{{ $bot->system_prompt }}</div></div>
</div>
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Apeluri recente</h2></div>
    @if($recentCalls->count())
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100"><th class="px-5 py-3 text-left text-xs font-medium uppercase text-slate-500">ID</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-slate-500">Apelant</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-slate-500">Status</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-slate-500">Durata</th><th class="px-5 py-3 text-left text-xs font-medium uppercase text-slate-500">Data</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($recentCalls as $call)
            <tr class="hover:bg-slate-50"><td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="text-red-800 hover:underline">#{{ $call->id }}</a></td><td class="px-5 py-3 text-slate-600">{{ $call->caller_number ?? '-' }}</td><td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $call->status }}</span></td><td class="px-5 py-3 text-slate-600">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</td><td class="px-5 py-3 text-slate-500 text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="px-5 py-8 text-center text-sm text-slate-400">Niciun apel.</div>
    @endif
</div>
@endsection
