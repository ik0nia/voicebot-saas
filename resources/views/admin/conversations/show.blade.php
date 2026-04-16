@extends('layouts.admin')
@section('title', 'Conversatie #' . $conversation->id . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.conversations.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-slate-900">Conversatie #{{ $conversation->id }}</h1>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $conversation->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $conversation->status === 'active' ? 'Activa' : 'Incheiata' }}</span>
    </div>
    <p class="text-sm text-slate-500">Tenant: <span class="font-medium text-slate-700">{{ $conversation->tenant?->name ?? '-' }}</span> | Bot: <span class="font-medium text-slate-700">{{ $conversation->bot?->name ?? '-' }}</span> | Contact: {{ $conversation->contact_name ?: ($conversation->contact_identifier ?: '-') }}</p>
</div>

{{-- Cost breakdown — aggregate message costs per model so an
     admin can see what the conversation actually spent on. Same
     shape as the call detail page so the UX is consistent. --}}
@php
    $totalCents = $messages->sum('cost_cents');
    $totalInTokens = $messages->sum('input_tokens');
    $totalOutTokens = $messages->sum('output_tokens');
    $perModel = $messages->where('cost_cents', '>', 0)->groupBy('ai_model')->map(fn($ms) => [
        'cost_cents' => $ms->sum('cost_cents'),
        'input' => $ms->sum('input_tokens'),
        'output' => $ms->sum('output_tokens'),
        'count' => $ms->count(),
    ]);
@endphp
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex items-baseline justify-between">
        <h2 class="text-base font-semibold text-slate-900">Breakdown cost</h2>
        <span class="text-xs text-slate-500">
            {{ $messages->count() }} mesaje &middot; {{ number_format($totalInTokens + $totalOutTokens) }} tokens totale
        </span>
    </div>
    <div class="p-5 space-y-3">
        @forelse($perModel as $model => $stats)
            @php
                $usd = $stats['cost_cents'] / 100;
                $pct = $totalCents > 0 ? round($stats['cost_cents'] / $totalCents * 100) : 0;
            @endphp
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="font-medium text-slate-700">{{ $model ?? 'LLM' }}</span>
                    <span class="text-xs text-slate-400">
                        ({{ $stats['count'] }} mesaje, {{ number_format($stats['input']) }} in / {{ number_format($stats['output']) }} out)
                    </span>
                </div>
                <span class="font-mono text-sm">${{ number_format($usd, 4) }} <span class="text-xs text-slate-400">{{ $pct }}%</span></span>
            </div>
        @empty
            <p class="text-sm text-slate-400 italic">Niciun mesaj cu cost calculat.</p>
        @endforelse
        <div class="border-t border-slate-100 pt-3 flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-900">Total conversație</span>
            <span class="font-mono text-base font-semibold">${{ number_format($totalCents / 100, 4) }}</span>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Mesaje ({{ $messages->count() }})</h2></div>
    <div class="p-5 space-y-3">
        @forelse($messages as $msg)
        <div class="flex {{ $msg->direction === 'inbound' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[75%]">
                <div class="px-4 py-2.5 rounded-2xl text-sm {{ $msg->direction === 'inbound' ? 'bg-red-800 text-white rounded-br-md' : 'bg-slate-100 text-slate-700 rounded-bl-md' }}">
                    {{ $msg->content }}
                    <div class="flex items-center gap-2 text-[10px] mt-1 {{ $msg->direction === 'inbound' ? 'text-red-200' : 'text-slate-400' }}">
                        <span>{{ $msg->created_at->format('H:i') }}</span>
                        @if($msg->ai_model)
                            <span class="opacity-70">{{ $msg->ai_model }}</span>
                            <span class="opacity-70">{{ $msg->input_tokens + $msg->output_tokens }} tok</span>
                            @if($msg->cost_cents > 0)
                                <span class="opacity-70">{{ number_format($msg->cost_cents / 100, 4) }}$</span>
                            @endif
                        @endif
                    </div>
                </div>
                @if($msg->metadata && !empty($msg->metadata['products']))
                    <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                        @foreach($msg->metadata['products'] as $product)
                            <div class="flex-shrink-0 w-36 rounded-lg border border-slate-200 bg-white overflow-hidden">
                                @if(!empty($product['image_url']))
                                    <img src="{{ $product['image_url'] }}" class="w-full h-20 object-cover" loading="lazy">
                                @endif
                                <div class="p-2">
                                    <p class="text-[11px] font-semibold text-slate-700 line-clamp-2">{{ $product['name'] }}</p>
                                    <p class="text-xs font-bold text-slate-900 mt-0.5">{{ $product['price'] }} {{ $product['currency'] ?? 'RON' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @empty
        <p class="text-center text-slate-400 text-sm py-4">Niciun mesaj.</p>
        @endforelse
    </div>
</div>
@endsection
