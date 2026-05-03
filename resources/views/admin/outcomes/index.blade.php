@extends('layouts.admin')
@section('title', 'Venituri & outcomes')

@section('content')
@php
    $revenueRon = ($total['revenue_booked_cents'] / 100) * $usdRon;
    $spendRon = ($spendCents / 100) * $usdRon;
    $ratio = $spendCents > 0 ? ($total['revenue_booked_cents'] / $spendCents) : null;
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-ink">Venituri & outcomes</h1>
    <p class="text-sm text-muted mt-1">
        Rollup zilnic de ce a livrat agentul ({{ $start->format('d M Y') }} → {{ $end->format('d M Y') }}).
        Curs BNR: <span class="font-mono">1 USD = {{ number_format($usdRon, 4) }} RON</span>.
    </p>
</div>

{{-- Filtre --}}
<form method="GET" class="mb-4 bg-white rounded-xl border border-line p-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-muted mb-1">Perioada</label>
        <select name="period" onchange="this.form.submit()" class="rounded-lg border border-line px-3 py-2 text-sm">
            @foreach(['today'=>'Azi','yesterday'=>'Ieri','this_week'=>'Săptămâna asta','last_week'=>'Săptămâna trecută','this_month'=>'Luna asta','last_month'=>'Luna trecută','last_30'=>'Ultimele 30 zile','custom'=>'Interval custom'] as $k => $label)
                <option value="{{ $k }}" {{ $period === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if($period === 'custom')
        <div>
            <label class="block text-xs font-medium text-muted mb-1">De la</label>
            <input type="date" name="from" value="{{ $start->toDateString() }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-muted mb-1">Până la</label>
            <input type="date" name="to" value="{{ $end->toDateString() }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
    @endif
    <div>
        <label class="block text-xs font-medium text-muted mb-1">Grupare</label>
        <select name="mode" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="tenant" {{ $mode === 'tenant' ? 'selected' : '' }}>Per tenant</option>
            <option value="bot" {{ $mode === 'bot' ? 'selected' : '' }}>Per agent AI</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-muted mb-1">Tenant</label>
        <select name="tenant_id" class="rounded-lg border border-line px-3 py-2 text-sm">
            <option value="">— Toți —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenantFilter === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-coral">Aplică</button>
</form>

{{-- Spend vs value card --}}
<div class="bg-white rounded-xl border border-line p-5 mb-4">
    <p class="text-xs font-semibold text-muted uppercase mb-3">Cheltuit vs încasat</p>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <p class="text-xs text-muted">Cheltuit (AI + telefonie)</p>
            <p class="text-xl font-bold text-ink mt-1 font-mono">{{ number_format($spendCents/100, 2) }} $</p>
            <p class="text-xs text-muted">≈ {{ number_format($spendRon, 2) }} lei</p>
        </div>
        <div>
            <p class="text-xs text-muted">Încasat (venit atribuit)</p>
            <p class="text-xl font-bold text-emerald-700 mt-1 font-mono">{{ number_format($total['revenue_booked_cents']/100, 2) }} $</p>
            <p class="text-xs text-muted">≈ {{ number_format($revenueRon, 2) }} lei</p>
        </div>
        <div>
            <p class="text-xs text-muted">Raport venit / cost</p>
            <p class="text-xl font-bold text-ink mt-1">
                @if($ratio !== null)
                    {{ number_format($ratio, 2) }}×
                @else
                    —
                @endif
            </p>
            <p class="text-xs text-muted">&nbsp;</p>
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-line p-4">
        <p class="text-xs text-muted uppercase font-semibold">Programări</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $total['bookings_requested'] }}</p>
        <p class="text-xs text-emerald-600">{{ $total['bookings_confirmed'] }} confirmate</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <p class="text-xs text-muted uppercase font-semibold">Lead-uri</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $total['leads_generated'] }}</p>
        <p class="text-xs text-muted">{{ $total['callbacks_requested'] }} callback-uri</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <p class="text-xs text-muted uppercase font-semibold">Comenzi influențate</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $total['orders_influenced'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <p class="text-xs text-muted uppercase font-semibold">Conversații</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $total['conversations_count'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-4">
        <p class="text-xs text-muted uppercase font-semibold">Apeluri voce</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $total['voice_calls_count'] }}</p>
    </div>
</div>

{{-- Tabel grupat --}}
<div class="bg-white rounded-xl border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-cream text-muted text-xs uppercase">
            <tr>
                <th class="px-4 py-2 text-left">{{ $mode === 'tenant' ? 'Tenant' : 'Agent AI' }}</th>
                @if($mode === 'bot')<th class="px-4 py-2 text-left">Tenant</th>@endif
                <th class="px-4 py-2 text-right">Programări</th>
                <th class="px-4 py-2 text-right">Lead-uri</th>
                <th class="px-4 py-2 text-right">Comenzi</th>
                <th class="px-4 py-2 text-right">Venit RON</th>
                <th class="px-4 py-2 text-right">Conversații</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($grouped as $r)
                <tr class="hover:bg-cream">
                    <td class="px-4 py-2 font-medium text-ink">{{ $r['name'] }}</td>
                    @if($mode === 'bot')<td class="px-4 py-2 text-xs text-muted">{{ $r['tenant_name'] }}</td>@endif
                    <td class="px-4 py-2 text-right tabular-nums">{{ $r['bookings_requested'] }} <span class="text-xs text-muted">/ {{ $r['bookings_confirmed'] }}</span></td>
                    <td class="px-4 py-2 text-right tabular-nums">{{ $r['leads_generated'] }}</td>
                    <td class="px-4 py-2 text-right tabular-nums">{{ $r['orders_influenced'] }}</td>
                    <td class="px-4 py-2 text-right font-mono font-semibold text-emerald-700">{{ number_format($r['revenue_booked_cents']/100*$usdRon, 2) }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-xs text-muted">{{ $r['conversations_count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $mode === 'bot' ? 7 : 6 }}" class="px-4 py-8 text-center text-sm text-muted">
                    Nicio activitate pentru perioada selectată. Rulează <code class="bg-cream px-1.5 py-0.5 rounded">php artisan outcomes:rollup --from=... --to=...</code>.
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
