@extends('layouts.admin')
@section('title', 'Tenanti - Admin')
@section('content')
<h1 class="text-2xl font-bold text-slate-900 mb-6">Toti tenantii</h1>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Nume</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Plan</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Utilizatori</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Boti</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Conversatii</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cost apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cost AI chat</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cost total</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Creat</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($tenants as $tenant)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-red-800 hover:underline">{{ $tenant->name }}</a></td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst($tenant->plan ?? 'free') }}</span></td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $tenant->users_count }}</td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $tenant->bots_count }}</td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $tenant->calls_count }}</td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $tenant->conversations_count }}</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($tenant->calls_sum_cost_cents ?? 0) / 100, 2) }} $</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($tenant->chat_cost_cents ?? 0) / 100, 4) }} $</td>
                <td class="px-5 py-3 font-semibold text-slate-900 font-mono text-xs">{{ number_format(($tenant->total_cost_cents ?? 0) / 100, 2) }} $</td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $tenant->created_at->format('d.m.Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="px-5 py-8 text-center text-slate-400">Niciun tenant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
