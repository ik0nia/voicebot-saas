@extends('layouts.admin')
@section('title', 'Costuri — Setup AI (detaliat)')

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Setup AI — detaliat</h1>
        <p class="text-sm text-slate-500 mt-1">
            Fiecare apel AI triggerat din UI-ul de configurare a agenților ({{ $start->format('d M Y') }} → {{ $end->format('d M Y') }}).
            Curs BNR: <span class="font-mono">1 USD = {{ number_format($usdRon, 4) }} RON</span>.
        </p>
    </div>
    <a href="{{ route('admin.costs.index', request()->except('setup_ai_only', 'page')) }}"
       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
        ← Înapoi la sumar
    </a>
</div>

{{-- Filtre --}}
<form method="GET" class="mb-4 bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap items-end gap-3">
    <input type="hidden" name="setup_ai_only" value="1">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Perioada</label>
        <select name="period" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @foreach(['today'=>'Azi','yesterday'=>'Ieri','this_week'=>'Săptămâna asta','last_week'=>'Săptămâna trecută','this_month'=>'Luna asta','last_month'=>'Luna trecută','last_30'=>'Ultimele 30 zile','custom'=>'Interval custom'] as $k => $label)
                <option value="{{ $k }}" {{ $period === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if($period === 'custom')
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">De la</label>
            <input type="date" name="from" value="{{ $start->toDateString() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Până la</label>
            <input type="date" name="to" value="{{ $end->toDateString() }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
    @endif
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Tenant</label>
        <select name="tenant_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Toți —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenantFilter === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    @if($botFilter)
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Bot ID</label>
            <input type="number" name="bot_id" value="{{ $botFilter }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-24">
        </div>
    @endif
    @if($userFilter)
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">User ID</label>
            <input type="number" name="user_id" value="{{ $userFilter }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-24">
        </div>
    @endif
    <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
        Aplică
    </button>
</form>

{{-- Totals strip --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Apeluri</p>
        <p class="text-lg font-bold text-slate-900 mt-1">{{ number_format($totals['call_count']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Cost USD</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($totals['cost_usd'], 4) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Cost RON</p>
        <p class="text-lg font-bold text-slate-900 mt-1">{{ number_format($totals['cost_ron'], 2) }} lei</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Tokens in / out</p>
        <p class="text-lg font-bold text-slate-900 mt-1">{{ number_format($totals['tokens_in']) }} / {{ number_format($totals['tokens_out']) }}</p>
    </div>
</div>

{{-- Detail table --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
                <th class="px-3 py-2 text-left">Timestamp</th>
                <th class="px-3 py-2 text-left">Tenant</th>
                <th class="px-3 py-2 text-left">Bot</th>
                <th class="px-3 py-2 text-left">User</th>
                <th class="px-3 py-2 text-left">Target</th>
                <th class="px-3 py-2 text-left">Model</th>
                <th class="px-3 py-2 text-right">Tokens (in/out/cache)</th>
                <th class="px-3 py-2 text-right">Cost USD</th>
                <th class="px-3 py-2 text-right">Cost RON</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                @php
                    $target = $row->metadata['target'] ?? null;
                    $cachedTokens = $row->metadata['cached_tokens'] ?? null;
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2 text-xs text-slate-600 tabular-nums whitespace-nowrap">{{ $row->created_at->format('d M H:i:s') }}</td>
                    <td class="px-3 py-2 text-slate-900">{{ $row->tenant?->name ?? '#'.$row->tenant_id }}</td>
                    <td class="px-3 py-2">
                        @if($row->bot)
                            <span class="text-slate-900">{{ $row->bot->name }}</span>
                            <span class="text-xs text-slate-400">#{{ $row->bot->id }}</span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-xs">
                        @if($row->user)
                            <span class="text-slate-900">{{ $row->user->name ?: $row->user->email }}</span>
                            <span class="block text-[10px] text-slate-400">#{{ $row->user->id }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-xs">
                        @if($target)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[10px] text-slate-700">{{ $target }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-600 font-mono">{{ $row->model }}</td>
                    <td class="px-3 py-2 text-right text-xs tabular-nums text-slate-600">
                        {{ number_format($row->input_tokens) }} / {{ number_format($row->output_tokens) }}{{ $cachedTokens !== null ? ' / '.number_format($cachedTokens) : '' }}
                    </td>
                    <td class="px-3 py-2 text-right font-mono text-xs">${{ number_format($row->cost_cents/100, 4) }}</td>
                    <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format(($row->cost_cents/100)*$usdRon, 4) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">
                        Niciun apel setup-AI în perioada selectată.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($rows->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
