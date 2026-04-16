@extends('layouts.admin')
@section('title', 'Costuri & Profitabilitate')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Costuri & Profitabilitate</h1>
    <p class="text-sm text-slate-500 mt-1">
        Agregat din rollup-ul zilnic ({{ $start->format('d M Y') }} → {{ $end->format('d M Y') }}).
        Curs BNR folosit: <span class="font-mono">1 USD = {{ number_format($usdRon, 4) }} RON</span>.
    </p>
</div>

{{-- Filtre --}}
<form method="GET" class="mb-4 bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap items-end gap-3">
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
        <label class="block text-xs font-medium text-slate-600 mb-1">Grupare</label>
        <select name="mode" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="tenant" {{ $mode === 'tenant' ? 'selected' : '' }}>Per tenant</option>
            <option value="bot" {{ $mode === 'bot' ? 'selected' : '' }}>Per agent AI</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Tenant</label>
        <select name="tenant_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Toți —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenantFilter === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    @if($mode === 'bot' && $tenantFilter)
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Agent AI</label>
            <select name="bot_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">— Toți —</option>
                @foreach($bots as $b)
                    <option value="{{ $b->id }}" {{ $botFilter === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
        Aplică
    </button>
</form>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
@endif

{{-- KPI-uri top --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Cost total</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($total['grand_total']/100, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">≈ {{ number_format($total['grand_total']/100*$usdRon, 2) }} lei</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Voice (apeluri)</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($total['voice_total']/100, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $total['calls'] }} apeluri &middot; {{ number_format($total['seconds']/60, 1) }} min
        </p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Chat</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($total['chat_cost']/100, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $total['conversations'] }} conv &middot; {{ $total['messages'] }} mesaje
        </p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Cost / min apel</p>
        @php $perMin = $total['seconds'] > 0 ? ($total['voice_total']/100) / ($total['seconds']/60) : 0; @endphp
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($perMin, 3) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">≈ {{ number_format($perMin*$usdRon, 3) }} lei/min</p>
    </div>
</div>

{{-- Breakdown top-level voice --}}
@if($total['voice_total'] > 0)
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
        <p class="text-xs font-semibold text-slate-600 uppercase mb-2">Defalcare voice</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
            <div>
                <p class="text-slate-500">OpenAI Realtime</p>
                <p class="font-mono font-semibold">${{ number_format($total['voice_openai']/100, 4) }}</p>
            </div>
            <div>
                <p class="text-slate-500">Twilio voice</p>
                <p class="font-mono font-semibold">${{ number_format($total['voice_twilio']/100, 4) }}</p>
            </div>
            <div>
                <p class="text-slate-500">Embeddings</p>
                <p class="font-mono font-semibold">${{ number_format($total['voice_embedding']/100, 4) }}</p>
            </div>
            @if($total['voice_unattributed'] > 0)
                <div title="Cost din apeluri vechi, înainte să tragem breakdown-ul OpenAI/Twilio. Apelurile noi au defalcare completă.">
                    <p class="text-slate-500">Istoric (neatribuit)</p>
                    <p class="font-mono font-semibold text-amber-700">${{ number_format($total['voice_unattributed']/100, 4) }}</p>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- Tabel grupat --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
                <th class="px-4 py-2 text-left">{{ $mode === 'tenant' ? 'Tenant' : 'Agent AI' }}</th>
                @if($mode === 'bot')
                    <th class="px-4 py-2 text-left">Tenant</th>
                @endif
                <th class="px-4 py-2 text-right">Apeluri</th>
                <th class="px-4 py-2 text-right">Minute</th>
                <th class="px-4 py-2 text-right">Voice $</th>
                <th class="px-4 py-2 text-right">Conv / msg</th>
                <th class="px-4 py-2 text-right">Chat $</th>
                <th class="px-4 py-2 text-right">Total $</th>
                <th class="px-4 py-2 text-right">Total lei</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($grouped as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ $r['name'] }}</td>
                    @if($mode === 'bot')
                        <td class="px-4 py-2 text-slate-600 text-xs">{{ $r['tenant_name'] }}</td>
                    @endif
                    <td class="px-4 py-2 text-right tabular-nums">{{ $r['calls'] }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-xs text-slate-500">{{ number_format($r['seconds']/60, 1) }}</td>
                    <td class="px-4 py-2 text-right font-mono">${{ number_format($r['voice']/100, 3) }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-xs text-slate-500">{{ $r['conversations'] }} / {{ $r['messages'] }}</td>
                    <td class="px-4 py-2 text-right font-mono">${{ number_format($r['chat']/100, 3) }}</td>
                    <td class="px-4 py-2 text-right font-mono font-semibold">${{ number_format($r['total']/100, 3) }}</td>
                    <td class="px-4 py-2 text-right font-mono font-semibold text-slate-700">{{ number_format($r['total']/100*$usdRon, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $mode === 'bot' ? 9 : 8 }}" class="px-4 py-8 text-center text-sm text-slate-500">
                        Nicio activitate în perioada selectată. Rulează
                        <code class="bg-slate-100 px-1.5 py-0.5 rounded">php artisan costs:rollup --from=... --to=...</code>
                        dacă aștepți date vechi în rollup.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Platform overhead — AI calls not attached to a tenant conversation --}}
@if($platformTotal > 0)
    <div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-600 uppercase">Overhead platformă</p>
                <p class="text-xs text-slate-500 mt-0.5">API-uri care nu se atribuie unui tenant / conversație: indexare KB, agent web-scan, imagini social, voice cloning.</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500">Total</p>
                <p class="text-lg font-bold text-slate-900 font-mono">${{ number_format($platformTotal/100, 4) }}</p>
                <p class="text-xs text-slate-500">≈ {{ number_format($platformTotal/100*$usdRon, 2) }} lei</p>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Scop</th>
                    <th class="px-4 py-2 text-left">Provider / model</th>
                    <th class="px-4 py-2 text-right">Apeluri API</th>
                    <th class="px-4 py-2 text-right">Cost $</th>
                    <th class="px-4 py-2 text-right">Cost lei</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($platform as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 font-medium text-slate-900">{{ $p->purpose ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-slate-600">{{ $p->provider }} / {{ $p->model }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format($p->n) }}</td>
                        <td class="px-4 py-2 text-right font-mono">${{ number_format($p->cost_cents/100, 4) }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ number_format($p->cost_cents/100*$usdRon, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Re-agregare manuală --}}
<form method="POST" action="{{ route('admin.costs.reaggregate') }}" class="mt-6 bg-slate-50 rounded-xl border border-slate-200 p-4 flex flex-wrap items-end gap-3">
    @csrf
    <div>
        <p class="text-xs font-semibold text-slate-600 uppercase">Re-agregare manuală</p>
        <p class="text-xs text-slate-500 mt-0.5">Pentru backfill sau după webhook-uri întârziate.</p>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">De la</label>
        <input type="date" name="from" value="{{ $start->toDateString() }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" required>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Până la</label>
        <input type="date" name="to" value="{{ $end->toDateString() }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm" required>
    </div>
    <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Rulează
    </button>
</form>
@endsection
