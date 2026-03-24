@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- No Sites Banner --}}
    @if(auth()->user()->tenant->sites()->count() === 0)
    <div class="mb-6 bg-gradient-to-r from-red-50 to-amber-50 border border-red-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Incepe prin adaugarea site-ului tau</h3>
        <p class="text-sm text-slate-600 mb-4">Pentru a folosi chatbot-ul, trebuie mai intai sa adaugi si verifici domeniul site-ului tau.</p>
        <a href="{{ route('dashboard.sites.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            Adauga site-ul tau
        </a>
    </div>
    @endif

    {{-- Onboarding Banner --}}
    @if(!$onboardingComplete && empty($_COOKIE['sambla_hide_onboarding']))
    <div id="onboarding-banner" class="relative overflow-hidden rounded-xl border border-primary-100 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-slate-900">Bine ai venit! Hai să configurăm Sambla-ul tău</h2>
                <p class="mt-1 text-sm text-slate-500">Urmează pașii de mai jos pentru a începe să primești apeluri.</p>

                <ul class="mt-4 space-y-3">
                    {{-- 1. Cont creat --}}
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Cont creat</span>
                    </li>

                    {{-- 2. Creează primul bot --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['first_bot'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Creează primul bot</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/boti/create" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Creează primul bot</a>
                        @endif
                    </li>

                    {{-- 3. Adaugă un număr de telefon --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['phone_number'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Adaugă un număr de telefon</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/numere" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Adaugă un număr de telefon</a>
                        @endif
                    </li>

                    {{-- 4. Testează botul --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['test_call'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Testează botul</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <span class="text-sm text-slate-500">Testează botul</span>
                        @endif
                    </li>

                    {{-- 5. Invită un coleg --}}
                    <li class="flex items-center gap-3">
                        @if($onboarding['invite_team'])
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-sm text-slate-700 line-through">Invită un coleg</span>
                        @else
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-300 bg-white"></span>
                        <a href="/dashboard/echipa" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">Invită un coleg</a>
                        @endif
                    </li>
                </ul>

                {{-- Progress bar --}}
                @php
                    $completedSteps = count(array_filter($onboarding));
                    $totalSteps = count($onboarding);
                    $progressPercent = round(($completedSteps / $totalSteps) * 100);
                @endphp
                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Progres</span>
                        <span>{{ $completedSteps }}/{{ $totalSteps }} completat</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-primary-600 transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>

                {{-- Nu mai arăta permanent --}}
                <button onclick="dismissOnboardingForever()" class="mt-3 text-xs text-slate-400 hover:text-slate-600 underline transition-colors">
                    Nu mai arăta acest mesaj
                </button>
            </div>

            {{-- Dismiss button (temporar, doar sesiune) --}}
            <button onclick="document.getElementById('onboarding-banner').style.display='none'" class="ml-4 flex-shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Ascunde">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
    <script>
    function dismissOnboardingForever() {
        document.getElementById('onboarding-banner').remove();
        document.cookie = 'sambla_hide_onboarding=1;path=/;max-age=' + (365*24*60*60) + ';SameSite=Lax';
    }
    </script>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {{-- 1. Boti activi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Boti activi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($activeBots) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                </div>
            </div>
        </div>

        {{-- 2. Conversatii azi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Conversatii azi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($conversationsToday) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                </div>
            </div>
        </div>

        {{-- 3. Mesaje azi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Mesaje azi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($messagesToday) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                </div>
            </div>
        </div>

        {{-- 4. Apeluri azi --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Apeluri azi</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($callsToday) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-800">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                </div>
            </div>
        </div>

        {{-- 5. Minute voce --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Minute voce</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format(round($minutesToday)) }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Bot Costs --}}
    @if($botCosts->count() > 0)
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Costuri per bot</h3>
            <span class="text-sm font-medium text-slate-500">Total: {{ number_format($totalCostCents / 100, 2, ',', '.') }} EUR</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-5 py-3 font-medium text-slate-500">Bot</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Apeluri</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Minute</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($botCosts as $bot)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ $bot->name }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $bot->calls_count }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ number_format(($bot->calls_sum_duration_seconds ?? 0) / 60, 1) }} min</td>
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">{{ number_format(($bot->calls_sum_cost_cents ?? 0) / 100, 2, ',', '.') }} EUR</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Calls Bar Chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Apeluri &mdash; Ultimele 7 zile</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="callsChart"></canvas>
            </div>
        </div>

        {{-- Minutes Line Chart --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Minute &mdash; Ultimele 7 zile</h3>
            <div class="mt-4" style="height: 260px;">
                <canvas id="minutesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Calls Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Ultimele apeluri</h3>
            <a href="/dashboard/apeluri" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline">
                Vezi toate apelurile &rarr;
            </a>
        </div>

        @if($recentCalls->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-medium text-slate-900">Niciun apel încă</h4>
            <p class="mt-1 text-sm text-slate-500">Creează primul bot pentru a începe.</p>
            <a href="/dashboard/boti/create" class="mt-4 inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors">
                Creează un bot
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-5 py-3 font-medium text-slate-500">Bot</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Apelant</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Status</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Durată</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentCalls as $call)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">
                            {{ $call->bot?->name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                            {{ $call->caller_number ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-3">
                            @switch($call->status)
                                @case('completed')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Finalizat</span>
                                    @break
                                @case('in_progress')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800">În desfășurare</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Eșuat</span>
                                    @break
                                @case('initiated')
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">Inițiat</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $call->status }}</span>
                            @endswitch
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                            @if($call->duration_seconds)
                                {{ gmdate('i:s', $call->duration_seconds) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-slate-500">
                            {{ $call->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Recent Conversations --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-900">Ultimele conversatii chatbot</h3>
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>Total: {{ number_format($totalConversations) }} conversatii / {{ number_format($totalMessages) }} mesaje</span>
            </div>
        </div>
        @if($recentConversations->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-400">Nicio conversatie chatbot inca.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Bot</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Contact</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Mesaje</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wider text-slate-500">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentConversations as $conv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-700">{{ $conv->bot?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $conv->contact_name ?: ($conv->contact_identifier ?: '—') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-700 font-medium">{{ $conv->messages_count }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $conv->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-50 text-slate-600' }}">
                                    {{ $conv->status === 'active' ? 'Activa' : 'Incheiata' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $conv->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Creează un bot --}}
        <a href="/dashboard/boti/create" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition-colors group-hover:bg-primary-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Creează un bot</h4>
            <p class="mt-1 text-xs text-slate-500">Configurează un nou Sambla cu personalitate și instrucțiuni personalizate.</p>
        </a>

        {{-- Adaugă un număr --}}
        <a href="/dashboard/numere" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 transition-colors group-hover:bg-sky-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Adaugă un număr</h4>
            <p class="mt-1 text-xs text-slate-500">Conectează un număr de telefon pentru a primi și iniția apeluri.</p>
        </a>

        {{-- Invită un coleg --}}
        <a href="/dashboard/echipa" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary-200 hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-800 transition-colors group-hover:bg-red-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            </div>
            <h4 class="mt-3 text-sm font-semibold text-slate-900">Invită un coleg</h4>
            <p class="mt-1 text-xs text-slate-500">Adaugă membri în echipă pentru a gestiona boții și apelurile împreună.</p>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartLabels = @json($chartData->pluck('date'));
    const chartCalls = @json($chartData->pluck('calls'));
    const chartMinutes = @json($chartData->pluck('minutes'));

    const sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 12 },
                bodyFont: { size: 12 },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11 },
                    stepSize: 1,
                    precision: 0,
                },
                border: { display: false },
            },
        },
    };

    // Calls Bar Chart
    new Chart(document.getElementById('callsChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Apeluri',
                data: chartCalls,
                backgroundColor: '#991b1b',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 40,
            }],
        },
        options: {
            ...sharedOptions,
            plugins: {
                ...sharedOptions.plugins,
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: function(ctx) { return ctx.parsed.y + ' apeluri'; }
                    }
                }
            }
        },
    });

    // Minutes Line Chart
    new Chart(document.getElementById('minutesChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Minute',
                data: chartMinutes,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            ...sharedOptions,
            scales: {
                ...sharedOptions.scales,
                y: {
                    ...sharedOptions.scales.y,
                    ticks: {
                        ...sharedOptions.scales.y.ticks,
                        stepSize: undefined,
                        precision: 1,
                        callback: function(value) { return value + ' min'; }
                    },
                },
            },
            plugins: {
                ...sharedOptions.plugins,
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: function(ctx) { return ctx.parsed.y + ' minute'; }
                    }
                }
            }
        },
    });
});
</script>
@endpush
