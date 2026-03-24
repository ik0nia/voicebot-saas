@extends('layouts.admin')
@section('title', 'Conversatii - Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Toate conversatiile</h1>
    <div class="text-sm text-slate-500">
        Cost total pagina: <span class="font-semibold text-slate-900">{{ number_format($conversations->sum('real_cost_cents') / 100, 4) }} $</span>
    </div>
</div>

{{-- Filters --}}
<div class="flex gap-3 mb-4">
    <form method="GET" class="flex gap-3 items-center">
        <select name="tenant" class="rounded-lg border border-slate-300 text-sm py-2 px-3 text-slate-700" onchange="this.form.submit()">
            <option value="">Toti tenantii</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ request('tenant') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
            @endforeach
        </select>
        @if(request('tenant') || request('bot'))
            <a href="{{ route('admin.conversations.index') }}" class="text-xs text-red-800 hover:underline">Reset filtre</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">ID</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Tenant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Bot</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Contact</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Mesaje</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cost AI</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Data</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($conversations as $conv)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.conversations.show', $conv) }}" class="font-medium text-red-800 hover:underline">#{{ $conv->id }}</a></td>
                <td class="px-5 py-3 text-slate-500">{{ $conv->tenant?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-700">{{ $conv->bot?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-600">{{ $conv->contact_name ?: ($conv->contact_identifier ?: '-') }}</td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $conv->messages_count }}</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ ($conv->real_cost_cents ?? 0) > 0 ? number_format($conv->real_cost_cents / 100, 4) . ' $' : '-' }}</td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $conv->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $conv->status === 'active' ? 'Activa' : 'Incheiata' }}</span></td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $conv->created_at->format('d.m.Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Nicio conversatie.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $conversations->links() }}</div>
@endsection
