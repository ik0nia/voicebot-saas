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

    {{-- Plan usage — strip cu gauges circulare, gradient cream→coral, CTA mare --}}
    @if($planUsage)
    @php
        $usageItems = [
            ['label' => 'Mesaje', 'data' => $planUsage['messages']],
            ['label' => 'Agenți', 'data' => $planUsage['bots']],
        ];
        if ($hasVoice) {
            $usageItems[] = ['label' => 'Min. voce', 'data' => $planUsage['voice_minutes']];
        }
    @endphp
    <section class="relative overflow-hidden rounded-card border border-coral/20 bg-gradient-to-br from-coralsoft via-cream to-white p-5 md:p-6 shadow-sm">
        <div class="absolute -bottom-16 -right-16 w-56 h-56 rounded-full bg-coral/8 blur-3xl pointer-events-none"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center gap-5 lg:gap-8">
            {{-- Plan label --}}
            <div class="flex items-center gap-3 lg:shrink-0">
                <div class="hidden md:flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-coral/15">
                    <svg class="w-5 h-5 text-coralh" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-2xs uppercase tracking-widest text-muted font-semibold">Plan curent</p>
                    <h3 class="display text-xl md:text-2xl font-semibold text-ink leading-tight mt-0.5">{{ $planUsage['plan']['name'] }}</h3>
                </div>
            </div>

            {{-- Gauges --}}
            <div class="grid grid-cols-{{ count($usageItems) }} gap-4 md:gap-6 flex-1 lg:border-l lg:border-coral/15 lg:pl-8">
                @foreach($usageItems as $u)
                @php
                    $pct = (int) min(100, $u['data']['percent']);
                    $isInf = ($u['data']['limit'] ?? 0) === -1;
                    $col = $isInf ? 'text-emerald-600' : ($pct >= 90 ? 'text-coral' : ($pct >= 70 ? 'text-amber-600' : 'text-emerald-600'));
                @endphp
                <div class="flex items-center gap-3">
                    <div class="relative w-12 h-12 shrink-0">
                        <svg class="w-12 h-12 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e7e5e4" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" class="{{ $col }} transition-all" stroke-width="3"
                                    stroke-dasharray="{{ $isInf ? 100 : $pct }} {{ $isInf ? 0 : 100 - $pct }}" stroke-linecap="round"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold {{ $col }} font-mono">{{ $isInf ? '∞' : $pct.'%' }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xs uppercase tracking-wider text-muted font-medium">{{ $u['label'] }}</p>
                        <p class="text-sm font-semibold text-ink mt-0.5 font-mono whitespace-nowrap">
                            {{ number_format($u['data']['used']) }}<span class="text-muted">/{{ $isInf ? '∞' : number_format($u['data']['limit']) }}</span>
                        </p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Upgrade CTA --}}
            <a href="{{ route('dashboard.billing.index') }}" class="inline-flex items-center justify-center gap-2 rounded-pill px-5 py-2.5 bg-ink text-cream text-sm font-semibold shadow-sm hover:bg-ink/85 transition-colors lg:shrink-0">
                Upgrade plan
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>
    @endif

    {{-- ACTIVITY CHART — full-width gradient area, fără card-wrapper banal --}}
    <section class="rounded-card border border-line bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 pt-5 pb-2">
            <div>
                <h3 class="display text-lg font-semibold text-ink">Activitate · ultimele 7 zile</h3>
                <p class="text-xs text-muted mt-0.5">Conversații, mesaje și leads pe zi</p>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-2xs text-muted">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-coral"></span> Conversații</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Leads</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-ink/40"></span> Mesaje</span>
            </div>
        </div>
        <div class="px-4 pb-4" style="height: 280px;"><canvas id="activityChart"></canvas></div>
    </section>

    {{-- Pipeline funnel + Activity timeline — 2 col, accent = timeline (story) --}}
    @php
        $timeline = $recentConversations->map(fn($c) => [
            'type' => 'conversation',
            'at' => $c->created_at,
            'href' => route('dashboard.conversations.show', $c),
            'name' => $c->contact_name ?: ($c->contact_identifier ?: 'Vizitator'),
            'sub' => ($c->bot?->name ?: 'Agent') . ' · ' . $c->messages_count . ' mesaje',
            'badge' => null,
        ])->concat($recentLeads->map(fn($l) => [
            'type' => 'lead',
            'at' => $l->created_at,
            'href' => route('dashboard.leads.show', $l),
            'name' => $l->name ?: ($l->email ?: ($l->phone ?: 'Lead #'.$l->id)),
            'sub' => $l->email ?: $l->phone ?: 'Lead capturat',
            'badge' => \App\Models\Lead::STAGES[$l->pipeline_stage] ?? $l->pipeline_stage,
            'badgeStage' => $l->pipeline_stage,
        ]))->sortByDesc('at')->take(10)->values();

        $stages = [
            'new'=>['Noi','#3b82f6'], 'contacted'=>['Contactați','#0ea5e9'],
            'scheduled'=>['Programați','#f59e0b'], 'met'=>['Întâlnire','#f97316'],
            'quoted'=>['Ofertă','#8b5cf6'], 'won'=>['Câștigați','#10b981'],
            'lost'=>['Pierduți','#ef4444'],
        ];
        $pipelineTotal = array_sum(array_map(fn($k) => $leadPipeline[$k] ?? 0, array_keys($stages)));
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT: Activity timeline (2/3) --}}
        <div class="lg:col-span-2 rounded-card border border-line bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <div>
                    <h3 class="display text-base font-semibold text-ink">Activitate recentă</h3>
                    <p class="text-2xs text-muted mt-0.5 mono">{{ number_format($totalConversations) }} conversații · {{ number_format($leadsTotal) }} leads total</p>
                </div>
                <a href="/dashboard/inbox" class="text-xs font-medium text-coralh hover:underline">Toate &rarr;</a>
            </div>
            @forelse($timeline as $event)
            <a href="{{ $event['href'] }}" class="group flex items-center gap-4 px-6 py-3 hover:bg-cream/50 transition-colors border-b border-line/50 last:border-b-0">
                {{-- Type indicator --}}
                <div class="relative shrink-0">
                    <div class="w-9 h-9 rounded-full {{ $event['type'] === 'conversation' ? 'bg-coralsoft text-coralh' : 'bg-emerald-50 text-emerald-700' }} flex items-center justify-center font-bold text-xs">
                        {{ mb_strtoupper(mb_substr($event['name'], 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full {{ $event['type'] === 'conversation' ? 'bg-coral' : 'bg-emerald-500' }} ring-2 ring-white flex items-center justify-center">
                        @if($event['type'] === 'conversation')
                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                        @else
                            <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5L20 7"/></svg>
                        @endif
                    </span>
                </div>
                {{-- Body --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-ink truncate">{{ $event['name'] }}</span>
                        @if($event['type'] === 'lead' && !empty($event['badge']))
                            @php $bs = ['new'=>'bg-blue-50 text-blue-700 ring-blue-200','contacted'=>'bg-sky-50 text-sky-700 ring-sky-200','won'=>'bg-emerald-50 text-emerald-700 ring-emerald-200','lost'=>'bg-coralsoft text-coralh ring-coral/30']; @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 {{ $bs[$event['badgeStage']] ?? 'bg-cream text-muted ring-line' }}">{{ $event['badge'] }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-muted truncate">
                        {{ $event['type'] === 'conversation' ? 'Conversație' : 'Lead nou' }} · {{ $event['sub'] }}
                    </p>
                </div>
                {{-- Time + arrow --}}
                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-2xs text-muted mono whitespace-nowrap">{{ $event['at']->diffForHumans(null, true) }}</span>
                    <svg class="w-4 h-4 text-line group-hover:text-coralh group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-sm text-muted">Nicio activitate încă.</p>
                <p class="text-xs text-muted mt-1">Conversațiile și lead-urile capturate apar aici în timp real.</p>
            </div>
            @endforelse
        </div>

        {{-- RIGHT: Pipeline funnel (1/3) — vertical stages with gradient bars --}}
        <div class="rounded-card border border-line bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <div>
                    <h3 class="display text-base font-semibold text-ink">Pipeline leads</h3>
                    <p class="text-2xs text-muted mt-0.5 mono">{{ $pipelineTotal }} în pipeline · {{ $leadsTotal }} total</p>
                </div>
                <a href="/dashboard/leads" class="text-xs font-medium text-coralh hover:underline">Vezi &rarr;</a>
            </div>
            <div class="p-5 space-y-3">
                @foreach($stages as $key => [$label, $hex])
                    @php
                        $c = $leadPipeline[$key] ?? 0;
                        $maxStage = max(1, ...array_map(fn($k) => $leadPipeline[$k] ?? 0, array_keys($stages)));
                        $barPct = $maxStage > 0 ? min(100, ($c / $maxStage) * 100) : 0;
                    @endphp
                    <div class="group">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $hex }}"></span>
                                <span class="font-medium text-ink">{{ $label }}</span>
                            </div>
                            <span class="font-mono font-semibold {{ $c > 0 ? 'text-ink' : 'text-line' }}">{{ $c }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-cream overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out"
                                 style="width: {{ $barPct }}%; background: linear-gradient(90deg, {{ $hex }}66 0%, {{ $hex }} 100%);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Bot Health Cards — la final, după activitate (drill-in pe agenți, nu summary) --}}
    @include('dashboard.partials.bot-health')

    {{-- Quick Actions — afișate doar dacă onboarding incomplet (filler dacă e gata). --}}
    @if(!$onboardingComplete)
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach([
            ['href' => '/dashboard/boti/create', 'title' => 'Creează un agent AI', 'desc' => 'Agent AI nou cu personalitate custom.', 'color' => 'primary', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
            ['href' => '/dashboard/leads', 'title' => 'Gestionează leads', 'desc' => 'Lead-uri capturate din conversații.', 'color' => 'emerald', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['href' => '/dashboard/echipa', 'title' => 'Invită un coleg', 'desc' => 'Adaugă membri în echipă.', 'color' => 'sky', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
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
    @endif
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
    var c2d = ctx.getContext('2d');
    var convGrad = c2d.createLinearGradient(0, 0, 0, 280);
    convGrad.addColorStop(0, 'rgba(220, 38, 38, 0.40)');
    convGrad.addColorStop(1, 'rgba(220, 38, 38, 0.02)');
    var leadGrad = c2d.createLinearGradient(0, 0, 0, 280);
    leadGrad.addColorStop(0, 'rgba(16, 185, 129, 0.30)');
    leadGrad.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartData->pluck('date')),
            datasets: [
                { label: 'Conversații', data: @json($chartData->pluck('conversations')), borderColor: '#dc2626', borderWidth: 2.5, backgroundColor: convGrad, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: '#fff', pointHoverBorderColor: '#dc2626', pointHoverBorderWidth: 2, order: 2 },
                { label: 'Leads', data: @json($chartData->pluck('leads')), borderColor: '#10b981', borderWidth: 2.5, backgroundColor: leadGrad, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: '#fff', pointHoverBorderColor: '#10b981', pointHoverBorderWidth: 2, order: 3 },
                { label: 'Mesaje', data: @json($chartData->pluck('messages')), borderColor: 'rgba(28,25,23,0.35)', borderWidth: 1.5, borderDash: [4, 4], pointRadius: 0, tension: 0.3, fill: false, order: 1, yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c1917',
                    titleColor: '#fff', bodyColor: '#fff',
                    cornerRadius: 8, padding: 12,
                    titleFont: { size: 11, weight: '600' }, bodyFont: { size: 12 },
                    boxPadding: 6,
                    displayColors: true,
                    usePointStyle: true,
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#a8a29e', font: { size: 11, weight: '500' } }, border: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f5f5f4', drawTicks: false }, ticks: { color: '#a8a29e', font: { size: 10 }, stepSize: 1, precision: 0, padding: 8 }, border: { display: false } },
                y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#d6d3d1', font: { size: 9 } }, border: { display: false } },
            },
            animation: { duration: 1000, easing: 'easeOutQuart' },
        },
    });
});
</script>
@endpush
