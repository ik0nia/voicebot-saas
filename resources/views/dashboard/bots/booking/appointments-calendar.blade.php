@extends('layouts.dashboard')

@section('title', 'Calendar — ' . $bot->name)

@section('content')
@php
    use Carbon\Carbon;
    $monthStart = $cursor->copy()->startOfMonth();
    $monthEnd = $cursor->copy()->endOfMonth();
    // Calendar grid începe de luni (ISO weekday 1).
    $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
    $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

    // Group by Y-m-d.
    $byDay = [];
    foreach ($appointments as $apt) {
        $key = $apt->starts_at->format('Y-m-d');
        $byDay[$key] = $byDay[$key] ?? [];
        $byDay[$key][] = $apt;
    }
@endphp

<div class="max-w-7xl mx-auto py-6 px-4">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <div class="text-xs text-muted mb-1">
                <a href="{{ route('dashboard.bots.booking.appointments', $bot) }}" class="hover:text-coralh">← Listă programări</a>
            </div>
            <h1 class="text-2xl font-bold text-ink">Calendar programări</h1>
            <p class="text-sm text-muted mt-1">Vedere lunară. Click pe o programare pentru detalii.</p>
        </div>
        <a href="{{ route('dashboard.bots.booking.appointments', $bot) }}"
           class="text-xs font-medium text-muted hover:text-ink border border-line rounded-lg px-3 py-1.5 hover:bg-cream">
            ☰ Vedere listă
        </a>
    </div>

    {{-- Month nav --}}
    <div class="flex items-center justify-between mb-4 bg-white rounded-xl border border-line p-3">
        <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'view' => 'calendar', 'month' => $cursor->copy()->subMonth()->format('Y-m')]) }}"
           class="text-sm font-medium text-muted hover:text-ink px-3 py-1 rounded hover:bg-cream">
            ← {{ $cursor->copy()->subMonth()->locale('ro')->translatedFormat('F') }}
        </a>
        <div class="text-center">
            <h2 class="text-lg font-bold text-ink">{{ $cursor->locale('ro')->translatedFormat('F Y') }}</h2>
            @if(!$cursor->isSameMonth(now()))
                <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'view' => 'calendar', 'month' => now()->format('Y-m')]) }}"
                   class="text-xs text-coralh hover:underline">Du-mă la luna curentă</a>
            @endif
        </div>
        <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'view' => 'calendar', 'month' => $cursor->copy()->addMonth()->format('Y-m')]) }}"
           class="text-sm font-medium text-muted hover:text-ink px-3 py-1 rounded hover:bg-cream">
            {{ $cursor->copy()->addMonth()->locale('ro')->translatedFormat('F') }} →
        </a>
    </div>

    {{-- Calendar grid --}}
    <div class="bg-white rounded-xl border border-line overflow-hidden">
        <div class="grid grid-cols-7 bg-cream text-xs font-semibold text-muted uppercase">
            @foreach(['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'] as $day)
                <div class="px-2 py-2 text-center border-r border-line last:border-r-0">{{ $day }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7 divide-x divide-y divide-line">
            @php $cell = $gridStart->copy(); @endphp
            @while($cell->lessThanOrEqualTo($gridEnd))
                @php
                    $key = $cell->format('Y-m-d');
                    $isOutMonth = !$cell->isSameMonth($cursor);
                    $isToday = $cell->isToday();
                    $dayApts = $byDay[$key] ?? [];
                @endphp
                <div class="min-h-[110px] p-1.5 relative {{ $isOutMonth ? 'bg-cream/30' : '' }} {{ $isToday ? 'ring-2 ring-coral/40 ring-inset' : '' }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-2xs font-bold {{ $isOutMonth ? 'text-muted/60' : ($isToday ? 'text-coralh' : 'text-ink') }}">{{ $cell->format('d') }}</span>
                        @if(count($dayApts) > 0)
                            <span class="text-2xs text-muted">{{ count($dayApts) }}</span>
                        @endif
                    </div>
                    @foreach(array_slice($dayApts, 0, 3) as $apt)
                        @php
                            $color = match($apt->status) {
                                'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'confirmed', 'reminder_sent' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'requested' => 'bg-amber-100 text-amber-900 border-amber-200',
                                'canceled', 'noshow' => 'bg-coralsoft text-coralh border-coral/30 line-through opacity-60',
                                default => 'bg-cream text-muted border-line',
                            };
                        @endphp
                        <a href="{{ route('dashboard.bots.booking.appointment', [$bot, $apt]) }}"
                           class="block text-2xs px-1.5 py-0.5 rounded border mb-0.5 truncate hover:shadow-sm transition {{ $color }}"
                           :title="'{{ $apt->starts_at->format('H:i') }} {{ addslashes($apt->customer_name ?: 'Programare') }}'">
                            <span class="font-semibold">{{ $apt->starts_at->format('H:i') }}</span>
                            <span class="ml-1">{{ \Illuminate\Support\Str::limit($apt->customer_name ?: 'Programare', 12) }}</span>
                        </a>
                    @endforeach
                    @if(count($dayApts) > 3)
                        <a href="{{ route('dashboard.bots.booking.appointments', ['bot' => $bot, 'status' => 'upcoming']) }}#{{ $key }}"
                           class="block text-2xs text-coralh hover:underline mt-0.5">
                            +{{ count($dayApts) - 3 }} altele
                        </a>
                    @endif
                </div>
                @php $cell->addDay(); @endphp
            @endwhile
        </div>
    </div>

    {{-- Legendă --}}
    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-muted">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-200"></span> Cerută</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-100 border border-blue-200"></span> Confirmată</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-200"></span> Finalizată</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-coralsoft border border-coral/30"></span> Anulată/no-show</span>
    </div>
</div>
@endsection
