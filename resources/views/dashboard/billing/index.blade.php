@extends('layouts.dashboard')

@section('title', 'Facturare')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Facturare</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Facturare &amp; Utilizare</h1>
        <p class="mt-1 text-sm text-slate-500">Monitorizează utilizarea și gestionează planul tău.</p>
    </div>

    @if(!$tenant || !$usage)
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-slate-500">Nu există informații de facturare disponibile.</p>
        </div>
    @else

    {{-- Current Plan Card --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-red-50 text-red-800">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-xl font-bold text-slate-900">{{ $usage['plan']['name'] }}</h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-800">
                            {{ ucfirst($usage['plan']['slug']) }}
                        </span>
                    </div>
                    <p class="mt-1 text-3xl font-extrabold text-slate-900">
                        {{ number_format($usage['plan']['price_monthly'], 0) }}<span class="text-base font-medium text-slate-500">&euro;/lună</span>
                    </p>
                </div>
            </div>
            <div class="flex flex-col sm:items-end gap-2">
                @if($tenant->isOnTrial())
                    @php $trialDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false); @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Perioadă de probă &mdash; mai ai {{ $trialDaysLeft }} {{ $trialDaysLeft == 1 ? 'zi' : 'zile' }}
                    </span>
                @endif
                <p class="text-sm text-slate-500">Perioada: <span class="font-medium text-slate-700">{{ $usage['period'] }}</span></p>
                <a href="/preturi" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                    Schimbă planul
                </a>
            </div>
        </div>
    </div>

    {{-- Usage Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Messages Usage --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Mesaje</h3>
                </div>
                <span class="text-xs font-semibold {{ $usage['messages']['percent'] >= 90 ? 'text-red-600' : ($usage['messages']['percent'] >= 70 ? 'text-yellow-600' : 'text-slate-500') }}">
                    {{ $usage['messages']['percent'] }}%
                </span>
            </div>
            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden mb-2">
                <div class="h-2 rounded-full transition-all {{ $usage['messages']['percent'] >= 90 ? 'bg-red-500' : ($usage['messages']['percent'] >= 70 ? 'bg-yellow-500' : 'bg-emerald-500') }}"
                     style="width: {{ min($usage['messages']['percent'], 100) }}%"></div>
            </div>
            <p class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ number_format($usage['messages']['used']) }}</span>
                / {{ number_format($usage['messages']['limit']) }} mesaje
            </p>
            @if($usage['messages']['overage'] > 0)
                <p class="text-xs text-red-600 font-medium mt-1">
                    +{{ number_format($usage['messages']['overage']) }} extra &middot; &euro;{{ number_format($usage['messages']['overage_cost'], 2) }} overage
                </p>
            @endif
            @if($usage['messages']['overage_unit_cost'] > 0)
                <p class="text-xs text-slate-400 mt-1">Suplimentar: &euro;{{ number_format($usage['messages']['overage_unit_cost'], 2) }}/mesaj</p>
            @endif
        </div>

        {{-- Bots Usage --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Chatboți</h3>
                </div>
                <span class="text-xs font-semibold {{ $usage['bots']['percent'] >= 100 ? 'text-red-600' : 'text-slate-500' }}">
                    {{ $usage['bots']['percent'] }}%
                </span>
            </div>
            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden mb-2">
                <div class="h-2 rounded-full transition-all {{ $usage['bots']['percent'] >= 100 ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ min($usage['bots']['percent'], 100) }}%"></div>
            </div>
            <p class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ $usage['bots']['used'] }}</span>
                / {{ $usage['bots']['limit'] }} boți incluși
            </p>
            @if($usage['bots']['used'] > $usage['bots']['limit'])
                <p class="text-xs text-red-600 font-medium mt-1">
                    +{{ $usage['bots']['used'] - $usage['bots']['limit'] }} extra &middot; &euro;{{ number_format(($usage['bots']['used'] - $usage['bots']['limit']) * $usage['bots']['overage_unit_cost'], 0) }}/lună overage
                </p>
            @endif
            @if($usage['bots']['overage_unit_cost'] > 0)
                <p class="text-xs text-slate-400 mt-1">Bot suplimentar: &euro;{{ number_format($usage['bots']['overage_unit_cost'], 0) }}/lună</p>
            @endif
        </div>

        {{-- Voice Minutes --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Minute voce</h3>
                </div>
                @if($usage['voice_minutes']['has_voice'])
                    <span class="text-xs font-semibold {{ $usage['voice_minutes']['percent'] >= 90 ? 'text-red-600' : 'text-slate-500' }}">
                        {{ $usage['voice_minutes']['percent'] }}%
                    </span>
                @endif
            </div>
            @if($usage['voice_minutes']['has_voice'])
                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden mb-2">
                    <div class="h-2 rounded-full transition-all {{ $usage['voice_minutes']['percent'] >= 90 ? 'bg-red-500' : 'bg-purple-500' }}"
                         style="width: {{ min($usage['voice_minutes']['percent'], 100) }}%"></div>
                </div>
                <p class="text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">{{ number_format($usage['voice_minutes']['used']) }}</span>
                    / {{ $usage['voice_minutes']['limit'] == -1 ? 'nelimitat' : number_format($usage['voice_minutes']['limit']) }} minute
                </p>
            @else
                <div class="mt-2">
                    <p class="text-sm text-slate-500">Fără addon de voce activ.</p>
                    <a href="/preturi#voice" class="text-xs text-red-700 font-medium hover:underline mt-1 inline-block">Adaugă pachet voce &rarr;</a>
                </div>
            @endif
        </div>

    </div>

    {{-- Detailed Usage Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-base font-semibold text-slate-900">Utilizare detaliată</h3>
        </div>
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Resursă</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Utilizat</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Limită</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Utilizare</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach([
                    ['Mesaje', $usage['messages']['used'], $usage['messages']['limit'], $usage['messages']['percent']],
                    ['Chatboți', $usage['bots']['used'], $usage['bots']['limit'], $usage['bots']['percent']],
                    ['Minute voce', $usage['voice_minutes']['used'], $usage['voice_minutes']['has_voice'] ? ($usage['voice_minutes']['limit'] == -1 ? '∞' : $usage['voice_minutes']['limit']) : 'N/A', $usage['voice_minutes']['percent']],
                    ['Site-uri', $usage['sites']['used'], $usage['sites']['limit'], $usage['sites']['percent']],
                    ['Rulări agenți AI', $usage['agent_runs']['used'], $usage['agent_runs']['limit'], $usage['agent_runs']['percent']],
                    ['Pagini scanate', $usage['pages_scanned']['used'], $usage['pages_scanned']['limit'], $usage['pages_scanned']['percent']],
                    ['Conectori', $usage['connectors']['used'], $usage['connectors']['limit'], $usage['connectors']['percent']],
                ] as [$label, $used, $limit, $percent])
                <tr>
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">{{ $label }}</td>
                    <td class="px-6 py-3 text-sm text-right text-slate-900 font-semibold">{{ number_format($used) }}</td>
                    <td class="px-6 py-3 text-sm text-right text-slate-600">{{ is_numeric($limit) ? number_format($limit) : $limit }}</td>
                    <td class="px-6 py-3 text-right">
                        <div class="inline-flex items-center gap-2">
                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $percent >= 90 ? 'bg-red-500' : ($percent >= 70 ? 'bg-yellow-500' : 'bg-emerald-500') }}"
                                     style="width: {{ min($percent, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium {{ $percent >= 90 ? 'text-red-600' : 'text-slate-500' }}">{{ $percent }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Overage Summary --}}
    @php
        $totalOverage = ($usage['messages']['overage_cost'] ?? 0);
        $botOverage = max(0, $usage['bots']['used'] - $usage['bots']['limit']) * ($usage['bots']['overage_unit_cost'] ?? 0);
        $totalOverage += $botOverage;
    @endphp
    @if($totalOverage > 0)
    <div class="rounded-xl border border-red-200 bg-red-50 p-6">
        <div class="flex items-center gap-3 mb-3">
            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <h3 class="text-base font-semibold text-red-900">Cost suplimentar luna aceasta</h3>
        </div>
        <div class="space-y-1 text-sm text-red-800">
            @if(($usage['messages']['overage_cost'] ?? 0) > 0)
                <div class="flex justify-between">
                    <span>{{ number_format($usage['messages']['overage']) }} mesaje extra &times; &euro;{{ number_format($usage['messages']['overage_unit_cost'], 2) }}</span>
                    <span class="font-semibold">&euro;{{ number_format($usage['messages']['overage_cost'], 2) }}</span>
                </div>
            @endif
            @if($botOverage > 0)
                <div class="flex justify-between">
                    <span>{{ max(0, $usage['bots']['used'] - $usage['bots']['limit']) }} boți extra &times; &euro;{{ number_format($usage['bots']['overage_unit_cost'], 0) }}/lună</span>
                    <span class="font-semibold">&euro;{{ number_format($botOverage, 0) }}</span>
                </div>
            @endif
            <div class="flex justify-between pt-2 border-t border-red-200 font-bold">
                <span>Total overage</span>
                <span>&euro;{{ number_format($totalOverage, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    @endif

</div>
@endsection
