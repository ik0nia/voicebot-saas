@extends('layouts.dashboard')

@section('title', 'Super Admin Dashboard')

@section('breadcrumb')
<span class="text-slate-900 font-medium">Super Admin</span>
@endsection

@section('content')

{{-- Admin Badge --}}
<div class="mb-6 flex items-center gap-3">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
        Super Admin
    </span>
    <span class="text-sm text-slate-500">Vizualizare la nivel de platformă</span>
</div>

{{-- Platform Metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Tenanți</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalTenants }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Utilizatori</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalUsers }}</p>
        @if($newUsersToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $newUsersToday }} azi</p>
        @endif
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Boți activi</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $activeBots }}</p>
        <p class="text-xs text-slate-400 mt-1">din {{ $totalBots }} total</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Apeluri total</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalCalls }}</p>
        @if($callsToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $callsToday }} azi</p>
        @endif
    </div>
</div>

{{-- Second row metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Minute totale</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalMinutes, 0) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ $minutesToday }} min azi</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Venit total</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($totalRevenue, 2) }}€</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Numere telefon</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalNumbers }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-sm font-medium text-slate-500">Apeluri azi</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $callsToday }}</p>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-900 mb-4">Apeluri — Ultimele 7 zile</h3>
        <canvas id="callsChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-900 mb-4">Utilizatori noi — Ultimele 7 zile</h3>
        <canvas id="usersChart" height="200"></canvas>
    </div>
</div>

{{-- Tenants Table --}}
<div class="bg-white rounded-xl border border-slate-200 mb-8">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900">Toți tenanții</h3>
        <span class="text-sm text-slate-500">{{ $totalTenants }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Utilizatori</th>
                    <th class="px-6 py-3">Boți</th>
                    <th class="px-6 py-3">Apeluri</th>
                    <th class="px-6 py-3">Creat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenants as $tenant)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $tenant->name }}</p>
                            <p class="text-xs text-slate-400">{{ $tenant->slug }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $planColors = ['starter' => 'bg-slate-100 text-slate-700', 'professional' => 'bg-primary-50 text-primary-700', 'enterprise' => 'bg-amber-50 text-amber-700'];
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $planColors[$tenant->plan] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ ucfirst($tenant->plan) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $tenant->users_count }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $tenant->bots_count }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $tenant->calls_count }}</td>
                    <td class="px-6 py-3 text-sm text-slate-400">{{ $tenant->created_at?->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">Niciun tenant încă.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Calls Across All Tenants --}}
<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-900">Ultimele apeluri (toate platformele)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Bot</th>
                    <th class="px-6 py-3">Apelant</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Durată</th>
                    <th class="px-6 py-3">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentCalls as $call)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $call->tenant?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm font-medium text-slate-900">{{ $call->bot?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $call->caller_number ?? '—' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $statusColors = [
                                'completed' => 'bg-emerald-50 text-emerald-700',
                                'in_progress' => 'bg-red-50 text-red-800',
                                'failed' => 'bg-red-50 text-red-700',
                                'initiated' => 'bg-slate-100 text-slate-600',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$call->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $call->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $call->duration_seconds > 0 ? floor($call->duration_seconds / 60) . 'm ' . ($call->duration_seconds % 60) . 's' : '—' }}
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-400">{{ $call->created_at?->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">Niciun apel pe platformă.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const chartLabels = @json($chartData->pluck('date'));

    new Chart(document.getElementById('callsChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Apeluri',
                data: @json($chartData->pluck('calls')),
                backgroundColor: '#991b1b',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    new Chart(document.getElementById('usersChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Utilizatori noi',
                data: @json($chartData->pluck('users')),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>
@endpush
