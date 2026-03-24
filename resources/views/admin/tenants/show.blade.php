@extends('layouts.admin')
@section('title', $tenant->name . ' - Admin')
@section('content')
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('admin.tenants.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $tenant->name }}</h1>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst($tenant->plan ?? 'free') }}</span>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Utilizatori</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $tenant->users_count }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Boti</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $tenant->bots_count }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs text-slate-500">Apeluri</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $tenant->calls_count }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs text-amber-700">Cost apeluri</p>
        <p class="text-xl font-bold text-amber-900 mt-1">{{ number_format(($tenant->calls_sum_cost_cents ?? 0) / 100, 2) }} $</p>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-xs text-blue-700">Cost AI chat</p>
        <p class="text-xl font-bold text-blue-900 mt-1">{{ number_format(($tenant->chat_cost_cents ?? 0) / 100, 4) }} $</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs text-emerald-700">Cost total</p>
        <p class="text-xl font-bold text-emerald-900 mt-1">{{ number_format(($tenant->total_cost_cents ?? 0) / 100, 2) }} $</p>
    </div>
</div>

{{-- Users --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Utilizatori</h2></div>
    <div class="p-5">
        @foreach($users as $user)
        <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
            <div><p class="text-sm font-medium text-slate-900">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p></div>
            <span class="text-xs text-slate-400">{{ $user->last_login_at?->diffForHumans() ?? 'Niciodata' }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Bots with costs --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Boti</h2></div>
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Bot</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Apeluri</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Conversatii</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost apeluri</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($bots as $bot)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.bots.show', $bot) }}" class="font-medium text-red-800 hover:underline">{{ $bot->name }}</a></td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span></td>
                <td class="px-5 py-3 text-slate-600">{{ $bot->calls_count }}</td>
                <td class="px-5 py-3 text-slate-600">{{ $bot->conversations_count }}</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($bot->calls_sum_cost_cents ?? 0) / 100, 2) }} $</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Recent Conversations with costs --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Conversatii recente</h2></div>
    @if($conversations->count())
    <table class="w-full text-sm">
        <thead><tr class="border-b border-slate-100 text-left">
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">ID</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Bot</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Mesaje</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Cost AI</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
            <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Data</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($conversations as $conv)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.conversations.show', $conv) }}" class="font-medium text-red-800 hover:underline">#{{ $conv->id }}</a></td>
                <td class="px-5 py-3 text-slate-700">{{ $conv->bot?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-600">{{ $conv->messages_count }}</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ ($conv->real_cost_cents ?? 0) > 0 ? number_format($conv->real_cost_cents / 100, 4) . ' $' : '-' }}</td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $conv->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $conv->status === 'active' ? 'Activa' : 'Incheiata' }}</span></td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $conv->created_at->format('d.m.Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="px-5 py-8 text-center text-sm text-slate-400">Nicio conversatie.</div>
    @endif
</div>

{{-- Recent Calls --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-base font-semibold text-slate-900">Apeluri recente</h2></div>
    @if($recentCalls->count())
    <table class="w-full text-sm">
        <tbody class="divide-y divide-slate-100">
            @foreach($recentCalls as $call)
            <tr class="hover:bg-slate-50">
                <td class="px-5 py-3"><a href="{{ route('admin.calls.show', $call) }}" class="text-red-800 hover:underline">#{{ $call->id }}</a></td>
                <td class="px-5 py-3 text-slate-700">{{ $call->bot?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-slate-600">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '-' }}</td>
                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ number_format(($call->cost_cents ?? 0) / 100, 2) }} $</td>
                <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $call->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $call->status }}</span></td>
                <td class="px-5 py-3 text-slate-500 text-xs">{{ $call->created_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="px-5 py-8 text-center text-sm text-slate-400">Niciun apel.</div>
    @endif
</div>
@endsection
