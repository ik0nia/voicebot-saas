@extends('layouts.dashboard')
@section('title', 'Oportunitate #' . $conversation->id)
@section('breadcrumb')
<a href="{{ route('dashboard.opportunities.index') }}" class="text-blue-600">Oportunități</a>
<span class="mx-1 text-slate-400">/</span>
<span class="text-slate-900 font-medium">#{{ $conversation->id }}</span>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Scor Interes</h3>
            <div class="text-4xl font-bold {{ $conversation->opportunity_score >= 60 ? 'text-green-600' : 'text-yellow-600' }}">{{ $conversation->opportunity_score }}/100</div>
            <div class="mt-2 text-sm text-slate-500">
                @if(is_array($conversation->opportunity_reasons))
                    @foreach($conversation->opportunity_reasons as $reason)
                        <span class="inline-block px-2 py-0.5 bg-slate-100 rounded text-xs mr-1 mb-1">{{ $reason }}</span>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Produse vizualizate</h3>
            @foreach($productEvents->where('event_name', 'product_click')->take(10) as $pe)
                <div class="text-sm text-slate-700 py-1 border-b border-slate-50">
                    🔗 {{ $pe->properties['product_name'] ?? 'Produs #' . ($pe->product_id ?? '?') }}
                </div>
            @endforeach
            @if($productEvents->where('event_name', 'product_click')->isEmpty())
                <p class="text-sm text-slate-400">Niciun click pe produs</p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Drop-off</h3>
            @if($dropOffEvent)
                <p class="text-sm text-slate-600">Ultimul eveniment: <strong>{{ $dropOffEvent->event_name }}</strong></p>
                <p class="text-xs text-slate-400">{{ $dropOffEvent->occurred_at->format('d.m.Y H:i:s') }}</p>
            @endif
            @if(in_array('abandoned_after_products', $conversation->outcomes_summary ?? []))
                <div class="mt-2 px-2 py-1 bg-red-50 border border-red-200 rounded text-xs text-red-700">Abandonat după ce a văzut produse</div>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Conversație completă</h3>
            <div class="space-y-3 max-h-[600px] overflow-y-auto">
                @foreach($conversation->messages as $msg)
                <div class="{{ $msg->direction === 'inbound' ? 'text-right' : 'text-left' }}">
                    <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $msg->direction === 'inbound' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-900' }}">
                        {{ $msg->content }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
