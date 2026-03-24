@extends('layouts.dashboard')

@section('title', 'Knowledge Builder — ' . $bot->name)

@section('breadcrumb')
    <a href="/dashboard/boti" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <svg class="w-4 h-4 text-slate-400 mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <a href="/dashboard/boti/{{ $bot->id }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <svg class="w-4 h-4 text-slate-400 mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Knowledge Builder</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Toast container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-[100] flex flex-col gap-2"></div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Banner: bot fara site asociat --}}
    @if(!$site)
    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="text-sm font-medium text-yellow-800">Botul nu are un site asociat</p>
            <p class="text-xs text-yellow-600">Asociaza un site pentru a restrictiona unde poate rula chatbot-ul. <a href="/dashboard/sites" class="underline hover:text-yellow-800">Gestioneaza sites</a></p>
        </div>
    </div>
    @endif

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Knowledge Builder</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $bot->name }} &mdash; construiește baza de cunoștințe cu agenți AI, fișiere, scanner și conectori</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                {{ $documents->sum('chunks_count') }} fragmente
            </span>
        </div>
    </div>

    {{-- Usage bar --}}
    @if(isset($usageSummary))
    <div class="mb-6 bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-700">Consumul planului <span class="text-xs font-normal text-slate-400">({{ $plan->name ?? 'Free' }})</span></h3>
            <a href="/dashboard/plan" class="text-xs text-red-700 hover:text-red-900 font-medium transition-colors">Upgrade plan</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Knowledge entries --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-slate-500">Boți</span>
                    <span class="text-xs font-medium text-slate-700">{{ $usageSummary['bots']['used'] }}/{{ $usageSummary['bots']['limit'] }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all {{ $usageSummary['bots']['percent'] > 90 ? 'bg-red-600' : ($usageSummary['bots']['percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($usageSummary['bots']['percent'], 100) }}%"></div>
                </div>
            </div>
            {{-- Agent runs --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-slate-500">Rulări agent</span>
                    <span class="text-xs font-medium text-slate-700">{{ $usageSummary['agent_runs']['used'] }}/{{ $usageSummary['agent_runs']['limit'] }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all {{ $usageSummary['agent_runs']['percent'] > 90 ? 'bg-red-600' : ($usageSummary['agent_runs']['percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($usageSummary['agent_runs']['percent'], 100) }}%"></div>
                </div>
            </div>
            {{-- Tokens --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-slate-500">Tokens</span>
                    <span class="text-xs font-medium text-slate-700">{{ number_format($usageSummary['tokens']['used']) }}/{{ number_format($usageSummary['tokens']['limit']) }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all {{ $usageSummary['tokens']['percent'] > 90 ? 'bg-red-600' : ($usageSummary['tokens']['percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($usageSummary['tokens']['percent'], 100) }}%"></div>
                </div>
            </div>
            {{-- Scan pages --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-slate-500">Pagini scanate</span>
                    <span class="text-xs font-medium text-slate-700">{{ $usageSummary['pages_scanned']['used'] }}/{{ $usageSummary['pages_scanned']['limit'] }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all {{ $usageSummary['pages_scanned']['percent'] > 90 ? 'bg-red-600' : ($usageSummary['pages_scanned']['percent'] > 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($usageSummary['pages_scanned']['percent'], 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Onboarding banner --}}
    @if($documents->count() === 0)
    <div class="mb-6 bg-gradient-to-br from-red-50 to-orange-50 rounded-xl border border-red-200/60 p-6">
        <div class="text-center max-w-lg mx-auto">
            <svg class="w-12 h-12 text-red-700/80 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <h3 class="text-lg font-bold text-slate-900 mb-1">Baza de cunoștințe este goală</h3>
            <p class="text-sm text-slate-600 mb-5">Alege una din metodele de mai jos pentru a adăuga conținut botului tău.</p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <button onclick="switchBuilderTab('agents')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Generează cu AI
                </button>
                <button onclick="switchBuilderTab('upload')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload fișiere
                </button>
                <button onclick="switchBuilderTab('scanner')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Scanează site
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Tab navigation --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex border-b border-slate-200 overflow-x-auto" role="tablist">
            <button onclick="switchBuilderTab('agents')" id="btab-agents" role="tab" aria-selected="true" aria-controls="panel-agents" class="builder-tab-btn flex items-center gap-2 px-6 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap border-red-800 text-red-800">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Agenți AI
            </button>
            <button onclick="switchBuilderTab('upload')" id="btab-upload" role="tab" aria-selected="false" aria-controls="panel-upload" class="builder-tab-btn flex items-center gap-2 px-6 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Upload Fișiere
            </button>
            <button onclick="switchBuilderTab('scanner')" id="btab-scanner" role="tab" aria-selected="false" aria-controls="panel-scanner" class="builder-tab-btn flex items-center gap-2 px-6 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                Scanner Website
            </button>
            <button onclick="switchBuilderTab('connectors')" id="btab-connectors" role="tab" aria-selected="false" aria-controls="panel-connectors" class="builder-tab-btn flex items-center gap-2 px-6 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Conectori
            </button>
            <button onclick="switchBuilderTab('documents')" id="btab-documents" role="tab" aria-selected="false" aria-controls="panel-documents" class="builder-tab-btn flex items-center gap-2 px-6 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documente
                <span id="docs-count-badge" class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ $documents->count() }}</span>
            </button>
        </div>

        {{-- Tab content --}}
        <div id="panel-agents" class="builder-panel" role="tabpanel" aria-labelledby="btab-agents">
            @include('dashboard.bots.knowledge.partials.agents', ['bot' => $bot, 'agentsByCategory' => $agentsByCategory, 'lockedAgents' => $lockedAgents ?? collect(), 'recentRuns' => $recentRuns ?? collect(), 'plan' => $plan])
        </div>
        <div id="panel-upload" class="builder-panel hidden" role="tabpanel" aria-labelledby="btab-upload">
            @include('dashboard.bots.knowledge.partials.upload', ['bot' => $bot])
        </div>
        <div id="panel-scanner" class="builder-panel hidden" role="tabpanel" aria-labelledby="btab-scanner">
            @include('dashboard.bots.knowledge.partials.scanner', ['bot' => $bot, 'scans' => $scans, 'site' => $site ?? null])
        </div>
        <div id="panel-connectors" class="builder-panel hidden" role="tabpanel" aria-labelledby="btab-connectors">
            @include('dashboard.bots.knowledge.partials.connectors', ['bot' => $bot, 'connectors' => $connectors, 'site' => $site ?? null])
        </div>
        <div id="panel-documents" class="builder-panel hidden" role="tabpanel" aria-labelledby="btab-documents">
            @include('dashboard.bots.knowledge.partials.documents', ['bot' => $bot, 'documents' => $documents])
        </div>
    </div>
</div>

{{-- Agent Modal --}}
@include('dashboard.bots.knowledge.partials.agent-modal', ['bot' => $bot])

@endsection

@push('scripts')
<script>
    function switchBuilderTab(tab) {
        document.querySelectorAll('.builder-panel').forEach(function(p) { p.classList.add('hidden'); });
        document.querySelectorAll('.builder-tab-btn').forEach(function(t) {
            t.classList.remove('border-red-800', 'text-red-800');
            t.classList.add('border-transparent', 'text-slate-500');
            t.setAttribute('aria-selected', 'false');
        });
        var panel = document.getElementById('panel-' + tab);
        if (panel) panel.classList.remove('hidden');
        var btn = document.getElementById('btab-' + tab);
        if (btn) {
            btn.classList.remove('border-transparent', 'text-slate-500');
            btn.classList.add('border-red-800', 'text-red-800');
            btn.setAttribute('aria-selected', 'true');
        }
        // Update URL hash
        history.replaceState(null, null, '#' + tab);
    }

    // Restore tab from hash on page load
    (function() {
        var hash = window.location.hash.replace('#', '');
        var validTabs = ['agents', 'upload', 'scanner', 'connectors', 'documents'];
        if (hash && validTabs.indexOf(hash) !== -1) {
            switchBuilderTab(hash);
        }
    })();

    // Toast notification helper
    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('toast-container');
        var toast = document.createElement('div');
        var bgClass = type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-700' : 'bg-slate-700');
        toast.className = 'flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium ' + bgClass + ' transform translate-x-full transition-transform duration-300';
        var icon = '';
        if (type === 'success') {
            icon = '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        } else if (type === 'error') {
            icon = '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
        } else {
            icon = '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }
        toast.innerHTML = icon + '<span>' + message + '</span>';
        container.appendChild(toast);
        // Trigger animation
        requestAnimationFrame(function() {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        });
        // Auto-remove after 4s
        setTimeout(function() {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    }
</script>
@endpush
