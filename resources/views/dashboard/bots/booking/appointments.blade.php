@extends('layouts.dashboard')

@section('title', 'Programări — ' . $bot->name)

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-start justify-between gap-3 mb-6">
        <div>
            <div class="text-xs text-muted mb-1">
                <a href="{{ route('dashboard.bots.booking', $bot) }}" class="hover:text-coralh">← {{ $bot->name }} · Programări</a>
            </div>
            <h1 class="text-2xl font-bold text-ink">Programări</h1>
            <p class="text-sm text-muted mt-1">Toate rezervările făcute de agentul AI sau direct prin widget.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'view' => 'calendar']) }}"
               class="text-xs font-medium text-coralh hover:text-coral border border-coral/30 bg-coralsoft rounded-lg px-3 py-1.5">
                📅 Vedere calendar
            </a>
            <a href="{{ route('dashboard.bots.booking', $bot) }}"
               class="text-xs font-medium text-muted hover:text-ink border border-line rounded-lg px-3 py-1.5 hover:bg-cream">
                ⚙ Configurare servicii
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter tabs --}}
    <div class="flex flex-wrap gap-1.5 mb-4 border-b border-line">
        @foreach([
            'upcoming'  => ['Următoare', $counts['upcoming']],
            'past'      => ['Trecute', $counts['past']],
            'completed' => ['Finalizate', null],
            'canceled'  => ['Anulate', $counts['canceled']],
        ] as $key => [$label, $count])
            <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'status' => $key]) }}"
               class="px-3 py-2 text-sm font-medium rounded-t-lg border-b-2 -mb-px
                      {{ $status === $key ? 'text-coralh border-coral' : 'text-muted border-transparent hover:text-ink hover:border-line' }}">
                {{ $label }}
                @if($count !== null)
                    <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full {{ $status === $key ? 'bg-coralsoft text-coralh' : 'bg-cream text-muted' }}">{{ $count }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @if($appointments->isEmpty())
        <div class="rounded-xl border border-line bg-white p-10 text-center">
            <div class="text-3xl mb-2">📅</div>
            <p class="text-sm text-muted">Nicio programare în acest filtru.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-line overflow-hidden">
            <div class="divide-y divide-line">
                @foreach($appointments as $apt)
                    @php
                        $statusColor = match($apt->status) {
                            'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'confirmed', 'reminder_sent' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'requested' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'canceled', 'noshow' => 'bg-coralsoft text-coralh border-coral/30',
                            default => 'bg-cream text-muted border-line',
                        };
                        $statusLabel = match($apt->status) {
                            'requested' => 'Cerută',
                            'confirmed' => 'Confirmată',
                            'reminder_sent' => 'Reamintit',
                            'completed' => 'Finalizată',
                            'canceled' => 'Anulată',
                            'noshow' => 'No-show',
                            default => $apt->status,
                        };
                    @endphp
                    <a href="{{ route('dashboard.bots.booking.appointment', [$bot, $apt]) }}"
                       class="flex items-start gap-4 px-4 py-3 hover:bg-cream transition">
                        {{-- Date column --}}
                        <div class="shrink-0 w-16 text-center">
                            <div class="text-2xs uppercase text-muted">{{ $apt->starts_at->locale('ro')->shortDayName }}</div>
                            <div class="text-xl font-bold text-ink leading-none">{{ $apt->starts_at->format('d') }}</div>
                            <div class="text-2xs text-muted">{{ $apt->starts_at->locale('ro')->shortMonthName }}</div>
                        </div>
                        {{-- Main info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="font-semibold text-ink truncate">{{ $apt->customer_name ?: '—' }}</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs border {{ $statusColor }}">{{ $statusLabel }}</span>
                                @if($apt->source)
                                    <span class="text-2xs text-muted">via {{ $apt->source }}</span>
                                @endif
                            </div>
                            <div class="text-xs text-muted space-x-2">
                                <span>🕐 {{ $apt->starts_at->format('H:i') }}–{{ $apt->ends_at?->format('H:i') ?: '?' }}</span>
                                @if($apt->serviceType)
                                    <span>· {{ $apt->serviceType->name }}</span>
                                @endif
                                @if($apt->staffMember)
                                    <span>· {{ $apt->staffMember->name }}</span>
                                @endif
                            </div>
                            @if($apt->customer_phone || $apt->customer_email)
                                <div class="mt-1 text-2xs text-muted">
                                    @if($apt->customer_phone)<span class="mr-2">📞 {{ $apt->customer_phone }}</span>@endif
                                    @if($apt->customer_email)<span>✉ {{ $apt->customer_email }}</span>@endif
                                </div>
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-muted shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            {{ $appointments->links() }}
        </div>
    @endif
</div>
@endsection
