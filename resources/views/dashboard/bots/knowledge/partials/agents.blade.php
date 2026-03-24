<div class="p-6">
    {{-- Search bar --}}
    <div class="mb-6">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" id="agent-search" placeholder="Caută agenți..." oninput="filterAgents(this.value)"
                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
        </div>
    </div>

    {{-- Category navigation --}}
    <div class="flex flex-wrap items-center gap-2 mb-6 pb-4 border-b border-slate-200">
        <button onclick="filterAgentCategory('all', this)" class="agent-cat-btn px-3 py-1.5 text-xs font-medium rounded-full bg-red-800 text-white transition-colors">
            Toate
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs bg-white/20">{{ collect($agentsByCategory)->flatten(1)->count() }}</span>
        </button>
        @foreach($agentsByCategory as $category => $categoryAgents)
            <button onclick="filterAgentCategory('{{ Str::slug($category) }}', this)" class="agent-cat-btn px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                {{ $category }}
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-xs bg-slate-200/60 text-slate-500">{{ count($categoryAgents) }}</span>
            </button>
        @endforeach
    </div>

    {{-- No results message --}}
    <div id="agents-no-results" class="hidden text-center py-8">
        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <p class="text-sm text-slate-500">Niciun agent găsit pentru căutarea ta.</p>
    </div>

    @php
        $recentRunSlugs = isset($recentRuns) ? (is_array($recentRuns) ? $recentRuns : $recentRuns->pluck('agent_slug')->toArray()) : [];
        $lockedSlugs = isset($lockedAgents) ? (is_array($lockedAgents) ? $lockedAgents : $lockedAgents->pluck('slug')->toArray()) : [];
    @endphp

    @foreach($agentsByCategory as $category => $categoryAgents)
        <div class="agent-category-group mb-8 last:mb-0" data-category="{{ Str::slug($category) }}">
            <div class="flex items-center gap-3 mb-4 pb-2 border-b border-slate-100">
                <div class="w-1 h-5 bg-red-700 rounded-full"></div>
                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">{{ $category }}</h3>
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">{{ count($categoryAgents) }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($categoryAgents as $agent)
                    @php
                        $isLocked = in_array($agent['slug'], $lockedSlugs);
                        $wasUsed = in_array($agent['slug'], $recentRunSlugs);
                    @endphp
                    <div class="agent-card border border-slate-200 rounded-lg p-4 transition-all {{ $isLocked ? 'opacity-60 cursor-not-allowed' : 'hover:border-red-300 hover:shadow-sm cursor-pointer group' }}"
                         data-agent-name="{{ strtolower($agent['name']) }}"
                         data-agent-desc="{{ strtolower($agent['description']) }}"
                         @if(!$isLocked)
                         onclick="openAgentModal('{{ $agent['slug'] }}', '{{ addslashes($agent['name']) }}', '{{ addslashes($agent['description']) }}', '{{ addslashes($agent['role']) }}')"
                         @endif
                         @if($isLocked)
                         title="Disponibil pe Pro"
                         @endif>
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-lg {{ $isLocked ? 'bg-slate-100 text-slate-400' : 'bg-red-50 text-red-700 group-hover:bg-red-100' }} flex items-center justify-center transition-colors">
                                @switch($agent['icon'])
                                    @case('box')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        @break
                                    @case('help-circle')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
                                        @break
                                    @case('map')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                        @break
                                    @case('file-text')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @break
                                    @case('trending-up')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                        @break
                                    @case('code')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                        @break
                                    @case('briefcase')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
                                        @break
                                    @case('globe')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                                        @break
                                    @case('mic')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2m7 9v3m-4 0h8"/></svg>
                                        @break
                                    @case('search')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                                        @break
                                    @case('check-circle')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @break
                                    @case('eye')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        @break
                                    @case('users')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg>
                                        @break
                                    @case('bar-chart-2')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                        @break
                                    @case('user')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        @break
                                    @case('layers')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                        @break
                                    @case('message-square')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                        @break
                                    @case('arrow-up-circle')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M16 12l-4-4-4 4m4-4v8"/></svg>
                                        @break
                                    @case('smile')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2m-9-3h.01M15 11h.01"/></svg>
                                        @break
                                    @case('shield')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                @endswitch
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $agent['name'] }}</h4>
                                    @if($isLocked)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-500" title="Disponibil pe Pro">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                            Pro
                                        </span>
                                    @endif
                                    @if($wasUsed)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Folosit
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $agent['description'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    function filterAgents(query) {
        query = query.toLowerCase().trim();
        var cards = document.querySelectorAll('.agent-card');
        var groups = document.querySelectorAll('.agent-category-group');
        var anyVisible = false;

        cards.forEach(function(card) {
            var name = card.getAttribute('data-agent-name') || '';
            var desc = card.getAttribute('data-agent-desc') || '';
            var match = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
            card.classList.toggle('hidden', !match);
            if (match) anyVisible = true;
        });

        // Hide empty category groups
        groups.forEach(function(group) {
            var visibleCards = group.querySelectorAll('.agent-card:not(.hidden)');
            group.classList.toggle('hidden', visibleCards.length === 0);
        });

        var noResults = document.getElementById('agents-no-results');
        if (noResults) {
            noResults.classList.toggle('hidden', anyVisible);
        }
    }

    function filterAgentCategory(catSlug, btn) {
        // Update active button
        document.querySelectorAll('.agent-cat-btn').forEach(function(b) {
            b.classList.remove('bg-red-800', 'text-white');
            b.classList.add('bg-slate-100', 'text-slate-600');
        });
        btn.classList.remove('bg-slate-100', 'text-slate-600');
        btn.classList.add('bg-red-800', 'text-white');

        var groups = document.querySelectorAll('.agent-category-group');
        if (catSlug === 'all') {
            groups.forEach(function(g) { g.classList.remove('hidden'); });
            // Re-show all cards (reset search too)
            document.querySelectorAll('.agent-card').forEach(function(c) { c.classList.remove('hidden'); });
        } else {
            groups.forEach(function(g) {
                g.classList.toggle('hidden', g.getAttribute('data-category') !== catSlug);
            });
        }

        // Clear search
        var searchInput = document.getElementById('agent-search');
        if (searchInput) searchInput.value = '';

        document.getElementById('agents-no-results').classList.add('hidden');
    }
</script>
@endpush
