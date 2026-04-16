@extends('layouts.admin')

@section('title', 'Twilio — Consum per client')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Consum Twilio per client</h1>
        <p class="mt-1 text-sm text-slate-500">
            Live, direct din Twilio Usage API. Fiecare rând e un subaccount provisionat pentru un tenant.
            Cache 5 minute per tenant ca să nu ardem API quota Twilio.
        </p>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <label class="text-sm text-slate-700 flex items-center gap-2">
            Fereastra
            <select name="window" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                <option value="today"     {{ $window === 'today' ? 'selected' : '' }}>Azi</option>
                <option value="yesterday" {{ $window === 'yesterday' ? 'selected' : '' }}>Ieri</option>
                <option value="thisMonth" {{ $window === 'thisMonth' ? 'selected' : '' }}>Luna curentă</option>
                <option value="lastMonth" {{ $window === 'lastMonth' ? 'selected' : '' }}>Luna trecută</option>
                <option value="allTime"   {{ $window === 'allTime' ? 'selected' : '' }}>Tot</option>
            </select>
        </label>
        <button class="rounded-lg bg-red-600 text-white px-4 py-1.5 text-sm hover:bg-red-700">Aplică</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Tenant</th>
                    <th class="px-4 py-3 text-right font-semibold">Minute calls</th>
                    <th class="px-4 py-3 text-right font-semibold">Inbound ($)</th>
                    <th class="px-4 py-3 text-right font-semibold">Outbound ($)</th>
                    <th class="px-4 py-3 text-right font-semibold">SMS</th>
                    <th class="px-4 py-3 text-right font-semibold">Total USD</th>
                    <th class="px-4 py-3 text-right font-semibold">Numere</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ $r['tenant_name'] }}
                            <div class="text-xs text-slate-400">#{{ $r['tenant_id'] }}</div>
                        </td>
                        @if(isset($r['error']))
                            <td colspan="6" class="px-4 py-3 text-xs text-red-600">eroare: {{ $r['error'] }}</td>
                        @else
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format(($r['calls_seconds'] ?? 0) / 60, 1) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($r['inbound_price_usd'] ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($r['outbound_price_usd'] ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $r['sms_count'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold">${{ number_format($r['total_price_usd'] ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $r['numbers_owned'] ?? 0 }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                            Niciun tenant nu are încă subaccount Twilio. Subaccount-urile se creează automat
                            la prima provisionare de număr.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
