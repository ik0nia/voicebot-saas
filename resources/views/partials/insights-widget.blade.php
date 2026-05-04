{{-- AI Insights widget — apare pe /dashboard sub Live activity.
     Cere POST /dashboard/insights → cache 30min, regenerare la click „↻". --}}
<div x-data="aiInsights()" x-init="loadIfFresh()" class="card overflow-hidden">

    <div class="px-5 py-3 border-b border-line bg-gradient-to-r from-coralsoft to-cream/40 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-coral text-cream flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <h3 class="display text-base font-semibold text-ink">AI Insights</h3>
            <span x-show="cached && !loading" class="text-2xs text-muted mono" x-text="'· ' + relativeTime"></span>
        </div>
        <button @click="generate(true)" :disabled="loading"
                class="text-2xs text-coralh hover:underline disabled:opacity-50">
            <span x-show="!loading">↻ regenerează</span>
            <span x-show="loading">analizez…</span>
        </button>
    </div>

    <div class="p-5">
        <template x-if="!loaded && !loading">
            <div class="text-center py-8">
                <p class="text-sm text-muted mb-4">Lasă AI-ul să-ți spună ce s-a întâmplat în ultima săptămână + ce ai de făcut.</p>
                <button @click="generate(false)"
                        class="btn-coral inline-flex items-center gap-2 rounded-pill px-5 py-2.5 text-sm font-medium">
                    ✨ Generează insights
                </button>
            </div>
        </template>

        <template x-if="loading">
            <div class="text-center py-8">
                <div class="inline-flex items-center gap-1 text-sm text-muted">
                    <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                    <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                    <span class="ml-2">analizez ultimele 7 zile…</span>
                </div>
            </div>
        </template>

        <template x-if="error">
            <div class="p-3 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="error"></div>
        </template>

        <ul x-show="insights.length > 0 && !loading" class="space-y-3">
            <template x-for="(ins, i) in insights" :key="i">
                <li class="flex items-start gap-3 p-3 rounded-lg border"
                    :class="{
                        'bg-emerald-50 border-emerald-200': ins.severity === 'good',
                        'bg-amber-50 border-amber-200': ins.severity === 'warn',
                        'bg-cream border-line': ins.severity === 'info',
                    }">
                    <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-base"
                         :class="{
                             'bg-emerald-100 text-emerald-700': ins.severity === 'good',
                             'bg-amber-100 text-amber-800': ins.severity === 'warn',
                             'bg-paper text-inkSoft': ins.severity === 'info',
                         }">
                        <span x-text="ins.severity === 'good' ? '✓' : (ins.severity === 'warn' ? '!' : 'i')"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-ink" x-text="ins.title"></div>
                        <div class="text-xs text-inkSoft mt-0.5" x-text="ins.detail"></div>
                        <div x-show="ins.action" class="text-2xs text-coralh font-medium mt-2" x-text="'→ ' + ins.action"></div>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</div>

<script>
function aiInsights() {
    return {
        insights: [],
        loaded: false,
        loading: false,
        cached: false,
        generatedAt: null,
        relativeTime: '',
        error: null,

        async loadIfFresh() {
            // Auto-load DOAR din cache prin GET — nu burnem OpenAI.
            // Dacă e cache (6h), arătăm direct insights-urile; altfel CTA „Generează".
            try {
                const r = await fetch('/dashboard/insights', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                if (d.cached && Array.isArray(d.insights) && d.insights.length > 0) {
                    this.insights = d.insights;
                    this.cached = true;
                    this.generatedAt = d.generated_at;
                    this.computeRelative();
                    this.loaded = true;
                    setInterval(() => this.computeRelative(), 30000);
                }
            } catch (e) {}
        },

        async generate(force) {
            this.loading = true;
            this.error = null;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/insights' + (force ? '?force=1' : ''), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || ('HTTP ' + r.status));
                this.insights = d.insights || [];
                this.cached = !!d.cached;
                this.generatedAt = d.generated_at;
                this.computeRelative();
                this.loaded = true;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        computeRelative() {
            if (!this.generatedAt) return;
            const sec = Math.floor((Date.now() - new Date(this.generatedAt).getTime()) / 1000);
            if (sec < 60) this.relativeTime = 'acum ' + sec + 's';
            else if (sec < 3600) this.relativeTime = 'acum ' + Math.floor(sec / 60) + ' min';
            else this.relativeTime = 'acum ' + Math.floor(sec / 3600) + 'h';
        },
    };
}
</script>
