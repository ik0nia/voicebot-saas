@extends('layouts.dashboard')

@section('title', 'Comenzi — ' . $bot->name)

@php
    $statusStyles = [
        'draft'            => ['Coș abandonat', 'bg-amber-50 text-amber-800 border-amber-200'],
        'placed'           => ['Primită',       'bg-coralsoft text-coralh border-coral/30'],
        'confirmed'        => ['Confirmată',    'bg-blue-50 text-blue-700 border-blue-200'],
        'preparing'        => ['În preparare',  'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'out_for_delivery' => ['La livrare',    'bg-violet-50 text-violet-700 border-violet-200'],
        'completed'        => ['Finalizată',    'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'canceled'         => ['Anulată',       'bg-stone-100 text-stone-600 border-stone-300'],
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="text-xs text-muted mb-1">
        <a href="{{ route('dashboard.bots.edit', $bot) }}" class="hover:text-coralh">← {{ $bot->name }}</a>
    </div>
    <h1 class="text-2xl font-bold text-ink">Comenzi</h1>
    <p class="text-sm text-muted mt-1 mb-6">Comenzile preluate de agent la telefon și pe chat.</p>

    @include('dashboard.bots.restaurant._nav', ['active' => 'orders'])

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(!$settings || !$settings->ordering_enabled)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <div class="text-sm text-amber-900">
                <span class="mr-1">⚠️</span>
                <strong>Agentul nu preia comenzi.</strong>
                @if(!$settings)
                    Nu ai configurat încă nimic — la telefon răspunde că localul nu ia comenzi.
                @else
                    Preluarea comenzilor este oprită din setări.
                @endif
            </div>
            <a href="{{ route('dashboard.bots.restaurant.settings', $bot) }}"
               class="inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 whitespace-nowrap">
                Configurează acum
            </a>
        </div>
    @endif

    {{-- Filter pills --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @foreach(['active' => 'De rezolvat', 'draft' => 'Coșuri abandonate', 'completed' => 'Finalizate', 'canceled' => 'Anulate', 'all' => 'Toate'] as $key => $label)
            <a href="{{ route('dashboard.bots.restaurant.orders', ['bot' => $bot, 'status' => $key]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill text-xs font-medium border
                      {{ $status === $key ? 'bg-coral text-white border-coral' : 'bg-white text-inkSoft border-line hover:bg-cream' }}">
                {{ $label }}
                <span class="text-2xs opacity-70">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach

        <form method="GET" action="{{ route('dashboard.bots.restaurant.orders', $bot) }}" class="flex-1 max-w-xs ml-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="search" value="{{ request('search') }}"
                   placeholder="Caută după nume, telefon, adresă sau nr. comandă…"
                   class="w-full rounded-lg border border-line bg-white px-3 py-1.5 text-xs">
        </form>
    </div>

    @if($orders->isEmpty())
        <div class="rounded-xl border border-line bg-white p-10 text-center">
            <div class="text-3xl mb-2">🧾</div>
            <p class="text-sm text-muted">
                @if(request('search'))
                    Nicio comandă nu se potrivește căutării.
                @elseif($status === 'draft')
                    Niciun coș abandonat. Bine.
                @elseif($status === 'active')
                    Nicio comandă în lucru acum.
                @else
                    Nicio comandă încă.
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-line overflow-hidden">
            <div class="divide-y divide-line">
                @foreach($orders as $order)
                    @php [$label, $chip] = $statusStyles[$order->status] ?? [$order->status, 'bg-stone-100 text-stone-600 border-stone-300']; @endphp
                    <a href="{{ route('dashboard.bots.restaurant.order', [$bot, $order]) }}"
                       class="flex items-start gap-4 px-4 py-3 hover:bg-cream/40 transition">
                        <div class="shrink-0 w-14 text-center">
                            <div class="font-mono text-sm font-bold text-ink">{{ $order->reference() }}</div>
                            <div class="text-2xs text-muted mt-0.5">{{ $order->source === 'voice' ? '📞' : '💬' }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs border {{ $chip }}">{{ $label }}</span>
                                <span class="font-semibold text-ink truncate">{{ $order->customer_name ?: 'Fără nume' }}</span>
                                @if($order->customer_phone)
                                    <span class="text-xs text-muted">{{ $order->customer_phone }}</span>
                                @endif
                                @if($order->fulfilment)
                                    <span class="text-2xs text-muted">
                                        · {{ $order->isDelivery() ? 'livrare' : 'ridicare' }}{{ $order->delivery_zone ? ' — ' . $order->delivery_zone : '' }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-muted truncate">
                                {{ $order->items->map(fn ($i) => $i->quantity . '× ' . $i->name_snapshot)->implode(', ') ?: 'fără produse' }}
                            </div>
                            @if($order->delivery_address)
                                <div class="text-2xs text-muted mt-0.5 truncate">📍 {{ $order->delivery_address }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="font-semibold text-coralh">{{ $order->formattedTotal() }}</div>
                            <div class="text-2xs text-muted mt-0.5">{{ $order->created_at->format('d.m H:i') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
