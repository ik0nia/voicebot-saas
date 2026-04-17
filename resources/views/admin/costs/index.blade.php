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

{{-- Filter chip: setup-AI only detail view --}}
<div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
    <a href="{{ route('admin.costs.index', array_merge(request()->except('setup_ai_only', 'page'), ['setup_ai_only' => 1])) }}"
       class="inline-flex items-center gap-1.5 rounded-full border border-red-700 bg-red-50 px-3 py-1 font-medium text-red-800 hover:bg-red-100">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Doar setup AI
    </a>
    <span class="text-slate-500">Afișează activitatea AI de configurare a agenților per-metric, pentru investigare de abuzuri.</span>
</div>

{{-- KPI-uri top --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Cost total</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format(($total['grand_total']+$setupAiTotals['cost_cents'])/100, 2) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">≈ {{ number_format(($total['grand_total']+$setupAiTotals['cost_cents'])/100*$usdRon, 2) }} lei</p>
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
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Configurare agenți (AI)</p>
        <p class="text-lg font-bold text-slate-900 mt-1">${{ number_format($setupAiTotals['cost_usd'], 4) }}</p>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ number_format($setupAiTotals['cost_ron'], 2) }} lei &middot; {{ number_format($setupAiTotals['call_count']) }} apeluri
        </p>
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
                @if($mode === 'tenant')
                    <th class="px-4 py-2 text-right" title="Cost Claude Haiku pentru generare FAQ/reguli/ton în builder.">Setup AI $</th>
                @endif
                <th class="px-4 py-2 text-right">Total $</th>
                <th class="px-4 py-2 text-right">Total lei</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($grouped as $r)
                @php
                    // Tenant-mode rows can carry a per-tenant setup-AI
                    // cost; bot-mode rows can't (setup-AI isn't part
                    // of the DailyCostRollup) — leave the column off.
                    $setupRow = $mode === 'tenant' ? ($setupAiByTenant[$r['id']] ?? null) : null;
                    $setupCents = $setupRow?->cost_cents ?? 0;
                    $setup30d = $mode === 'tenant' ? ($setupAi30dByTenant[$r['id']] ?? null) : null;
                    $setup30dRon = $setup30d?->cost_ron ?? 0;
                    $highlight = match(true) {
                        $setup30dRon > 50 => 'bg-red-50 border-l-4 border-red-500',
                        $setup30dRon > 10 => 'bg-amber-50 border-l-4 border-amber-500',
                        default => '',
                    };
                    $totalCents = $r['total'] + $setupCents;
                @endphp
                <tr class="hover:bg-slate-50 {{ $highlight }}">
                    <td class="px-4 py-2 font-medium text-slate-900">
                        {{ $r['name'] }}
                        @if($mode === 'tenant' && $setup30dRon > 10)
                            <span class="ml-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                {{ $setup30dRon > 50 ? 'bg-red-200 text-red-900' : 'bg-amber-200 text-amber-900' }}"
                                title="Ultimele 30 zile: {{ number_format($setup30dRon, 2) }} lei pe setup-AI">
                                {{ number_format($setup30dRon, 1) }} lei / 30d
                            </span>
                        @endif
                    </td>
                    @if($mode === 'bot')
                        <td class="px-4 py-2 text-slate-600 text-xs">{{ $r['tenant_name'] }}</td>
                    @endif
                    <td class="px-4 py-2 text-right tabular-nums">{{ $r['calls'] }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-xs text-slate-500">{{ number_format($r['seconds']/60, 1) }}</td>
                    <td class="px-4 py-2 text-right font-mono">${{ number_format($r['voice']/100, 3) }}</td>
                    <td class="px-4 py-2 text-right tabular-nums text-xs text-slate-500">{{ $r['conversations'] }} / {{ $r['messages'] }}</td>
                    <td class="px-4 py-2 text-right font-mono">${{ number_format($r['chat']/100, 3) }}</td>
                    @if($mode === 'tenant')
                        <td class="px-4 py-2 text-right font-mono text-xs">
                            @if($setupCents > 0)
                                <a href="{{ route('admin.costs.index', array_merge(request()->except('page'), ['setup_ai_only' => 1, 'tenant_id' => $r['id']])) }}"
                                   class="text-red-700 hover:underline">
                                    ${{ number_format($setupCents/100, 4) }}
                                </a>
                                <span class="block text-[10px] text-slate-500">{{ $setupRow->call_count }} req</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="px-4 py-2 text-right font-mono font-semibold">${{ number_format($totalCents/100, 3) }}</td>
                    <td class="px-4 py-2 text-right font-mono font-semibold text-slate-700">{{ number_format($totalCents/100*$usdRon, 2) }}</td>
                </tr>
            @empty
                @php
                    // 1 extra col when mode=bot (tenant_name), 1 extra
                    // col when mode=tenant (setup-AI). Base 8 columns.
                    $emptyCols = 8 + ($mode === 'bot' ? 1 : 0) + ($mode === 'tenant' ? 1 : 0);
                @endphp
                <tr>
                    <td colspan="{{ $emptyCols }}" class="px-4 py-8 text-center text-sm text-slate-500">
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

{{-- Top abusers panel --}}
@if($topAbusers['by_cost']->isNotEmpty() || $topAbusers['by_count']->isNotEmpty())
    <div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-hidden"
         x-data="{ openTenantId: null, limitValue: '' }">
        <div class="px-4 py-3 border-b border-slate-200">
            <p class="text-xs font-semibold text-slate-600 uppercase">Setup AI — top consumatori (ultimele 30 zile)</p>
            <p class="text-xs text-slate-500 mt-0.5">
                Fereastră: {{ $topAbusers['window_start']->format('d M') }} → {{ $topAbusers['window_end']->format('d M Y') }}.
                Limita curentă e persistată pe <code class="bg-slate-100 px-1.5 py-0.5 rounded">tenant.settings.setup_ai_limit_ron</code>
                — enforcement-ul efectiv e muncă viitoare.
            </p>
        </div>
        <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200">
            {{-- By cost --}}
            <div class="p-4">
                <p class="text-xs font-semibold text-slate-700 mb-2">Top 5 după cost</p>
                @if($topAbusers['by_cost']->isEmpty())
                    <p class="text-xs text-slate-400">Niciun tenant cu cost setup-AI &gt; 0 în perioada selectată.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="text-left pb-1">Tenant</th>
                                <th class="text-right pb-1">Cost</th>
                                <th class="text-right pb-1">Apeluri</th>
                                <th class="text-right pb-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAbusers['by_cost'] as $row)
                                @php
                                    $tenant = $abuserTenants[$row->tenant_id] ?? null;
                                    $currentLimit = $tenant?->settings['setup_ai_limit_ron'] ?? null;
                                @endphp
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 font-medium text-slate-900">
                                        {{ $tenant?->name ?? '#'.$row->tenant_id }}
                                        @if($currentLimit !== null)
                                            <span class="ml-1 text-[10px] text-slate-500">(limită: {{ number_format((float)$currentLimit, 2) }} lei)</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right font-mono">{{ number_format($row->cost_ron, 2) }} lei</td>
                                    <td class="py-2 text-right tabular-nums text-xs text-slate-500">{{ number_format($row->call_count) }}</td>
                                    <td class="py-2 text-right">
                                        <button type="button"
                                                @click="openTenantId = openTenantId === {{ $row->tenant_id }} ? null : {{ $row->tenant_id }}; limitValue = '{{ $currentLimit ?? '' }}'"
                                                class="text-xs rounded border border-slate-300 bg-white px-2 py-1 hover:bg-slate-50">
                                            Set limită
                                        </button>
                                    </td>
                                </tr>
                                <tr x-show="openTenantId === {{ $row->tenant_id }}" x-cloak>
                                    <td colspan="4" class="bg-slate-50 px-3 py-3">
                                        <form method="POST" action="{{ route('admin.costs.setupAiLimit', ['tenantId' => $row->tenant_id]) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-600 uppercase mb-0.5">Max setup-AI RON / lună</label>
                                                <input type="number" step="0.01" min="0" name="max_setup_ai_ron_per_month"
                                                       x-model="limitValue"
                                                       class="rounded border border-slate-300 px-2 py-1 text-sm font-mono w-32" required>
                                            </div>
                                            <button type="submit" class="rounded bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-800">Salvează</button>
                                            <span class="text-xs text-slate-500">Enforcement în follow-up; acum doar persistă valoarea.</span>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            {{-- By count --}}
            <div class="p-4">
                <p class="text-xs font-semibold text-slate-700 mb-2">Top 5 după număr de apeluri</p>
                @if($topAbusers['by_count']->isEmpty())
                    <p class="text-xs text-slate-400">Niciun tenant cu apeluri setup-AI în perioada selectată.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr>
                                <th class="text-left pb-1">Tenant</th>
                                <th class="text-right pb-1">Apeluri</th>
                                <th class="text-right pb-1">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAbusers['by_count'] as $row)
                                @php $tenant = $abuserTenants[$row->tenant_id] ?? null; @endphp
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 font-medium text-slate-900">{{ $tenant?->name ?? '#'.$row->tenant_id }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($row->call_count) }}</td>
                                    <td class="py-2 text-right font-mono text-xs text-slate-500">{{ number_format($row->cost_ron, 2) }} lei</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- Niche demo landing pages — conversion funnel panel (Iteration F) --}}
@if(!empty($demoStats))
<div class="mt-8 bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Demo pe landing pages</h2>
            <p class="text-xs text-slate-500 mt-0.5">Ultimele 30 zile · Flag <code class="px-1 py-0.5 bg-slate-100 rounded">NICHE_PUBLIC_DEMO_ENABLED</code></p>
        </div>
        <a href="{{ route('admin.demo.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
            Vezi detaliat →
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
        <div class="bg-slate-50 rounded-lg p-3">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Demo-uri vizualizate</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($demoStats['viewed']) }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-3">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Mesaje trimise</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($demoStats['messages']) }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-3">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Calificate (≥3 mesaje)</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($demoStats['qualified']) }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-3">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Rată calificare</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $demoStats['rate'] }}%</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-3">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wide">Înregistrări (demo → signup)</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($demoStats['signups']) }}</div>
        </div>
    </div>

    @if(!empty($demoStats['by_niche']))
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase border-b border-slate-200">
                        <th class="px-3 py-2">Nișă</th>
                        <th class="px-3 py-2 text-right">Vizualizate</th>
                        <th class="px-3 py-2 text-right">Mesaje</th>
                        <th class="px-3 py-2 text-right">Calificate</th>
                        <th class="px-3 py-2 text-right">Rata</th>
                        <th class="px-3 py-2 text-right">Signups</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($demoStats['by_niche'], 0, 3) as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2 font-mono text-xs">{{ $row['niche'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row['viewed']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-slate-500">{{ number_format($row['messages']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-emerald-600 font-semibold">{{ number_format($row['qualified']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $row['rate'] }}%</td>
                            <td class="px-3 py-2 text-right tabular-nums text-indigo-600 font-semibold">{{ number_format($row['signups']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-xs text-slate-500">Încă niciun eveniment — ori flag-ul e oprit, ori landing-urile încă n-au primit trafic în ultimele 30 de zile.</p>
    @endif
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
