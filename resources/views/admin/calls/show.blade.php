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
    <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">Cost total</p><p class="text-sm font-medium text-slate-900 mt-1">${{ number_format($call->cost_cents / 100, 4) }}</p></div>
</div>

{{-- Cost breakdown per componentă. Cents in DB, display as USD
     4-decimal for precision on cheap calls ($0.02 is normal). --}}
@php
    $openaiUsd = ($call->openai_cost_cents ?? 0) / 100;
    $twilioUsd = ($call->twilio_cost_cents ?? 0) / 100;
    $embedUsd  = ($call->embedding_cost_cents ?? 0) / 100;
    $totalUsd  = ($call->cost_cents ?? 0) / 100;
    $pctOpenai = $totalUsd > 0 ? round($openaiUsd / $totalUsd * 100) : 0;
    $pctTwilio = $totalUsd > 0 ? round($twilioUsd / $totalUsd * 100) : 0;
    $pctEmbed  = $totalUsd > 0 ? round($embedUsd / $totalUsd * 100) : 0;
    $perMin = $call->duration_seconds > 0 ? $totalUsd / ($call->duration_seconds / 60) : 0;
@endphp
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-baseline justify-between">
        <h2 class="text-base font-semibold text-slate-900">Breakdown cost</h2>
        <span class="text-xs text-slate-500">
            {{ number_format($perMin, 4) }} $/min &middot; {{ $call->duration_seconds ?? 0 }}s
        </span>
    </div>
    <div class="p-5 space-y-3">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="font-medium text-slate-700">OpenAI Realtime</span>
                <span class="text-xs text-slate-400">(token-based: audio + text, input + output, cached + fresh)</span>
            </div>
            <span class="font-mono text-sm">${{ number_format($openaiUsd, 4) }} <span class="text-xs text-slate-400">{{ $pctOpenai }}%</span></span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                <span class="font-medium text-slate-700">Twilio voice</span>
                <span class="text-xs text-slate-400">(direct din Twilio Call API după fiecare apel)</span>
            </div>
            <span class="font-mono text-sm">${{ number_format($twilioUsd, 4) }} <span class="text-xs text-slate-400">{{ $pctTwilio }}%</span></span>
        </div>
        @if($embedUsd > 0)
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span class="font-medium text-slate-700">Embeddings knowledge</span>
                    <span class="text-xs text-slate-400">(text-embedding-3-small, fetch knowledge la start)</span>
                </div>
                <span class="font-mono text-sm">${{ number_format($embedUsd, 4) }} <span class="text-xs text-slate-400">{{ $pctEmbed }}%</span></span>
            </div>
        @endif
        <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-900">Total</span>
            <span class="font-mono text-base font-semibold">${{ number_format($totalUsd, 4) }}</span>
        </div>
    </div>
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
