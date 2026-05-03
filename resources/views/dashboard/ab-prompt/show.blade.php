@extends('layouts.dashboard')

@section('title', 'A/B prompt — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">A/B prompt</span>
@endsection

@section('content')
<div x-data="abPrompt()" class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">A/B prompt</h1>
            <p class="mt-2 text-sm text-muted">Testează 2 variante de prompt pe același input. Răspunsurile vin direct de la model, fără RAG/tool-uri — pur ca să compari prompting.</p>
        </div>
        <a href="{{ route('dashboard.playground.show', $bot) }}" class="text-sm px-4 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">
            ← Playground complet
        </a>
    </div>

    {{-- Shared input row --}}
    <div class="card p-4">
        <form @submit.prevent="send()" class="flex gap-2">
            <input type="text" x-model="input" :disabled="loading"
                   placeholder="Tastează un mesaj — vei vedea ambele răspunsuri…"
                   class="flex-1 rounded-pill border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            <button type="submit" :disabled="loading || !input.trim() || !promptA.trim() || !promptB.trim()"
                    class="btn-coral rounded-pill px-5 py-2.5 text-sm font-medium disabled:opacity-50">
                <span x-show="!loading">Trimite la ambele →</span>
                <span x-show="loading">Generez…</span>
            </button>
            <button type="button" @click="reset()" class="rounded-pill border border-line bg-white hover:bg-cream px-4 py-2.5 text-sm">↻</button>
        </form>
    </div>

    {{-- Two columns A/B --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Column A --}}
        <div class="space-y-3">
            <div class="card overflow-hidden">
                <div class="px-4 py-2.5 bg-coralsoft text-coralh flex items-center justify-between border-b border-coral/20">
                    <div class="flex items-center gap-2">
                        <span class="display text-base font-semibold">A</span>
                        <span class="text-2xs">prompt original</span>
                    </div>
                    <span class="text-2xs mono" x-text="statsA"></span>
                </div>
                <textarea x-model="promptA" rows="6"
                          placeholder="Ești asistentul..."
                          class="w-full px-3 py-2 text-2xs font-mono text-inkSoft bg-paper resize-y focus:outline-none focus:ring-1 focus:ring-coral border-0"></textarea>
            </div>

            <div class="card overflow-hidden flex flex-col" style="height: 50vh;">
                <div class="px-4 py-2 border-b border-line text-2xs uppercase tracking-wider text-muted bg-cream/40">
                    Conversație A
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-2" x-ref="messagesA">
                    <template x-if="messagesA.length === 0">
                        <p class="text-2xs text-muted text-center py-12">Fără mesaje încă.</p>
                    </template>
                    <template x-for="(m, i) in messagesA" :key="i">
                        <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="m.role === 'user' ? 'bg-coral text-cream' : 'bg-cream text-ink'"
                                 class="max-w-[85%] px-3 py-2 rounded-2xl text-xs whitespace-pre-wrap" x-text="m.content"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Column B --}}
        <div class="space-y-3">
            <div class="card overflow-hidden">
                <div class="px-4 py-2.5 bg-[#DCEBFA] text-[#1E40AF] flex items-center justify-between border-b border-[#1E40AF]/10">
                    <div class="flex items-center gap-2">
                        <span class="display text-base font-semibold">B</span>
                        <span class="text-2xs">variantă de testat</span>
                    </div>
                    <span class="text-2xs mono" x-text="statsB"></span>
                </div>
                <textarea x-model="promptB" rows="6"
                          placeholder="Modifică prompt-ul aici..."
                          class="w-full px-3 py-2 text-2xs font-mono text-inkSoft bg-paper resize-y focus:outline-none focus:ring-1 focus:ring-[#1E40AF] border-0"></textarea>
            </div>

            <div class="card overflow-hidden flex flex-col" style="height: 50vh;">
                <div class="px-4 py-2 border-b border-line text-2xs uppercase tracking-wider text-muted bg-cream/40">
                    Conversație B
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-2" x-ref="messagesB">
                    <template x-if="messagesB.length === 0">
                        <p class="text-2xs text-muted text-center py-12">Fără mesaje încă.</p>
                    </template>
                    <template x-for="(m, i) in messagesB" :key="i">
                        <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="m.role === 'user' ? 'bg-[#1E40AF] text-cream' : 'bg-cream text-ink'"
                                 class="max-w-[85%] px-3 py-2 rounded-2xl text-xs whitespace-pre-wrap" x-text="m.content"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Tip --}}
    <div class="card p-4 bg-cream/40 border-dashed">
        <div class="flex items-start gap-3">
            <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-2xs text-inkSoft">
                <strong class="text-ink">Ce surprinde A/B:</strong> doar diferențele de prompt (gpt-4o-mini, T=0.7, max 500 tokens). NU include RAG, tool-uri sau istoric persistent. Pentru test complet folosește <a href="{{ route('dashboard.playground.show', $bot) }}" class="text-coralh hover:underline">Playground</a>. Limit 30 comparări/min/tenant.
            </div>
        </div>
    </div>
</div>

<script>
function abPrompt() {
    return {
        promptA: @json($currentPrompt),
        promptB: @json($currentPrompt),
        input: '',
        messagesA: [],
        messagesB: [],
        loading: false,
        statsA: '',
        statsB: '',

        async send() {
            const msg = this.input.trim();
            if (!msg || this.loading) return;
            this.loading = true;

            // Add user message to both columns
            this.messagesA.push({ role: 'user', content: msg });
            this.messagesB.push({ role: 'user', content: msg });
            this.input = '';

            // Build history (excluding the just-added user message — server appends it)
            const histA = this.messagesA.slice(0, -1);
            const histB = this.messagesB.slice(0, -1);

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/agenti/{{ $bot->id }}/ab-prompt/compare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        prompt_a: this.promptA,
                        prompt_b: this.promptB,
                        message: msg,
                        history_a: histA,
                        history_b: histB,
                    }),
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || ('HTTP ' + r.status));

                this.messagesA.push({ role: 'assistant', content: d.a.content });
                this.messagesB.push({ role: 'assistant', content: d.b.content });
                this.statsA = d.a.tokens_in + ' in / ' + d.a.tokens_out + ' out';
                this.statsB = d.b.tokens_in + ' in / ' + d.b.tokens_out + ' out';
            } catch (e) {
                this.messagesA.push({ role: 'assistant', content: '⚠ ' + e.message });
                this.messagesB.push({ role: 'assistant', content: '⚠ ' + e.message });
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    if (this.$refs.messagesA) this.$refs.messagesA.scrollTop = this.$refs.messagesA.scrollHeight;
                    if (this.$refs.messagesB) this.$refs.messagesB.scrollTop = this.$refs.messagesB.scrollHeight;
                });
            }
        },

        reset() {
            this.messagesA = [];
            this.messagesB = [];
            this.statsA = '';
            this.statsB = '';
        },
    };
}
</script>
@endsection
