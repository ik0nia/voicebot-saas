{{-- Conversion funnel widget — randat pe /dashboard/analiza --}}
<div x-data="conversionFunnel()" x-init="load()" class="card overflow-hidden">

    <div class="px-5 py-3 border-b border-line bg-cream/40 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 4h18M5 8h14M7 12h10M9 16h6M11 20h2"/></svg>
            </div>
            <h3 class="display text-base font-semibold text-ink">Funnel conversie (30 zile)</h3>
            <span x-show="data" class="text-2xs text-coralh font-mono mono px-2 py-0.5 rounded bg-coralsoft" x-text="'overall ' + (data?.overall_conversion_pct || 0) + '%'"></span>
        </div>
        <button @click="load(true)" class="text-2xs text-muted hover:text-coralh">↻</button>
    </div>

    <div class="p-5">
        <template x-if="loading">
            <p class="text-sm text-muted text-center py-6">se calculează…</p>
        </template>
        <template x-if="data">
            <div class="space-y-2">
                <template x-for="(stage, idx) in data.stages" :key="stage.key">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="text-2xs text-muted font-mono w-6 text-right" x-text="(idx + 1) + '.'"></div>
                            <div class="flex-1 min-w-0 relative">
                                <div class="h-10 rounded-lg overflow-hidden bg-cream relative">
                                    <div class="h-full transition-all duration-700 flex items-center px-3"
                                         :style="{ width: barWidth(idx) + '%', backgroundColor: barColor(idx) }">
                                        <span class="display text-sm font-semibold text-cream whitespace-nowrap mono" x-text="stage.count.toLocaleString('ro-RO')"></span>
                                    </div>
                                    <div class="absolute inset-y-0 right-3 flex items-center">
                                        <span class="text-xs font-medium text-inkSoft" x-text="stage.label"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="w-16 text-right">
                                <div class="text-2xs text-muted mono" x-text="idx === 0 ? '' : '↓ ' + stage.pct + '%'"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="pt-4 border-t border-line text-2xs text-muted">
                    <strong>Conversie totală</strong> visitor → won/completed:
                    <span class="text-coralh font-semibold mono" x-text="data.overall_conversion_pct + '%'"></span>
                    · benchmark industrie chat support: 2-8%
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function conversionFunnel() {
    return {
        data: null,
        loading: true,

        async load(force) {
            this.loading = true;
            try {
                const r = await fetch('/dashboard/funnel' + (force ? '?_=' + Date.now() : ''));
                if (!r.ok) throw new Error('HTTP ' + r.status);
                this.data = await r.json();
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        barWidth(idx) {
            // Logarithmic-ish width: stage count vs visitors (stage 0)
            if (!this.data) return 0;
            const visitors = Math.max(1, this.data.stages[0].count);
            const cnt = this.data.stages[idx].count;
            const pct = (cnt / visitors) * 100;
            return Math.max(8, pct); // minimum 8% width pentru lizibilitate
        },

        barColor(idx) {
            const colors = ['#1E40AF', '#5B21B6', '#9A3412', '#DC2626', '#991B1B', '#047857'];
            return colors[idx] || '#1C1917';
        },
    };
}
</script>
