@extends('layouts.admin')
@section('title', 'Apeluri - Admin')
@section('content')
<h1 class="text-2xl font-bold text-ink mb-6">Toate apelurile</h1>
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-line text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">ID</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Tenant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Bot</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Apelant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Durata</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Cost</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Data</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($calls as $call)
            <tr class="hover:bg-cream">
                <td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="font-medium text-coralh hover:underline">#{{ $call->id }}</a></td>
                <td class="px-5 py-3 text-muted">{{ $call->tenant?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-inkSoft">{{ $call->bot?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-muted">{{ $call->caller_number ?? '-' }}</td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : ($call->status === 'failed' ? 'bg-coralsoft text-coralh' : 'bg-cream text-muted') }}">{{ $call->status }}</span></td>
                <td class="px-5 py-3 text-muted">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ number_format($call->cost_cents / 100, 2) }}€</td>
                <td class="px-5 py-3 text-muted text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-5 py-8 text-center text-muted">Niciun apel.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $calls->links() }}</div>
@endsection
