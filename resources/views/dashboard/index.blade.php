@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb')
<span class="text-ink font-medium">Dashboard</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Super-admin "Doar eu" without an own tenant: explain the empty state.
         Without this the dashboard looks broken — every stat card shows 0
         because TenantScope filters to tenant_id=0 in this mode. --}}
    @if(auth()->user()->hasRole('super_admin') && !auth()->user()->getAttributes()['tenant_id'] && !session('admin_as_tenant_id') && !session('admin_view_all', false))
    <div class="rounded-xl border border-line bg-cream p-6">
        <div class="flex items-start gap-4">
            <div class="shrink-0 w-10 h-10 rounded-full bg-sand flex items-center justify-center">
                <svg class="w-5 h-5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-ink">Nu ai date proprii în acest dashboard</h3>
                <p class="mt-1 text-sm text-muted">Ești logat ca super admin fără tenant propriu. Folosește selectorul din dreapta-sus („<span class="font-medium">Doar eu</span>") ca să vizualizezi platforma ca un tenant anume sau să vezi agregat toate tenant-urile.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- No Sites Banner --}}
    @if(auth()->user()->tenant?->sites()->count() === 0 && auth()->user()->tenant)
    <div class="bg-gradient-to-r from-red-50 to-amber-50 border border-coral/30 rounded-xl p-6">
        <h3 class="text-lg font-bold text-ink mb-2">Incepe prin adaugarea site-ului tau</h3>
        <p class="text-sm text-muted mb-4">Pentru a folosi agentul AI, trebuie mai intai sa adaugi si verifici domeniul site-ului tau.</p>
        <a href="{{ route('dashboard.sites.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
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
                <h2 class="text-xl font-semibold text-ink">Bine ai venit! Configureaza Sambla-ul tau</h2>
                <p class="mt-1 text-sm text-muted">Urmeaza pasii de mai jos:</p>
                <ul class="mt-4 space-y-2.5">
                    @foreach([
                        ['done' => $onboarding['account'], 'label' => 'Cont creat'],
                        ['done' => $onboarding['first_bot'], 'label' => 'Creeaza primul agent AI', 'link' => '/dashboard/boti/create'],
                        ['done' => $onboarding['invite_team'], 'label' => 'Invita un coleg', 'link' => '/dashboard/echipa'],
                    ] as $step)
                    <li class="flex items-center gap-2.5">
                        @if($step['done'])
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <span class="text-sm text-muted line-through">{{ $step['label'] }}</span>
                        @else
                            <div class="w-5 h-5 rounded-full border-2 border-line shrink-0"></div>
                            @if(isset($step['link']))
                                <a href="{{ $step['link'] }}" class="text-sm font-medium text-primary-600 hover:underline">{{ $step['label'] }}</a>
                            @else
                                <span class="text-sm text-muted">{{ $step['label'] }}</span>
                            @endif
                        @endif
                    </li>
                    @endforeach
                </ul>
                <button onclick="document.getElementById('onboarding-banner').remove();document.cookie='sambla_hide_onboarding=1;path=/;max-age=31536000;SameSite=Lax';" class="mt-4 text-xs text-muted hover:text-muted underline">Nu mai arata</button>
            </div>
            <button onclick="document.getElementById('onboarding-banner').style.display='none'" class="ml-4 shrink-0 p-1.5 text-muted hover:bg-cream rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
    </div>
    @endif

    {{-- HERO BRIEFING — element dominant. Ink + coral glow + grain.
         Înlocuiește welcome + live-activity-widget + stat cards row.
         Live counter & deltas se actualizează la 5s prin /dashboard/live-activity. --}}
    @php
        $todayConv = $chartData->get(6)['conversations'] ?? $conversationsToday;
        $yesterdayConv = $chartData->get(5)['conversations'] ?? 0;
        $delta = $yesterdayConv > 0 ? (int) round((($todayConv - $yesterdayConv) / $yesterdayConv) * 100) : ($todayConv > 0 ? 100 : 0);
        $h = (int) now()->format('H');
        $greeting = $h < 12 ? 'Bună dimineața' : ($h < 18 ? 'Bună ziua' : 'Bună seara');
    @endphp
    <section x-data="dashHero()" x-init="start()" class="relative overflow-hidden rounded-card bg-ink shadow-md">
        <div class="absolute inset-0 opacity-[0.06] pointer-events-none" style="background-image: radial-gradient(rgba(255,255,255,0.7) 1px, transparent 1px); background-size: 6px 6px;"></div>
        <div class="absolute -top-32 -right-24 w-[28rem] h-[28rem] rounded-full bg-coral/20 blur-3xl pointer-events-none"></div>
        <div x-show="flash" x-transition.opacity.duration.700ms class="absolute inset-0 bg-coral/10 pointer-events-none"></div>

        <div class="relative grid lg:grid-cols-12 gap-6 lg:gap-8 p-6 md:p-8">
            {{-- LEFT: greeting + narrative + live pill --}}
            <div class="lg:col-span-5 flex flex-col justify-between">
                <div>
                    <p class="text-2xs uppercase tracking-widest text-cream/40 font-mono">{{ now()->translatedFormat('l, j F Y') }}</p>
                    <h1 class="display text-3xl md:text-4xl font-semibold text-cream mt-3 leading-[1.1]">
                        {{ $greeting }},<br>
                        <span class="text-coral">{{ Str::before(auth()->user()->name, ' ') }}</span>.
                    </h1>
                    <p class="mt-3 text-sm text-cream/70 leading-relaxed max-w-md">
                        @if($todayConv === 0)
                            Nicio conversație azi încă. Agenții așteaptă vizitatori.
                        @else
                            <span class="text-cream font-medium">{{ $todayConv }}</span> {{ $todayConv === 1 ? 'conversație' : 'conversații' }} azi@if($delta !== 0 && $yesterdayConv > 0)<span class="{{ $delta > 0 ? 'text-emerald-300' : 'text-coral' }}"> ({{ $delta > 0 ? '+' : '' }}{{ $delta }}% vs ieri)</span>@endif@if($leadsToday > 0), <span class="text-cream font-medium">{{ $leadsToday }}</span> {{ $leadsToday === 1 ? 'lead nou' : 'leads noi' }}@endif.
                        @endif
                    </p>
                </div>

                <div class="mt-6 inline-flex items-center gap-3 rounded-full bg-cream/5 ring-1 ring-cream/10 px-4 py-2 self-start">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    </span>
                    <span class="text-xs text-cream/80 font-medium">
                        Acum: <span class="text-cream font-semibold tabular-nums" x-text="liveActive">—</span> conv. active · <span class="text-cream font-semibold tabular-nums" x-text="liveMessages">—</span> mesaje/h
                    </span>
                </div>
            </div>

            {{-- CENTER: hero metric + sparkline --}}
            <div class="lg:col-span-4 lg:border-l lg:border-cream/10 lg:pl-8">
                <p class="text-2xs uppercase tracking-widest text-cream/50 font-semibold">Conversații azi</p>
                <div class="flex items-baseline gap-3 mt-2 flex-wrap">
                    <span class="display text-6xl md:text-7xl font-semibold text-cream tabular-nums leading-none">{{ $todayConv }}</span>
                    @if($delta !== 0 && $yesterdayConv > 0)
                        <span class="inline-flex items-center gap-1 text-sm font-medium {{ $delta > 0 ? 'text-emerald-300' : 'text-coral' }}">
                            @if($delta > 0)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8m0 0v6m0-6h-6"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l6 6 4-4 8 8m0 0v-6m0 6h-6"/></svg>
                            @endif
                            {{ abs($delta) }}%
                        </span>
                    @endif
                </div>
                <p class="text-xs text-cream/50 mt-1.5 mono">{{ $messagesToday }} mesaje · ultimele 7 zile</p>
                <canvas id="heroSparkline" class="mt-4 w-full" style="height: 72px;"></canvas>
            </div>

            {{-- RIGHT: secondary metrics --}}
            <div class="lg:col-span-3 grid grid-cols-2 gap-3 content-start">
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Leads</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums">{{ $leadsToday }}</p>
                    <p class="text-2xs text-cream/40 mt-0.5">{{ $leadsNew }} necontactate</p>
                </div>
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Programări</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums" x-text="liveCallbacks">—</p>
                    <p class="text-2xs text-cream/40 mt-0.5">azi</p>
                </div>
                @if($hasVoice)
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Apeluri</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums">{{ $callsToday }}</p>
                    <p class="text-2xs text-cream/40 mt-0.5">{{ number_format(round($minutesToday)) }} min</p>
                </div>
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Agenți</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums">{{ $activeBots }}</p>
                    <p class="text-2xs text-cream/40 mt-0.5">activi</p>
                </div>
                @else
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Agenți</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums">{{ $activeBots }}</p>
                    <p class="text-2xs text-cream/40 mt-0.5">activi</p>
                </div>
                <div class="rounded-xl bg-cream/5 ring-1 ring-cream/10 p-3 hover:bg-cream/10 transition-colors">
                    <p class="text-2xs uppercase tracking-wider text-cream/50 font-medium">Coș</p>
                    <p class="display text-2xl font-semibold text-cream mt-1 tabular-nums">{{ $addToCartToday }}</p>
                    <p class="text-2xs text-cream/40 mt-0.5">{{ $productClicksToday }} click-uri</p>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- AI Insights — analiză LLM a ultimei săptămâni --}}
    @include('partials.insights-widget')

    {{-- Cost forecast — DOAR super-admin. Tenanții văd consumul lor în
         /dashboard/billing (mesaje folosite din plan + overage), nu costul
         nostru intern de infrastructură. Aici e dashboard-ul de margin. --}}
    @if(auth()->user()->isSuperAdmin())
        @include('partials.cost-forecast-widget')
    @endif

    {{-- Action Items --}}
    @include('dashboard.partials.action-items')

    {{-- Stat cards row eliminat — toate metricile sunt acum în HERO BRIEFING (sus). --}}

    {{-- Bot Health Cards --}}
    @include('dashboard.partials.bot-health')

    {{-- Plan Usage --}}
    @if($planUsage)
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h3 class="display text-base font-semibold text-ink">Utilizare plan</h3>
                <span class="inline-flex items-center rounded-pill px-2.5 py-0.5 text-2xs font-semibold bg-coralsoft text-coralh ring-1 ring-coral/20">{{ $planUsage['plan']['name'] }}</span>
            </div>
            <a href="{{ route('dashboard.billing.index') }}" class="text-xs font-medium text-coralh hover:underline">Upgrade &rarr;</a>
        </div>
        @if($hasVoice)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @endif
            @foreach([
                ['label' => 'Mesaje', 'data' => $planUsage['messages'], 'barColor' => 'bg-blue-500'],
                ['label' => 'Agenți AI', 'data' => $planUsage['bots'], 'barColor' => 'bg-red-500'],
            ] as $usage)
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-muted font-medium">{{ $usage['label'] }}</span>
                    <span class="{{ $usage['data']['percent'] >= 90 ? 'text-coral font-bold' : 'text-muted font-medium' }}">{{ number_format($usage['data']['used']) }}/{{ number_format($usage['data']['limit']) }}</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-cream"><div class="h-2.5 rounded-full transition-all {{ $usage['data']['percent'] >= 90 ? 'bg-red-500' : ($usage['data']['percent'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($usage['data']['percent'], 100) }}%"></div></div>
            </div>
            @endforeach
            @if($hasVoice)
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-muted font-medium">Minute voce</span>
                    <span class="text-muted font-medium">{{ number_format($planUsage['voice_minutes']['used']) }}/{{ $planUsage['voice_minutes']['limit'] == -1 ? '&infin;' : number_format($planUsage['voice_minutes']['limit']) }}</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-cream"><div class="h-2.5 rounded-full bg-purple-500 transition-all" style="width: {{ min($planUsage['voice_minutes']['percent'], 100) }}%"></div></div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Chart + Lead Pipeline --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="bg-coralsoft border-b border-coral/15 px-5 py-3">
                <h3 class="display text-base font-semibold text-coralh">Activitate · 7 zile</h3>
            </div>
            <div class="p-5" style="height: 240px;"><canvas id="activityChart"></canvas></div>
        </div>
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="display text-base font-semibold text-ink">Pipeline Leads</h3>
                <a href="/dashboard/leads" class="text-xs text-coralh hover:underline">Toate &rarr;</a>
            </div>
            @php $stages = ['new'=>['Noi','bg-blue-500'],'contacted'=>['Contactati','bg-sky-500'],'scheduled'=>['Programati','bg-amber-500'],'met'=>['Intalnire','bg-orange-500'],'quoted'=>['Oferta','bg-purple-500'],'won'=>['Castigati','bg-emerald-500'],'lost'=>['Pierduti','bg-red-500']]; @endphp
            <div class="space-y-2.5">
                @foreach($stages as $key => [$label, $color])
                @php $c = $leadPipeline[$key] ?? 0; @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5"><div class="w-2.5 h-2.5 rounded-full {{ $color }} ring-2 ring-offset-1 {{ str_replace('bg-', 'ring-', $color) }}/30"></div><span class="text-xs text-muted font-medium">{{ $label }}</span></div>
                    <span class="text-xs font-bold {{ $c > 0 ? 'text-ink' : 'text-line' }}">{{ $c }}</span>
                </div>
                @endforeach
            </div>
            <div class="mt-3 pt-3 border-t border-line flex items-center justify-between">
                <span class="text-xs font-medium text-muted">Total</span>
                <span class="text-base font-bold text-ink">{{ $leadsTotal }}</span>
            </div>
        </div>
    </div>

    {{-- Recent Activity: Conversations + Leads --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Conversations --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between bg-cream border-b border-line px-5 py-3">
                <h3 class="display text-base font-semibold text-ink">Ultimele conversații</h3>
                <span class="text-2xs text-muted mono">{{ number_format($totalConversations) }} total</span>
            </div>
            @forelse($recentConversations as $conv)
            <a href="{{ route('dashboard.conversations.show', $conv) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-cream transition-colors border-b border-slate-50 last:border-b-0">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center">
                    <span class="text-xs font-bold text-coralh">{{ mb_strtoupper(mb_substr($conv->contact_name ?: ($conv->contact_identifier ?: 'V'), 0, 1)) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-medium text-ink truncate block">{{ $conv->contact_name ?: ($conv->contact_identifier ?: 'Vizitator') }}</span>
                    <span class="text-[11px] text-muted">{{ $conv->bot?->name }} &middot; {{ $conv->messages_count }} msg</span>
                </div>
                <span class="text-[10px] text-muted ml-3 whitespace-nowrap">{{ $conv->created_at->diffForHumans() }}</span>
            </a>
            @empty
            <div class="px-5 py-6 text-center text-xs text-muted">Nicio conversatie inca.</div>
            @endforelse
        </div>

        {{-- Leads --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between bg-cream border-b border-line px-5 py-3">
                <h3 class="display text-base font-semibold text-ink">Ultimele leads</h3>
                <a href="/dashboard/leads" class="text-2xs text-coralh hover:underline">Toate &rarr;</a>
            </div>
            @forelse($recentLeads as $lead)
            <a href="{{ route('dashboard.leads.show', $lead) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-cream transition-colors border-b border-slate-50 last:border-b-0">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center">
                    <span class="text-xs font-bold text-coralh">{{ mb_strtoupper(mb_substr($lead->name ?: ($lead->email ?: ($lead->phone ?: 'L')), 0, 1)) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-sm font-medium text-ink truncate block">{{ $lead->name ?: ($lead->email ?: ($lead->phone ?: 'Lead #'.$lead->id)) }}</span>
                    <span class="text-[11px] text-muted">{{ $lead->email ?: $lead->phone ?: '-' }}</span>
                </div>
                <div class="text-right ml-3">
                    @php $sc = ['new'=>'bg-blue-100 text-blue-700','contacted'=>'bg-sky-100 text-sky-700','won'=>'bg-emerald-100 text-emerald-700','lost'=>'bg-coralsoft text-coralh']; @endphp
                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $sc[$lead->pipeline_stage] ?? 'bg-cream text-muted' }}">{{ \App\Models\Lead::STAGES[$lead->pipeline_stage] ?? $lead->pipeline_stage }}</span>
                    <div class="text-[10px] text-muted mt-0.5">{{ $lead->created_at->diffForHumans() }}</div>
                </div>
            </a>
            @empty
            <div class="px-5 py-6 text-center text-xs text-muted">Lead-urile sunt capturate automat din conversatii.</div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([
            ['href' => '/dashboard/boti/create', 'title' => 'Creeaza un agent AI', 'desc' => 'Agent AI nou cu personalitate custom.', 'color' => 'primary', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
            ['href' => '/dashboard/leads', 'title' => 'Gestioneaza leads', 'desc' => 'Lead-uri capturate din conversatii.', 'color' => 'emerald', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['href' => '/dashboard/echipa', 'title' => 'Invita un coleg', 'desc' => 'Adauga membri in echipa.', 'color' => 'sky', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
        ] as $action)
        @php
            $cardStyles = [
                'primary' => 'bg-coralsoft border-coralsoft hover:border-coral/30 hover:bg-coralsoft/80',
                'emerald' => 'bg-emerald-50 border-emerald-100 hover:border-emerald-200 hover:bg-emerald-50/80',
                'sky' => 'bg-sky-50 border-sky-100 hover:border-sky-200 hover:bg-sky-50/80',
            ];
            $iconStyles = [
                'primary' => 'bg-coralsoft text-coralh',
                'emerald' => 'bg-emerald-100 text-emerald-700',
                'sky' => 'bg-sky-100 text-sky-700',
            ];
        @endphp
        <a href="{{ $action['href'] }}" class="group flex items-center gap-4 rounded-xl border {{ $cardStyles[$action['color']] }} p-4 shadow-sm transition-all hover:shadow-md">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $iconStyles[$action['color']] }} shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}" /></svg>
            </div>
            <div><h4 class="text-sm font-semibold text-ink">{{ $action['title'] }}</h4><p class="text-xs text-muted">{{ $action['desc'] }}</p></div>
        </a>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Hero sparkline + live counters (Alpine component)
function dashHero() {
    return {
        liveActive: '—',
        liveMessages: '—',
        liveCallbacks: '—',
        liveCalls: '—',
        flash: false,
        prev: {},
        timer: null,
        start() {
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 5000);
        },
        async refresh() {
            try {
                const r = await fetch('/dashboard/live-activity', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                const map = {
                    liveActive:   d.active_conversations ?? 0,
                    liveMessages: d.messages_last_hour   ?? 0,
                    liveCallbacks:d.callbacks_today      ?? 0,
                    liveCalls:    d.calls_in_progress    ?? 0,
                };
                let changed = false;
                for (const k in map) {
                    if (this.prev[k] !== undefined && this.prev[k] !== map[k]) changed = true;
                    this.prev[k] = map[k];
                    this[k] = String(map[k]);
                }
                if (changed) { this.flash = true; setTimeout(() => this.flash = false, 700); }
            } catch (e) {}
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    // Hero sparkline — gradient area chart, minimal axes
    var sparkCtx = document.getElementById('heroSparkline');
    if (sparkCtx) {
        var sparkData = @json($chartData->pluck('conversations'));
        var grad = sparkCtx.getContext('2d').createLinearGradient(0, 0, 0, 72);
        grad.addColorStop(0, 'rgba(220, 38, 38, 0.55)');
        grad.addColorStop(1, 'rgba(220, 38, 38, 0)');
        new Chart(sparkCtx, {
            type: 'line',
            data: {
                labels: sparkData.map(function(_, i) { return i; }),
                datasets: [{
                    data: sparkData,
                    borderColor: '#fda4af',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#fda4af',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: grad,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
                animation: { duration: 800 },
                elements: { line: { capBezierPoints: true } },
            },
        });
    }

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
