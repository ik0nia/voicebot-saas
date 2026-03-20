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
        <h1 class="text-2xl font-bold text-slate-900">Facturare &amp; Abonament</h1>
        <p class="mt-1 text-sm text-slate-500">Gestionează planul, utilizarea și metoda de plată.</p>
    </div>

    {{-- Current Plan Card --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl
                    @if($plan === 'enterprise') bg-amber-50 text-amber-600
                    @elseif($plan === 'professional') bg-red-50 text-red-800
                    @else bg-slate-100 text-slate-600
                    @endif
                ">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-xl font-bold text-slate-900">{{ $planLimits['name'] ?? 'Starter' }}</h2>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            @if($plan === 'enterprise') bg-amber-100 text-amber-700
                            @elseif($plan === 'professional') bg-red-100 text-red-800
                            @else bg-slate-100 text-slate-600
                            @endif
                        ">
                            @if($plan === 'enterprise') Enterprise
                            @elseif($plan === 'professional') Profesional
                            @else Starter
                            @endif
                        </span>
                    </div>
                    @if($plan !== 'enterprise')
                        <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $monthlyCost }}<span class="text-base font-medium text-slate-500">€/lună</span></p>
                    @else
                        <p class="mt-1 text-lg font-semibold text-slate-600">Plan personalizat</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:items-end gap-2">
                @if($tenant->isOnTrial())
                    @php
                        $trialDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Perioadă de probă &mdash; mai ai {{ $trialDaysLeft }} {{ $trialDaysLeft == 1 ? 'zi' : 'zile' }}
                    </span>
                @else
                    <p class="text-sm text-slate-500">Se reînnoiește pe <span class="font-medium text-slate-700">{{ now()->addMonth()->startOfMonth()->format('d.m.Y') }}</span></p>
                @endif
                <a href="#plans-section" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                    Schimbă planul
                </a>
            </div>
        </div>
    </div>

    {{-- Usage Progress --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-slate-900">Utilizare lună curentă</h3>
            <span class="text-sm font-medium
                @if($usagePercent >= 100) text-red-600
                @elseif($usagePercent >= 80) text-yellow-600
                @else text-slate-500
                @endif
            ">{{ $usagePercent }}%</span>
        </div>

        {{-- Progress bar --}}
        <div class="h-4 w-full rounded-full bg-slate-100 overflow-hidden">
            <div class="h-4 rounded-full transition-all duration-500 ease-out
                @if($usagePercent >= 100) bg-red-500
                @elseif($usagePercent >= 80) bg-yellow-500
                @else bg-emerald-500
                @endif
            " style="width: {{ min($usagePercent, 100) }}%"></div>
        </div>

        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ number_format($minutesUsed, 1) }}</span> / {{ number_format($minutesLimit) }} minute utilizate
            </p>
            @if($plan !== 'enterprise')
                <p class="text-sm text-slate-500">{{ number_format(max(0, $minutesLimit - $minutesUsed), 1) }} minute rămase</p>
            @endif
        </div>

        @if($overageMinutes > 0)
            <div class="mt-4 flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700">Ai depășit limita cu {{ number_format($overageMinutes, 1) }} minute</p>
                    <p class="text-sm text-red-600 mt-0.5">Cost suplimentar estimat: {{ number_format($overageCost, 2) }}€</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Usage Breakdown (2 columns) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Left: Per-bot minute usage --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Utilizare per bot</h3>
            @php
                $botUsage = \App\Models\Call::where('tenant_id', $tenant->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('bot_id, SUM(duration_seconds) as total_seconds')
                    ->groupBy('bot_id')
                    ->with('bot:id,name')
                    ->get();
            @endphp

            @if($botUsage->isEmpty())
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" /></svg>
                    </div>
                    <p class="text-sm text-slate-500">Nicio utilizare în această lună.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($botUsage as $usage)
                        @php
                            $botMinutes = round($usage->total_seconds / 60, 1);
                            $botPercent = $minutesUsed > 0 ? round(($botMinutes / $minutesUsed) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-slate-700">{{ $usage->bot?->name ?? 'Bot șters' }}</span>
                                <span class="text-sm text-slate-500">{{ number_format($botMinutes, 1) }} min ({{ $botPercent }}%)</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-red-700 transition-all duration-300" style="width: {{ $botPercent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Right: Cost breakdown --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900 mb-4">Sumar costuri</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-800">
                            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                        </div>
                        <span class="text-sm text-slate-600">Abonament lunar</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ number_format($monthlyCost, 0) }}€</span>
                </div>

                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-sm text-slate-600">Minute suplimentare</span>
                    </div>
                    <span class="text-sm font-semibold {{ $overageCost > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($overageCost, 2) }}€</span>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                        </div>
                        <span class="text-base font-semibold text-slate-900">Total estimat</span>
                    </div>
                    <span class="text-lg font-bold text-slate-900">{{ number_format($monthlyCost + $overageCost, 2) }}€</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Plans Comparison --}}
    <div id="plans-section" class="scroll-mt-8">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Planuri disponibile</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            @foreach($allPlans as $planKey => $planData)
                @php
                    $isCurrent = $plan === $planKey;
                    $isEnterprise = $planKey === 'enterprise';
                    $isProfessional = $planKey === 'professional';
                @endphp
                <div class="relative rounded-xl border bg-white p-6 shadow-sm transition-all
                    {{ $isCurrent ? 'border-red-300 ring-2 ring-red-100' : 'border-slate-200 hover:border-slate-300 hover:shadow-md' }}
                    {{ $isProfessional && !$isCurrent ? 'md:scale-[1.02]' : '' }}
                ">
                    {{-- Current plan badge --}}
                    @if($isCurrent)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded-full bg-red-800 px-3 py-1 text-xs font-bold text-white shadow-sm">
                                Planul curent
                            </span>
                        </div>
                    @endif

                    {{-- Most popular badge --}}
                    @if($isProfessional && !$isCurrent)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded-full bg-red-800 px-3 py-1 text-xs font-bold text-white shadow-sm">
                                Cel mai popular
                            </span>
                        </div>
                    @endif

                    <div class="pt-2">
                        <h4 class="text-lg font-bold text-slate-900">{{ $planData['name'] }}</h4>

                        @if($isEnterprise)
                            <p class="mt-2 text-2xl font-extrabold text-slate-900">Personalizat</p>
                            <p class="text-sm text-slate-500 mt-1">Prețuri adaptate nevoilor tale</p>
                        @else
                            <p class="mt-2">
                                <span class="text-3xl font-extrabold text-slate-900">{{ $planData['price_monthly'] }}</span>
                                <span class="text-base font-medium text-slate-500">€/lună</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-1">sau {{ $planData['price_yearly'] }}€/lună facturat anual</p>
                        @endif

                        <div class="mt-5 space-y-2.5 text-sm text-slate-600 border-t border-slate-100 pt-5">
                            @if(!$isEnterprise)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span><strong>{{ number_format($planData['minutes']) }}</strong> minute incluse</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" /></svg>
                                    <span><strong>{{ $planData['bots'] }}</strong> {{ $planData['bots'] == 1 ? 'bot' : 'boți' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>Supracost: <strong>{{ $planData['overage_per_minute'] }}€</strong>/min</span>
                                </div>
                            @endif
                            @foreach($planData['features'] as $feature)
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            @if($isCurrent)
                                <button disabled class="w-full rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed">
                                    Planul curent
                                </button>
                            @elseif($isEnterprise)
                                <a href="/contact" class="block w-full rounded-lg border-2 border-slate-900 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 hover:bg-slate-900 hover:text-white transition-colors">
                                    Contactează-ne
                                </a>
                            @else
                                @php
                                    $planOrder = ['starter' => 0, 'professional' => 1, 'enterprise' => 2];
                                    $currentOrder = $planOrder[$plan] ?? 0;
                                    $targetOrder = $planOrder[$planKey] ?? 0;
                                    $isUpgrade = $targetOrder > $currentOrder;
                                @endphp
                                @if($isUpgrade)
                                    <button class="w-full rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                                        Upgrade la {{ $planData['name'] }}
                                    </button>
                                @else
                                    <button class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                        Downgrade la {{ $planData['name'] }}
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>

    {{-- Usage History Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Istoric utilizare</h3>
        </div>

        @if($usageRecords->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                </div>
                <h4 class="mt-3 text-sm font-medium text-slate-900">Nicio înregistrare de utilizare</h4>
                <p class="mt-1 text-sm text-slate-500">Istoricul va apărea pe măsură ce utilizezi platforma.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3 font-medium text-slate-500">Tip</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Cantitate</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Cost unitar</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Total</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Perioada</th>
                            <th class="px-5 py-3 font-medium text-slate-500">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($usageRecords as $record)
                            @php
                                $typeLabels = [
                                    'minutes' => 'Minute',
                                    'overage' => 'Minute suplimentare',
                                    'subscription' => 'Abonament',
                                    'sms' => 'SMS',
                                ];
                                $typeBadges = [
                                    'minutes' => 'bg-red-50 text-red-800',
                                    'overage' => 'bg-red-50 text-red-700',
                                    'subscription' => 'bg-emerald-50 text-emerald-700',
                                    'sms' => 'bg-red-50 text-red-800',
                                ];
                                $unitCost = $record->unit_cost_cents / 100;
                                $total = ($record->quantity * $record->unit_cost_cents) / 100;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="whitespace-nowrap px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeBadges[$record->type] ?? 'bg-slate-50 text-slate-600' }}">
                                        {{ $typeLabels[$record->type] ?? ucfirst($record->type) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-700 font-medium">
                                    {{ number_format($record->quantity, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    {{ number_format($unitCost, 2) }}€
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 font-semibold text-slate-900">
                                    {{ number_format($total, 2) }}€
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500">
                                    @if($record->period_start && $record->period_end)
                                        {{ $record->period_start->format('d.m') }} &ndash; {{ $record->period_end->format('d.m.Y') }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500" title="{{ $record->recorded_at?->format('d.m.Y H:i:s') }}">
                                    {{ $record->recorded_at?->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Payment Method --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900 mb-4">Metodă de plată</h3>

        @if($tenant->pm_last_four)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-18 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3">
                        @if(strtolower($tenant->pm_type ?? '') === 'visa')
                            <span class="text-sm font-bold text-red-800 tracking-wider">VISA</span>
                        @elseif(strtolower($tenant->pm_type ?? '') === 'mastercard')
                            <span class="text-sm font-bold text-orange-600 tracking-wider">MC</span>
                        @else
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900">
                            {{ ucfirst($tenant->pm_type ?? 'Card') }} &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; {{ $tenant->pm_last_four }}
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">Cardul tău principal</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                    Actualizează metoda de plată
                </button>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-lg bg-slate-50 border border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-200 text-slate-500">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Nicio metodă de plată adăugată</p>
                        <p class="text-xs text-slate-500 mt-0.5">Adaugă un card pentru a activa abonamentul.</p>
                    </div>
                </div>
                <button class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Adaugă card
                </button>
            </div>
        @endif

        <p class="mt-4 flex items-center gap-2 text-xs text-slate-400">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
            Plățile sunt procesate securizat prin Stripe.
        </p>
    </div>

    {{-- Alerts / Notifications Info --}}
    <div class="rounded-xl border border-red-200 bg-red-50 p-5">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <div>
                <p class="text-sm font-semibold text-red-900">Notificări de utilizare</p>
                <p class="text-sm text-red-800 mt-1">Vei primi notificări automate prin email la 80% și 100% din limita de minute. Poți configura notificările suplimentare din <a href="/dashboard/setari" class="font-medium underline hover:no-underline">Setări</a>.</p>
            </div>
        </div>
    </div>

</div>
@endsection
