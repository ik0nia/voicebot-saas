{{-- Cost forecast widget — proiecție end-of-month pe baza last 7d
     Afișat pe /dashboard sub AI Insights. --}}
<div x-data="costForecast()" x-init="load()" class="card overflow-hidden">

    <div class="px-5 py-3 border-b border-line bg-gradient-to-r from-[#FCEEC8] to-cream/40 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-amber-700 text-cream flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="display text-base font-semibold text-ink">Cost lunar — proiecție</h3>
            <span x-show="data" class="text-2xs text-muted mono" x-text="'· zi ' + (data?.days_so_far || 0) + '/' + (data?.days_in_month || 30)"></span>
        </div>
        <button @click="load(true)" class="text-2xs text-coralh hover:underline">↻</button>
    </div>

    <div class="p-5">
        <template x-if="loading">
            <div class="text-center py-6 text-sm text-muted">se calculează…</div>
        </template>
        <template x-if="error">
            <div class="text-center py-6 text-2xs text-coralh" x-text="error"></div>
        </template>

        <template x-if="data && !loading">
            <div class="space-y-4">
                {{-- Big projected number with progress bar --}}
                <div>
                    <div class="flex items-end justify-between mb-2">
                        <div>
                            <div class="text-2xs uppercase tracking-wider text-muted font-semibold">Proiecție end-of-month</div>
                            <div class="display text-3xl font-semibold mono text-ink mt-1" x-text="format(data.projected_month_ron) + ' RON'"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xs uppercase tracking-wider text-muted font-semibold">Cheltuit până acum</div>
                            <div class="display text-xl font-semibold mono text-emerald-700" x-text="format(data.spent_month_ron) + ' RON'"></div>
                        </div>
                    </div>
                    <div class="h-2 bg-cream rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 transition-all"
                             :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <div class="flex items-center justify-between mt-2 text-2xs text-muted">
                        <span x-text="progressPercent + '% din proiecție'"></span>
                        <span x-text="format(data.daily_rate_ron) + ' RON/zi · ratei last 7z'"></span>
                    </div>
                </div>

                {{-- Breakdown --}}
                <details class="group pt-3 border-t border-line">
                    <summary class="text-2xs text-coralh cursor-pointer hover:underline list-none">vezi defalcare pe componente</summary>
                    <ul class="mt-3 space-y-1.5 text-xs">
                        <li class="flex justify-between">
                            <span class="text-inkSoft">AI (LLM, embeddings)</span>
                            <span class="mono font-semibold" x-text="format(data.breakdown.ai_api_month_ron) + ' RON'"></span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-inkSoft">Voce — Twilio</span>
                            <span class="mono font-semibold" x-text="format(data.breakdown.calls_twilio_month_ron) + ' RON'"></span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-inkSoft">Voce — OpenAI Realtime</span>
                            <span class="mono font-semibold" x-text="format(data.breakdown.calls_openai_month_ron) + ' RON'"></span>
                        </li>
                    </ul>
                    <div class="mt-2 text-2xs text-muted">USD → RON: <span class="mono" x-text="data.usd_to_ron_rate"></span> (BNR)</div>
                </details>
            </div>
        </template>
    </div>
</div>

<script>
function costForecast() {
    return {
        data: null,
        loading: true,
        error: null,

        async load(force) {
            this.loading = true;
            this.error = null;
            try {
                const r = await fetch('/dashboard/cost-forecast' + (force ? '?_=' + Date.now() : ''));
                if (!r.ok) throw new Error('HTTP ' + r.status);
                this.data = await r.json();
            } catch (e) {
                this.error = 'Nu am putut calcula proiecția: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        format(n) {
            return Number(n || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        get progressPercent() {
            if (!this.data) return 0;
            const p = this.data.projected_month_ron > 0
                ? (this.data.spent_month_ron / this.data.projected_month_ron) * 100
                : 0;
            return Math.round(Math.min(100, p));
        },
    };
}
</script>
