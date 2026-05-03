@extends('layouts.dashboard')

@section('title', 'Gap-uri KB — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Gap-uri KB</span>
@endsection

@section('content')
<div x-data="kbGaps()" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Gap-uri în baza de cunoștințe</h1>
            <p class="mt-2 text-sm text-muted">Întrebări reale de la utilizatori la care <strong>{{ $bot->name }}</strong> nu a știut să răspundă în ultimele 30 zile. Pentru fiecare poți cere AI-ului să-ți scrie un draft FAQ.</p>
        </div>
        <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}" class="text-sm px-4 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">Knowledge base →</a>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4">
            <div class="text-2xs uppercase tracking-wider text-muted font-semibold">Total search-uri 30z</div>
            <div class="display text-3xl font-semibold mt-2 mono text-ink">{{ number_format($totalSearches) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-2xs uppercase tracking-wider text-muted font-semibold">Fără rezultate</div>
            <div class="display text-3xl font-semibold mt-2 mono {{ $zeroPct > 30 ? 'text-coralh' : ($zeroPct > 15 ? 'text-amber-700' : 'text-emerald-700') }}">{{ number_format($zeroSearches) }}</div>
            <div class="text-2xs text-muted mt-1">{{ $zeroPct }}% din total</div>
        </div>
        <div class="card p-4">
            <div class="text-2xs uppercase tracking-wider text-muted font-semibold">Query-uri unice</div>
            <div class="display text-3xl font-semibold mt-2 mono text-inkSoft">{{ $gaps->count() }}</div>
            <div class="text-2xs text-muted mt-1">distincte, top 30</div>
        </div>
    </div>

    @if($gaps->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <h3 class="display text-base font-semibold text-ink mb-1">Niciun gap detectat</h3>
            <p class="text-sm text-muted">Toate search-urile au returnat rezultate. Excelent acoperire KB!</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-line bg-cream/40">
                <h2 class="display text-base font-semibold text-ink">Top query-uri fără răspuns</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-cream/60 text-2xs uppercase tracking-wider text-muted">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold">Query</th>
                        <th class="text-right px-4 py-2.5 font-semibold w-20">Apariții</th>
                        <th class="text-right px-4 py-2.5 font-semibold w-32">Ultima</th>
                        <th class="text-right px-4 py-2.5 font-semibold w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach($gaps as $gap)
                        <tr class="hover:bg-cream/30 transition">
                            <td class="px-4 py-2.5 text-sm text-ink">{{ $gap->query }}</td>
                            <td class="px-4 py-2.5 text-right mono text-xs font-semibold text-coralh">{{ $gap->occurrences }}</td>
                            <td class="px-4 py-2.5 text-right text-2xs text-muted mono">{{ \Carbon\Carbon::parse($gap->last_seen)->diffForHumans() }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <button type="button" @click="suggest({{ json_encode($gap->query) }})"
                                        class="text-2xs px-3 py-1 rounded-pill bg-coralsoft text-coralh hover:bg-coral hover:text-cream transition font-medium">
                                    ✨ AI draft
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Suggestion modal --}}
    <div x-show="showSuggestion" x-cloak @click.self="closeSuggestion()"
         class="fixed inset-0 z-50 bg-ink/40 backdrop-blur-sm flex items-start justify-center pt-[10vh] px-4">
        <div class="card max-w-2xl w-full overflow-hidden">
            <div class="px-5 py-3 border-b border-line bg-coralsoft/40 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-coral text-cream flex items-center justify-center text-xs">✨</div>
                    <h2 class="display text-base font-semibold text-ink">Draft FAQ generat de AI</h2>
                </div>
                <button @click="closeSuggestion()" class="text-muted hover:text-ink">✕</button>
            </div>

            <div class="p-5 space-y-4">
                <template x-if="loading">
                    <div class="text-center py-8 text-sm text-muted">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                            <span class="ml-2">AI lucrează la draft…</span>
                        </span>
                    </div>
                </template>

                <template x-if="error">
                    <div class="p-3 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="error"></div>
                </template>

                <template x-if="!loading && suggestion">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Întrebare</label>
                            <textarea x-model="suggestion.question" rows="2"
                                      class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Răspuns</label>
                            <textarea x-model="suggestion.answer" rows="6"
                                      class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none"></textarea>
                            <p class="text-2xs text-muted mt-1">💡 Înlocuiește placeholder-ele <code class="bg-cream px-1 rounded">{între acolade}</code> cu valori reale înainte de a salva.</p>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-line">
                            <button @click="copyToClipboard()" class="text-2xs px-3 py-1.5 rounded-pill border border-line hover:bg-cream">
                                <span x-show="!copied">📋 Copiază Q+A</span>
                                <span x-show="copied">✓ Copiat</span>
                            </button>
                            <a :href="'{{ route('dashboard.bots.edit', $bot) }}#tab-faq'" target="_blank"
                               class="btn-coral rounded-pill px-4 py-1.5 text-2xs font-medium">
                                Adaugă în editor →
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function kbGaps() {
    return {
        showSuggestion: false,
        suggestion: null,
        loading: false,
        error: null,
        copied: false,

        async suggest(query) {
            this.showSuggestion = true;
            this.suggestion = null;
            this.loading = true;
            this.error = null;
            this.copied = false;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/agenti/{{ $bot->id }}/knowledge-gaps/suggest', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ query }),
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || ('HTTP ' + r.status));
                this.suggestion = { question: d.question, answer: d.answer };
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        closeSuggestion() {
            this.showSuggestion = false;
            this.suggestion = null;
            this.error = null;
        },

        async copyToClipboard() {
            if (!this.suggestion) return;
            const text = `Q: ${this.suggestion.question}\nA: ${this.suggestion.answer}`;
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {}
        },
    };
}
</script>
@endsection
