@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- No Sites Banner --}}
    @if(auth()->user()->tenant?->sites()->count() === 0)
    <div class="bg-gradient-to-r from-red-50 to-amber-50 border border-red-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Incepe prin adaugarea site-ului tau</h3>
        <p class="text-sm text-slate-600 mb-4">Pentru a folosi chatbot-ul, trebuie mai intai sa adaugi si verifici domeniul site-ului tau.</p>
        <a href="{{ route('dashboard.sites.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Adauga site-ul tau
        </a>
    </div>
    @endif

    {{-- Onboarding --}}
    @if(!$onboardingComplete && empty($_COOKIE['sambla_hide_onboarding']))
    <div id="onboarding-banner" class="relative rounded-xl border border-primary-100 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-slate-900">Bine ai venit! Configureaza Sambla-ul tau</h2>
                <p class="mt-1 text-sm text-slate-500">Urmeaza pasii de mai jos:</p>
                <ul class="mt-4 space-y-2.5">
                    @foreach([
                        ['done' => $onboarding['account'], 'label' => 'Cont creat'],
                        ['done' => $onboarding['first_bot'], 'label' => 'Creeaza primul bot', 'link' => '/dashboard/boti/create'],
                        ['done' => $onboarding['invite_team'], 'label' => 'Invita un coleg', 'link' => '/dashboard/echipa'],
                    ] as $step)
                    <li class="flex items-center gap-2.5">
                        @if($step['done'])
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-slate-500 line-through">{{ $step['label'] }}</span>
                        @else
                            <div class="w-5 h-5 rounded-full border-2 border-slate-300 shrink-0"></div>
                            @if(isset($step['link']))
                                <a href="{{ $step['link'] }}" class="text-sm font-medium text-primary-600 hover:underline">{{ $step['label'] }}</a>
                            @else
                                <span class="text-sm text-slate-500">{{ $step['label'] }}</span>
                            @endif
                        @endif
                    </li>
                    @endforeach
                </ul>
                <button onclick="document.getElementById('onboarding-banner').remove();document.cookie='sambla_hide_onboarding=1;path=/;max-age=31536000;SameSite=Lax';" class="mt-4 text-xs text-slate-400 hover:text-slate-600 underline">Nu mai arata</button>
            </div>
            <button onclick="document.getElementById('onboarding-banner').style.display='none'" class="ml-4 shrink-0 p-1.5 text-slate-400 hover:bg-slate-100 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
    </div>
    @endif

    {{-- Welcome --}}
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-red-800 via-red-700 to-red-900 px-6 py-5 shadow-md">
        {{-- Romanian motif pattern overlay --}}
        <div class="absolute inset-0 opacity-[0.07]" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22><path d=%22M20 0L40 20L20 40L0 20Z%22 fill=%22none%22 stroke=%22white%22 stroke-width=%221.5%22/><path d=%22M20 8L32 20L20 32L8 20Z%22 fill=%22none%22 stroke=%22white%22 stroke-width=%221%22/><circle cx=%2220%22 cy=%2220%22 r=%223%22 fill=%22white%22/></svg>'); background-size: 40px 40px;"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold text-white">
                @php $h = (int) now()->format('H'); @endphp
                {{ $h < 12 ? 'Buna dimineata' : ($h < 18 ? 'Buna ziua' : 'Buna seara') }}, {{ Str::before(auth()->user()->name, ' ') }}
            </h1>
            <p class="mt-0.5 text-sm text-red-100/80">Iata ce se intampla cu platforma ta.</p>
        </div>
    </div>

    {{-- Action Items --}}
    @include('dashboard.partials.action-items')

    {{-- Stat Cards --}}
    @if($hasVoice)
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    @else
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
    @endif
        @php
            $stats = [
                ['label' => 'Boti activi', 'value' => $activeBots, 'sub' => null, 'icon' => 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z', 'cardBg' => 'bg-red-50 border-red-100', 'iconBg' => 'bg-red-100 text-red-700', 'valueColor' => 'text-red-700'],
                ['label' => 'Conversatii', 'value' => $conversationsToday, 'sub' => $messagesToday . ' mesaje azi', 'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z', 'cardBg' => 'bg-blue-50 border-blue-100', 'iconBg' => 'bg-blue-100 text-blue-700', 'valueColor' => 'text-blue-700'],
                ['label' => 'Leads noi', 'value' => $leadsToday, 'sub' => $leadsNew . ' necontactate', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'cardBg' => 'bg-emerald-50 border-emerald-100', 'iconBg' => 'bg-emerald-100 text-emerald-700', 'valueColor' => 'text-emerald-700'],
                ['label' => 'Adaugari cos', 'value' => $addToCartToday, 'sub' => $productClicksToday . ' clickuri', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.34-1.87l1.5-6.69a1.5 1.5 0 00-1.467-1.837H5.106l-.382-1.428A1.5 1.5 0 003.636 1.5H2.25', 'cardBg' => 'bg-purple-50 border-purple-100', 'iconBg' => 'bg-purple-100 text-purple-700', 'valueColor' => 'text-purple-700'],
            ];
            if ($hasVoice) {
                $stats[] = ['label' => 'Apeluri', 'value' => $callsToday, 'sub' => null, 'icon' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'cardBg' => 'bg-amber-50 border-amber-100', 'iconBg' => 'bg-amber-100 text-amber-700', 'valueColor' => 'text-amber-700'];
                $stats[] = ['label' => 'Minute', 'value' => number_format(round($minutesToday)), 'sub' => null, 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'cardBg' => 'bg-sky-50 border-sky-100', 'iconBg' => 'bg-sky-100 text-sky-700', 'valueColor' => 'text-sky-700'];
            }
        @endphp
        @foreach($stats as $stat)
        <div class="rounded-xl border {{ $stat['cardBg'] }} p-4 shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-3xl font-bold {{ $stat['valueColor'] }}">{{ $stat['value'] }}</p>
                    @if($stat['sub'])<p class="text-[11px] text-slate-400 mt-0.5">{{ $stat['sub'] }}</p>@endif
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $stat['iconBg'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Bot Health Cards --}}
    @include('dashboard.partials.bot-health')

    {{-- Plan Usage --}}
    @if($planUsage)
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-900">Utilizare plan</h3>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold bg-gradient-to-r from-red-100 to-red-50 text-red-800 ring-1 ring-red-200/50">{{ $planUsage['plan']['name'] }}</span>
            </div>
            <a href="{{ route('dashboard.billing.index') }}" class="text-xs font-medium text-red-700 hover:underline">Upgrade &rarr;</a>
        </div>
        @if($hasVoice)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @endif
            @foreach([
                ['label' => 'Mesaje', 'data' => $planUsage['messages'], 'barColor' => 'bg-blue-500'],
                ['label' => 'Chatboti', 'data' => $planUsage['bots'], 'barColor' => 'bg-red-500'],
            ] as $usage)
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-slate-600 font-medium">{{ $usage['label'] }}</span>
                    <span class="{{ $usage['data']['percent'] >= 90 ? 'text-red-600 font-bold' : 'text-slate-500 font-medium' }}">{{ number_format($usage['data']['used']) }}/{{ number_format($usage['data']['limit']) }}</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-slate-100"><div class="h-2.5 rounded-full transition-all {{ $usage['data']['percent'] >= 90 ? 'bg-red-500' : ($usage['data']['percent'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($usage['data']['percent'], 100) }}%"></div></div>
            </div>
            @endforeach
            @if($hasVoice)
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-slate-600 font-medium">Minute voce</span>
                    <span class="text-slate-500 font-medium">{{ number_format($planUsage['voice_minutes']['used']) }}/{{ $planUsage['voice_minutes']['limit'] == -1 ? '&infin;' : number_format($planUsage['voice_minutes']['limit']) }}</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-slate-100"><div class="h-2.5 rounded-full bg-purple-500 transition-all" style="width: {{ min($planUsage['voice_minutes']['percent'], 100) }}%"></div></div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Chart + Lead Pipeline --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="bg-red-50 border-b border-red-100 px-5 py-3">
                <h3 class="text-sm font-semibold text-red-900">Activitate &mdash; 7 zile</h3>
            </div>
            <div class="p-5" style="height: 240px;"><canvas id="activityChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Pipeline Leads</h3>
                <a href="/dashboard/leads" class="text-xs text-red-700 hover:underline">Toate &rarr;</a>
            </div>
            @php $stages = ['new'=>['Noi','bg-blue-500'],'contacted'=>['Contactati','bg-sky-500'],'scheduled'=>['Programati','bg-amber-500'],'met'=>['Intalnire','bg-orange-500'],'quoted'=>['Oferta','bg-purple-500'],'won'=>['Castigati','bg-emerald-500'],'lost'=>['Pierduti','bg-red-500']]; @endphp
            <div class="space-y-2.5">
                @foreach($stages as $key => [$label, $color])
                @php $c = $leadPipeline[$key] ?? 0; @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5"><div class="w-2.5 h-2.5 rounded-full {{ $color }} ring-2 ring-offset-1 {{ str_replace('bg-', 'ring-', $color) }}/30"></div><span class="text-xs text-slate-600 font-medium">{{ $label }}</span></div>
                    <span class="text-xs font-bold {{ $c > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $c }}</span>
                </div>
                @endforeach
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs font-medium text-slate-600">Total</span>
                <span class="text-base font-bold text-slate-900">{{ $leadsTotal }}</span>
            </div>
        </div>
    </div>

    {{-- Recent Activity: Conversations + Leads --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Conversations --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between bg-slate-50 border-b border-slate-200 px-5 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Ultimele conversatii</h3>
                <span class="text-[11px] text-slate-400">{{ number_format($totalConversations) }} total</span>
            </div>
            @forelse($recentConversations as $conv)
            <a href="{{ route('dashboard.conversations.show', $conv) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-b-0">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center">
                    <span class="text-xs font-bold text-red-700">{{ mb_strtoupper(mb_substr($conv->contact_name ?: ($conv->contact_identifier ?: 'V'), 0, 1)) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-medium text-slate-900 truncate block">{{ $conv->contact_name ?: ($conv->contact_identifier ?: 'Vizitator') }}</span>
                    <span class="text-[11px] text-slate-400">{{ $conv->bot?->name }} &middot; {{ $conv->messages_count }} msg</span>
                </div>
                <span class="text-[10px] text-slate-400 ml-3 whitespace-nowrap">{{ $conv->created_at->diffForHumans() }}</span>
            </a>
            @empty
            <div class="px-5 py-6 text-center text-xs text-slate-400">Nicio conversatie inca.</div>
            @endforelse
        </div>

        {{-- Leads --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between bg-slate-50 border-b border-slate-200 px-5 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Ultimele leads</h3>
                <a href="/dashboard/leads" class="text-[11px] text-red-700 hover:underline">Toate &rarr;</a>
            </div>
            @forelse($recentLeads as $lead)
            <a href="{{ route('dashboard.leads.show', $lead) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-b-0">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center">
                    <span class="text-xs font-bold text-red-700">{{ mb_strtoupper(mb_substr($lead->name ?: ($lead->email ?: ($lead->phone ?: 'L')), 0, 1)) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-medium text-slate-900 truncate block">{{ $lead->name ?: ($lead->email ?: ($lead->phone ?: 'Lead #'.$lead->id)) }}</span>
                    <span class="text-[11px] text-slate-400">{{ $lead->email ?: $lead->phone ?: '-' }}</span>
                </div>
                <div class="text-right ml-3">
                    @php $sc = ['new'=>'bg-blue-100 text-blue-700','contacted'=>'bg-sky-100 text-sky-700','won'=>'bg-emerald-100 text-emerald-700','lost'=>'bg-red-100 text-red-700']; @endphp
                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $sc[$lead->pipeline_stage] ?? 'bg-slate-100 text-slate-500' }}">{{ \App\Models\Lead::STAGES[$lead->pipeline_stage] ?? $lead->pipeline_stage }}</span>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $lead->created_at->diffForHumans() }}</div>
                </div>
            </a>
            @empty
            <div class="px-5 py-6 text-center text-xs text-slate-400">Lead-urile sunt capturate automat din conversatii.</div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([
            ['href' => '/dashboard/boti/create', 'title' => 'Creeaza un bot', 'desc' => 'Chatbot nou cu personalitate custom.', 'color' => 'primary', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
            ['href' => '/dashboard/leads', 'title' => 'Gestioneaza leads', 'desc' => 'Lead-uri capturate din conversatii.', 'color' => 'emerald', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['href' => '/dashboard/echipa', 'title' => 'Invita un coleg', 'desc' => 'Adauga membri in echipa.', 'color' => 'sky', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
        ] as $action)
        @php
            $cardStyles = [
                'primary' => 'bg-red-50 border-red-100 hover:border-red-200 hover:bg-red-50/80',
                'emerald' => 'bg-emerald-50 border-emerald-100 hover:border-emerald-200 hover:bg-emerald-50/80',
                'sky' => 'bg-sky-50 border-sky-100 hover:border-sky-200 hover:bg-sky-50/80',
            ];
            $iconStyles = [
                'primary' => 'bg-red-100 text-red-700',
                'emerald' => 'bg-emerald-100 text-emerald-700',
                'sky' => 'bg-sky-100 text-sky-700',
            ];
        @endphp
        <a href="{{ $action['href'] }}" class="group flex items-center gap-4 rounded-xl border {{ $cardStyles[$action['color']] }} p-4 shadow-sm transition-all hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconStyles[$action['color']] }} shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" /></svg>
            </div>
            <div><h4 class="text-sm font-semibold text-slate-900">{{ $action['title'] }}</h4><p class="text-xs text-slate-500">{{ $action['desc'] }}</p></div>
        </a>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('activityChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartData->pluck('date')),
            datasets: [
                { label: 'Conversatii', data: @json($chartData->pluck('conversations')), backgroundColor: '#3b82f6', borderRadius: 4, maxBarThickness: 20, order: 2 },
                { label: 'Leads', data: @json($chartData->pluck('leads')), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 20, order: 3 },
                { label: 'Mesaje', data: @json($chartData->pluck('messages')), type: 'line', borderColor: '#94a3b8', borderWidth: 2, pointRadius: 2, tension: 0.3, fill: false, order: 1, yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle', padding: 12, font: { size: 10 } } }, tooltip: { backgroundColor: '#1e293b', cornerRadius: 6, padding: 8, titleFont: { size: 11 }, bodyFont: { size: 11 } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } }, border: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 }, stepSize: 1, precision: 0 }, border: { display: false } },
                y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#cbd5e1', font: { size: 9 } }, border: { display: false } },
            },
        },
    });
});
</script>
@endpush
