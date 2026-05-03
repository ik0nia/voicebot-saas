@extends('layouts.admin')
@section('title', 'Tenanti - Admin')
@section('content')
<h1 class="text-2xl font-bold text-ink mb-6">Toti tenantii</h1>
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-line text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Nume</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Plan</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Utilizatori</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Boti</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Conversatii</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Cost apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Cost AI chat</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Cost total</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Creat</th>
        </tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($tenants as $tenant)
            <tr class="hover:bg-cream">
                <td class="px-5 py-3"><a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-coralh hover:underline">{{ $tenant->name }}</a></td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-cream text-inkSoft">{{ ucfirst($tenant->plan ?? 'free') }}</span></td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ $tenant->users_count }}</td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ $tenant->bots_count }}</td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ $tenant->calls_count }}</td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ $tenant->conversations_count }}</td>
                <td class="px-5 py-3 text-muted font-mono text-xs">{{ number_format(($tenant->calls_sum_cost_cents ?? 0) / 100, 2) }} $</td>
                <td class="px-5 py-3 text-muted font-mono text-xs">{{ number_format(($tenant->chat_cost_cents ?? 0) / 100, 4) }} $</td>
                <td class="px-5 py-3 font-semibold text-ink font-mono text-xs">{{ number_format(($tenant->total_cost_cents ?? 0) / 100, 2) }} $</td>
                <td class="px-5 py-3 text-muted text-xs">{{ $tenant->created_at->format('d.m.Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="px-5 py-8 text-center text-muted">Niciun tenant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
