@extends('layouts.dashboard')

@section('title', 'Super Admin Dashboard')

@section('breadcrumb')
<span class="text-ink font-medium">Super Admin</span>
@endsection

@section('content')

{{-- Admin Badge --}}
<div class="mb-6 flex items-center gap-3">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-coralsoft text-coralh border border-coral/30">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
        Super Admin
    </span>
    <span class="text-sm text-muted">Vizualizare la nivel de platformă</span>
</div>

{{-- Platform Metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Tenanți</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalTenants }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Utilizatori</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalUsers }}</p>
        @if($newUsersToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $newUsersToday }} azi</p>
        @endif
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Agenți AI activi</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $activeBots }}</p>
        <p class="text-xs text-muted mt-1">din {{ $totalBots }} total</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Apeluri total</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalCalls }}</p>
        @if($callsToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $callsToday }} azi</p>
        @endif
    </div>
</div>

{{-- Second row metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Minute totale</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ number_format($totalMinutes, 0) }}</p>
        <p class="text-xs text-muted mt-1">{{ $minutesToday }} min azi</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Venit total</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ number_format($totalRevenue, 2) }}€</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Numere telefon</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalNumbers }}</p>
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Conversatii agent AI</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalConversations }}</p>
        @if($conversationsToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $conversationsToday }} azi</p>
        @endif
    </div>
    <div class="bg-white rounded-xl border border-line p-5">
        <p class="text-sm font-medium text-muted">Mesaje total</p>
        <p class="text-2xl font-bold text-ink mt-1">{{ $totalMessages }}</p>
        @if($messagesToday > 0)
            <p class="text-xs text-emerald-600 mt-1">+{{ $messagesToday }} azi</p>
        @endif
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-line p-6">
        <h3 class="font-semibold text-ink mb-4">Apeluri — Ultimele 7 zile</h3>
        <canvas id="callsChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-line p-6">
        <h3 class="font-semibold text-ink mb-4">Utilizatori noi — Ultimele 7 zile</h3>
        <canvas id="usersChart" height="200"></canvas>
    </div>
</div>

{{-- Tenants Table --}}
<div class="bg-white rounded-xl border border-line mb-8">
    <div class="px-6 py-4 border-b border-line flex items-center justify-between">
        <h3 class="font-semibold text-ink">Toți tenanții</h3>
        <span class="text-sm text-muted">{{ $totalTenants }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-muted uppercase tracking-wider">
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Utilizatori</th>
                    <th class="px-6 py-3">Agenți AI</th>
                    <th class="px-6 py-3">Apeluri</th>
                    <th class="px-6 py-3">Creat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenants as $tenant)
                <tr class="hover:bg-cream">
                    <td class="px-6 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $tenant->name }}</p>
                            <p class="text-xs text-muted">{{ $tenant->slug }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $planColors = ['starter' => 'bg-cream text-inkSoft', 'professional' => 'bg-primary-50 text-primary-700', 'enterprise' => 'bg-amber-50 text-amber-700'];
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $planColors[$tenant->plan] ?? 'bg-cream text-inkSoft' }}">
                            {{ ucfirst($tenant->plan) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $tenant->users_count }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $tenant->bots_count }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $tenant->calls_count }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $tenant->created_at?->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Niciun tenant încă.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Bot Costs Platform-wide --}}
@if($botCosts->count() > 0)
<div class="bg-white rounded-xl border border-line mb-8">
    <div class="px-6 py-4 border-b border-line flex items-center justify-between">
        <h3 class="font-semibold text-ink">Costuri per agent AI (toată platforma)</h3>
        <span class="text-sm font-medium text-muted">Total: {{ number_format($botCosts->sum('calls_sum_cost_cents') / 100, 2) }}€</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-muted uppercase tracking-wider">
                    <th class="px-6 py-3">Agent AI</th>
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Apeluri</th>
                    <th class="px-6 py-3">Minute</th>
                    <th class="px-6 py-3">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($botCosts as $bot)
                <tr class="hover:bg-cream">
                    <td class="px-6 py-3 text-sm font-medium text-ink">{{ $bot->name }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $bot->tenant?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $bot->calls_count }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ number_format(($bot->calls_sum_duration_seconds ?? 0) / 60, 1) }}</td>
                    <td class="px-6 py-3 text-sm font-semibold text-ink">{{ number_format(($bot->calls_sum_cost_cents ?? 0) / 100, 2) }}€</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Recent Calls Across All Tenants --}}
<div class="bg-white rounded-xl border border-line">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Ultimele apeluri (toate platformele)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-muted uppercase tracking-wider">
                    <th class="px-6 py-3">Tenant</th>
                    <th class="px-6 py-3">Agent AI</th>
                    <th class="px-6 py-3">Apelant</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Durată</th>
                    <th class="px-6 py-3">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentCalls as $call)
                <tr class="hover:bg-cream">
                    <td class="px-6 py-3 text-sm text-muted">{{ $call->tenant?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm font-medium text-ink">{{ $call->bot?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $call->caller_number ?? '—' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $statusColors = [
                                'completed' => 'bg-emerald-50 text-emerald-700',
                                'in_progress' => 'bg-coralsoft text-coralh',
                                'failed' => 'bg-coralsoft text-coralh',
                                'initiated' => 'bg-cream text-muted',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$call->status] ?? 'bg-cream text-muted' }}">
                            {{ $call->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-muted">
                        {{ $call->duration_seconds > 0 ? floor($call->duration_seconds / 60) . 'm ' . ($call->duration_seconds % 60) . 's' : '—' }}
                    </td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $call->created_at?->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Niciun apel pe platformă.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Conversations --}}
<div class="bg-white rounded-xl border border-line shadow-sm mt-6">
    <div class="px-6 py-4 border-b border-line">
        <h3 class="font-semibold text-ink">Ultimele conversatii agent AI (toate platformele)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left">
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Agent AI</th>
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Tenant</th>
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Contact</th>
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Mesaje</th>
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Status</th>
                    <th class="px-6 py-3 text-xs font-medium uppercase tracking-wider text-muted">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentConversations as $conv)
                <tr class="hover:bg-cream">
                    <td class="px-6 py-3 font-medium text-inkSoft">{{ $conv->bot?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-muted">{{ $conv->tenant?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-muted">{{ $conv->contact_name ?: ($conv->contact_identifier ?: '—') }}</td>
                    <td class="px-6 py-3 font-medium text-inkSoft">{{ $conv->messages_count }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $conv->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-cream text-muted' }}">
                            {{ $conv->status === 'active' ? 'Activa' : 'Incheiata' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted">{{ $conv->created_at?->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Nicio conversatie pe platforma.</td>
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
