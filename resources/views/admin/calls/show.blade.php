@extends('layouts.admin')
@section('title', 'Apel #' . $call->id . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.calls.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-slate-900">Apel #{{ $call->id }}</h1>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $call->status }}</span>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Tenant</p><p class="text-sm font-medium text-slate-900 mt-1">{{ $call->tenant?->name ?? '-' }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Bot</p><p class="text-sm font-medium text-slate-900 mt-1">{{ $call->bot?->name ?? '-' }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Durata</p><p class="text-sm font-medium text-slate-900 mt-1">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</p></div>
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Cost</p><p class="text-sm font-medium text-slate-900 mt-1">{{ number_format($call->cost_cents / 100, 2) }}€</p></div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Transcript</h2></div>
    <div class="p-5 space-y-3">
        @forelse($transcripts as $t)
        <div class="flex {{ $t->role === 'user' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[75%] px-4 py-2.5 rounded-2xl text-sm {{ $t->role === 'user' ? 'bg-red-800 text-white rounded-br-md' : 'bg-slate-100 text-slate-700 rounded-bl-md' }}">
                {{ $t->content }}
            </div>
        </div>
        @empty
        <p class="text-center text-slate-400 text-sm py-4">Niciun transcript.</p>
        @endforelse
    </div>
</div>
@endsection
