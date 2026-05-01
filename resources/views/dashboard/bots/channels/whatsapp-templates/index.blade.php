@extends('layouts.dashboard')

@section('title', 'Template-uri WhatsApp - ' . $channel->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Agenți AI</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">Canale</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Template-uri</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Template-uri WhatsApp</h1>
            <p class="text-sm text-slate-500 mt-1">
                Mesaje pre-aprobate de Meta — singurul tip de mesaj pe care îl poți trimite în afara ferestrei de 24h.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('dashboard.bots.channels.whatsapp-templates.sync', [$bot, $channel]) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sincronizează cu Meta
                </button>
            </form>
            <a href="{{ route('dashboard.bots.channels.whatsapp-templates.create', [$bot, $channel]) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Template nou
            </a>
        </div>
    </div>

    @if($templates->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-slate-200 px-8 py-16 text-center">
            <h2 class="text-base font-semibold text-slate-700">Niciun template încă</h2>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Creează un template pentru notificări automate (confirmări programare, reminders, statusul comenzii). Meta îl aprobă în 1-24h, apoi îl poți trimite oricând.
            </p>
            <a href="{{ route('dashboard.bots.channels.whatsapp-templates.create', [$bot, $channel]) }}"
               class="mt-6 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                Creează primul template
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Nume</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Categorie</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Limbă</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">Trimis la Meta</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($templates as $template)
                        @php
                            $color = $template->statusBadgeColor();
                            $colorClasses = [
                                'green' => 'bg-green-50 text-green-700',
                                'red' => 'bg-red-50 text-red-700',
                                'amber' => 'bg-amber-50 text-amber-700',
                                'orange' => 'bg-orange-50 text-orange-700',
                                'slate' => 'bg-slate-100 text-slate-600',
                            ][$color] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-slate-900">{{ $template->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $template->category }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $template->language }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorClasses }}">
                                    {{ $template->statusLabel() }}
                                </span>
                                @if($template->status === 'REJECTED' && $template->rejection_reason)
                                    <p class="text-xs text-red-600 mt-1">{{ $template->rejection_reason }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500">
                                {{ $template->submitted_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('dashboard.bots.channels.whatsapp-templates.edit', [$bot, $channel, $template]) }}"
                                       class="text-slate-500 hover:text-slate-700">{{ $template->isDraft() ? 'Editează' : 'Vezi' }}</a>
                                    @if($template->isDraft())
                                        <form method="POST" action="{{ route('dashboard.bots.channels.whatsapp-templates.submit', [$bot, $channel, $template]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-emerald-700 hover:text-emerald-900 font-medium">Trimite la Meta</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('dashboard.bots.channels.whatsapp-templates.destroy', [$bot, $channel, $template]) }}" class="inline"
                                          onsubmit="return confirm('Șterge template-ul {{ $template->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Șterge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
