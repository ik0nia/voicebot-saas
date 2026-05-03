@extends('layouts.dashboard')

@section('title', 'Mock customer — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Mock customer</span>
@endsection

@section('content')
<div x-data="mockCustomer()" class="space-y-6">

    <div>
        <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Mock customer simulator</h1>
        <p class="mt-2 text-sm text-muted">Un AI joacă rolul unui CLIENT REAL și vorbește 5-8 ture cu <strong>{{ $bot->name }}</strong>. La final primești raport de calitate cu scoruri și recomandări concrete pentru prompt.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Setup panel --}}
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-line bg-cream/40">
                <h2 class="display text-base font-semibold text-ink">Configurare simulare</h2>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Tipul de client (persona AI)</label>
                    <div class="space-y-2">
                        @foreach($personas as $p)
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition"
                                   :class="persona === '{{ $p['id'] }}' ? 'border-coral bg-coralsoft/40' : 'border-line hover:border-coral/40'">
                                <input type="radio" x-model="persona" value="{{ $p['id'] }}"
                                       class="mt-0.5 w-4 h-4 text-coralh focus:ring-coral/20">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-ink">{{ $p['name'] }}</div>
                                    <div class="text-2xs text-muted mt-0.5">{{ $p['desc'] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Număr de ture</label>
                    <input type="range" x-model="turns" min="3" max="8"
                           class="w-full accent-coral cursor-pointer">
                    <div class="flex justify-between text-2xs text-muted mt-1">
                        <span>scurt (3)</span>
                        <span class="font-semibold text-coralh mono" x-text="turns + ' ture'"></span>
                        <span>extins (8)</span>
                    </div>
                </div>

                <button @click="run()" :disabled="loading"
                        class="btn-coral w-full rounded-pill px-4 py-3 text-sm font-medium disabled:opacity-50">
                    <span x-show="!loading">▶ Pornește simularea</span>
                    <span x-show="loading">simulez (~20-40s)…</span>
                </button>

                <p class="text-2xs text-muted">Costul ~0.05 RON / simulare. Throttle 5/min/tenant.</p>
            </div>
        </div>

        {{-- Transcript --}}
        <div class="card overflow-hidden flex flex-col" style="height: 70vh;">
            <div class="px-4 py-3 border-b border-line bg-cream/40 flex items-center justify-between">
                <h2 class="display text-base font-semibold text-ink">Transcript</h2>
                <span x-show="transcript.length > 0" class="text-2xs text-muted mono" x-text="transcript.length + ' mesaje'"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <template x-if="!transcript.length && !loading">
                    <p class="text-sm text-muted text-center py-12">Pornește o simulare pentru a vedea conversația aici.</p>
                </template>
                <template x-if="loading && !transcript.length">
                    <div class="text-center py-12">
                        <div class="inline-flex items-center gap-1 text-sm text-muted">
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                            <span class="ml-2">simulare în curs…</span>
                        </div>
                    </div>
                </template>
                <template x-for="(m, i) in transcript" :key="i">
                    <div :class="m.role === 'customer' ? 'flex justify-end' : 'flex justify-start'">
                        <div class="max-w-[85%] px-3 py-2 rounded-2xl text-xs whitespace-pre-wrap"
                             :class="m.role === 'customer' ? 'bg-[#1E40AF] text-cream' : 'bg-cream text-ink'">
                            <div class="text-2xs opacity-60 font-semibold mb-0.5"
                                 x-text="m.role === 'customer' ? '👤 CLIENT (AI)' : '🤖 AGENT'"></div>
                            <div x-text="m.content"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Report --}}
    <template x-if="report">
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-line bg-gradient-to-r from-coralsoft to-cream/40">
                <h2 class="display text-base font-semibold text-ink">📊 Raport de calitate</h2>
            </div>
            <div class="p-5 space-y-5">
                {{-- Scores --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <template x-for="(label, key) in scoreLabels" :key="key">
                        <div class="text-center p-3 rounded-lg bg-cream/40 border border-line">
                            <div class="text-2xs uppercase tracking-wider text-muted font-semibold" x-text="label"></div>
                            <div class="display text-2xl font-semibold mt-1 mono"
                                 :class="scoreColor(report.scores[key])"
                                 x-text="report.scores[key]"></div>
                        </div>
                    </template>
                </div>

                <div class="text-center">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-pill text-sm font-semibold"
                          :class="{
                              'bg-emerald-100 text-emerald-700': report.verdict === 'good',
                              'bg-amber-100 text-amber-800': report.verdict === 'ok',
                              'bg-coralsoft text-coralh': report.verdict === 'needs_work',
                          }"
                          x-text="verdictLabel"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-emerald-700">✓</span>
                            <h3 class="text-sm font-semibold text-emerald-900">Ce a mers bine</h3>
                        </div>
                        <ul class="space-y-1 text-xs text-emerald-900">
                            <template x-for="w in report.wins" :key="w">
                                <li x-text="'• ' + w"></li>
                            </template>
                        </ul>
                    </div>

                    <div class="p-4 rounded-lg bg-coralsoft border border-coral/30">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-coralh">!</span>
                            <h3 class="text-sm font-semibold text-coralh">De îmbunătățit</h3>
                        </div>
                        <ul class="space-y-1 text-xs text-coralh">
                            <template x-for="i in report.issues" :key="i">
                                <li x-text="'• ' + i"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <template x-if="report.recommendation">
                    <div class="p-4 rounded-lg bg-coralsoft/40 border border-coral/20">
                        <div class="text-2xs uppercase tracking-wider text-coralh font-semibold mb-1">Recomandare pentru prompt</div>
                        <div class="text-sm text-ink" x-text="report.recommendation"></div>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-2xs px-3 py-1.5 rounded-pill border border-line hover:bg-cream">Editor prompt →</a>
                            <a href="{{ route('dashboard.personality-wizard.show', $bot) }}" class="text-2xs px-3 py-1.5 rounded-pill border border-line hover:bg-cream">Personalitate →</a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <template x-if="error">
        <div class="card p-4 border-coral/30 bg-coralsoft text-sm text-coralh" x-text="error"></div>
    </template>
</div>

<script>
function mockCustomer() {
    return {
        persona: 'programare',
        turns: 5,
        loading: false,
        transcript: [],
        report: null,
        error: null,
        channelId: {{ $webChannel->id }},

        scoreLabels: {
            goal: 'Atingere scop',
            natural: 'Naturalețe',
            efficient: 'Eficiență',
            information: 'Informație',
            overall: 'Overall',
        },

        get verdictLabel() {
            return {
                good: '✓ Agent OK — face față bine acestui tip de client',
                ok: '○ Agent OK pe alocuri — vezi recomandările',
                needs_work: '! Agent nu face față — necesită îmbunătățiri',
            }[this.report?.verdict] || '?';
        },

        scoreColor(s) {
            if (s >= 80) return 'text-emerald-700';
            if (s >= 60) return 'text-amber-700';
            return 'text-coralh';
        },

        async run() {
            this.loading = true;
            this.transcript = [];
            this.report = null;
            this.error = null;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/agenti/{{ $bot->id }}/mock-customer/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ persona: this.persona, turns: this.turns, channel_id: this.channelId }),
                });
                const d = await r.json();
                if (!r.ok) {
                    this.transcript = d.partial_transcript || [];
                    throw new Error(d.error || ('HTTP ' + r.status));
                }
                this.transcript = d.transcript || [];
                this.report = d.report || null;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
