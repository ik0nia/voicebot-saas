@extends('layouts.admin')
@section('title', 'Boti - Admin')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-ink">Toti botii</h1>
    <form class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cauta bot..." class="rounded-lg border border-line px-3 py-2 text-sm focus:border-coral/40 focus:ring-1 focus:ring-red-300">
        <button class="px-4 py-2 text-sm font-medium rounded-lg bg-coral text-white hover:bg-coralh">Cauta</button>
    </form>
</div>
<div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-line text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Nume</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Tenant</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Limba</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-muted">Creat</th>
        </tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($bots as $bot)
            <tr class="hover:bg-cream transition-colors">
                <td class="px-5 py-3"><a href="{{ route('admin.bots.show', $bot) }}" class="font-medium text-coralh hover:underline">{{ $bot->name }}</a></td>
                <td class="px-5 py-3 text-muted">{{ $bot->tenant?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-muted">{{ strtoupper($bot->language) }}</td>
                <td class="px-5 py-3"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-cream text-muted' }}"><span class="w-1.5 h-1.5 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span></td>
                <td class="px-5 py-3 text-inkSoft font-medium">{{ $bot->calls_count }}</td>
                <td class="px-5 py-3 text-muted text-xs">{{ $bot->created_at->format('d.m.Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-muted">Niciun bot.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bots->links() }}</div>
@endsection
