@extends('layouts.admin')
@section('title', 'Apeluri - Admin')
@section('content')
<h1 class="text-2xl font-bold text-slate-900 mb-6">Toate apelurile</h1>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">ID</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Tenant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Bot</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Apelant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Durata</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Cost</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Data</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($calls as $call)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="font-medium text-red-800 hover:underline">#{{ $call->id }}</a></td>
                <td class="px-5 py-3 text-slate-500">{{ $call->tenant?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-700">{{ $call->bot?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-600">{{ $call->caller_number ?? '-' }}</td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : ($call->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600') }}">{{ $call->status }}</span></td>
                <td class="px-5 py-3 text-slate-600">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ number_format($call->cost_cents / 100, 2) }}€</td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Niciun apel.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $calls->links() }}</div>
@endsection
