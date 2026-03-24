@extends('layouts.admin')
@section('title', 'Boti - Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Toti botii</h1>
    <form class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cauta bot..." class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-300 focus:ring-1 focus:ring-red-300">
        <button class="px-4 py-2 text-sm font-medium rounded-lg bg-red-800 text-white hover:bg-red-900">Cauta</button>
    </form>
</div>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Nume</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Tenant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Limba</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Creat</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($bots as $bot)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-5 py-3"><a href="{{ route('admin.bots.show', $bot) }}" class="font-medium text-red-800 hover:underline">{{ $bot->name }}</a></td>
                <td class="px-5 py-3 text-slate-500">{{ $bot->tenant?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-600">{{ strtoupper($bot->language) }}</td>
                <td class="px-5 py-3"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}"><span class="w-1.5 h-1.5 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span></td>
                <td class="px-5 py-3 text-slate-700 font-medium">{{ $bot->calls_count }}</td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $bot->created_at->format('d.m.Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Niciun bot.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bots->links() }}</div>
@endsection
