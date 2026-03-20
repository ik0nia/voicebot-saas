@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Onboarding Banner --}}
    @if(!$onboardingComplete)
    <div id="onboarding-banner" class="relative overflow-hidden rounded-xl border border-primary-100 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-slate-900">Bine ai venit! Hai să configurăm Sambla-ul tău</h2>
                <p class="mt-1 text-sm text-slate-500">Urmează pașii de mai jos pentru a începe să primești apeluri.</p>

                <ul class="mt-4 space-y-3">
                    {{-- 1. Cont creat --}}
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Cont creat</span>
                    </li>

                    {{-- 2. Creează primul bot --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['first_bot'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Creează primul bot</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/boti/create" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Creează primul bot</a>
                        @endif
                    </li>

                    {{-- 3. Adaugă un număr de telefon --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['phone_number'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Adaugă un număr de telefon</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/numere" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Adaugă un număr de telefon</a>
                        @endif
                    </li>

                    {{-- 4. Testează botul --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['test_call'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Testează botul</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <span class="text-sm text-slate-500">Testează botul</span>
                        @endif
                    </li>

                    {{-- 5. Invită un coleg --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['invite_team'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Invită un coleg</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/echipa" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Invită un coleg</a>
                        @endif
                    </li>
                </ul>

                {{-- Progress bar --}}
                @php
                    $completedSteps = count(array_filter($onboarding));
                    $totalSteps = count($onboarding);
                    $progressPercent = round(($completedSteps / $totalSteps) * 100);
                @endphp
                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Progres</span>
                        <span>{{ $completedSteps }}/{{ $totalSteps }} completat</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-primary-600 transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Dismiss button --}}
            <button onclick="document.getElementById('onboarding-banner').style.display='none'" class="ml-4 flex-shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Închide">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Apeluri azi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Apeluri azi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($callsToday) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                </div>
            </div>
        </div>

        {{-- Minute folosite --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Minute folosite</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format(round($minutesToday)) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        {{-- Boți activi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Boți activi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($activeBots) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                </div>
            </div>
        </div>

        {{-- Rata de succes --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Rata de succes</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $successRate }}%</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Calls Bar Chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Apeluri &mdash; Ultimele 7 zile</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="callsChart"></canvas>
            </div>
        </div>

        {{-- Minutes Line Chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Minute &mdash; Ultimele 7 zile</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="minutesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Calls Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Ultimele apeluri</h3>
            <a href="/dashboard/apeluri" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">
                Vezi toate apelurile &rarr;
            </a>
        </div>

        @if($recentCalls->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-medium text-slate-900">Niciun apel încă</h4>
            <p class="mt-1 text-sm text-slate-500">Creează primul bot pentru a începe.</p>
            <a href="/dashboard/boti/create" class="mt-4 inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors">
                Creează un bot
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-5 py-3 font-medium text-slate-500">Bot</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Apelant</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Status</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Durată</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentCalls as $call)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">
                            {{ $call->bot?->name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                            {{ $call->caller_number ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3">
                            @switch($call->status)
                                @case('completed')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Finalizat</span>
                                    @break
                                @case('in_progress')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800">În desfășurare</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Eșuat</span>
                                    @break
                                @case('initiated')
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Inițiat</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $call->status }}</span>
                            @endswitch
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                            @if($call->duration_seconds)
                                {{ gmdate('i:s', $call->duration_seconds) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-500">
                            {{ $call->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Creează un bot --}}
        <a href="/dashboard/boti/create" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition-colors group-hover:bg-primary-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Creează un bot</h4>
            <p class="mt-1 text-xs text-slate-500">Configurează un nou Sambla cu personalitate și instrucțiuni personalizate.</p>
        </a>

        {{-- Adaugă un număr --}}
        <a href="/dashboard/numere" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 transition-colors group-hover:bg-sky-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Adaugă un număr</h4>
            <p class="mt-1 text-xs text-slate-500">Conectează un număr de telefon pentru a primi și iniția apeluri.</p>
        </a>

        {{-- Invită un coleg --}}
        <a href="/dashboard/echipa" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600 transition-colors group-hover:bg-violet-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Invită un coleg</h4>
            <p class="mt-1 text-xs text-slate-500">Adaugă membri în echipă pentru a gestiona boții și apelurile împreună.</p>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartLabels = @json($chartData->pluck('date'));
    const chartCalls = @json($chartData->pluck('calls'));
    const chartMinutes = @json($chartData->pluck('minutes'));

    const sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 12 },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    stepSize: 1,
                    precision: 0,
                },
                border: { display: false },
            },
        },
    };

    // Calls Bar Chart
    new Chart(document.getElementById('callsChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Apeluri',
                data: chartCalls,
                backgroundColor: '#4f46e5',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 40,
            }],
        },
        options: {
            ...sharedOptions,
            plugins: {
                ...sharedOptions.plugins,
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: function(ctx) { return ctx.parsed.y + ' apeluri'; }
                    }
                }
            }
        },
    });

    // Minutes Line Chart
    new Chart(document.getElementById('minutesChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Minute',
                data: chartMinutes,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            ...sharedOptions,
            scales: {
                ...sharedOptions.scales,
                y: {
                    ...sharedOptions.scales.y,
                    ticks: {
                        ...sharedOptions.scales.y.ticks,
                        stepSize: undefined,
                        precision: 1,
                        callback: function(value) { return value + ' min'; }
                    },
                },
            },
            plugins: {
                ...sharedOptions.plugins,
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: function(ctx) { return ctx.parsed.y + ' minute'; }
                    }
                }
            }
        },
    });
});
</script>
@endpush
