{{-- Live activity widget — auto-refresh la 5s, pulses on change.
     Inclus pe /dashboard sus, sub welcome banner. --}}
<div x-data="liveActivity()" x-init="start()"
     class="card p-5 relative overflow-hidden">

    {{-- Subtle pulse glow when an event arrives --}}
    <div x-show="flash" x-transition:enter="transition-opacity duration-300"
         x-transition:leave="transition-opacity duration-1000 ease-out"
         class="absolute inset-0 bg-coral/5 pointer-events-none"></div>

    <div class="flex items-center justify-between mb-3 relative">
        <div class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>
            <h3 class="display text-base font-semibold text-ink">Live</h3>
            <span class="text-2xs text-muted mono" x-text="lastUpdated"></span>
        </div>
        <button @click="refresh()" class="text-2xs text-muted hover:text-coralh">↻ refresh</button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 relative">
        <template x-for="kpi in kpis" :key="kpi.key">
            <div :class="kpi.flash ? 'ring-2 ring-coral' : ''"
                 class="rounded-lg bg-cream/60 border border-line p-3 transition-all">
                <div class="text-2xs uppercase tracking-wider text-muted font-semibold" x-text="kpi.label"></div>
                <div class="display text-2xl font-semibold mt-1 mono"
                     :class="kpi.color || 'text-ink'"
                     x-text="kpi.value"></div>
            </div>
        </template>
    </div>

    <template x-if="latest">
        <div class="mt-4 pt-3 border-t border-line flex items-center gap-2 relative">
            <span x-text="latest.icon" class="text-base"></span>
            <span class="text-xs text-inkSoft truncate flex-1" x-text="latest.text"></span>
            <span class="text-2xs text-muted mono shrink-0" x-text="latestRelative"></span>
        </div>
    </template>

    <style>.pulse-dot { animation: pulse 1.6s ease-in-out infinite; } @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }</style>
</div>

<script>
function liveActivity() {
    return {
        kpis: [
            { key: 'active_conversations', label: 'Conv. active', value: '—', color: 'text-coralh' },
            { key: 'messages_last_hour',   label: 'Mesaje 1h', value: '—' },
            { key: 'calls_in_progress',    label: 'Apeluri live', value: '—', color: 'text-emerald-700' },
            { key: 'leads_today',          label: 'Leads azi', value: '—', color: 'text-amber-700' },
            { key: 'callbacks_today',      label: 'Programări', value: '—', color: 'text-[#5B21B6]' },
        ],
        latest: null,
        latestRelative: '',
        lastUpdated: 'se încarcă…',
        flash: false,
        prevValues: {},
        timer: null,

        start() {
            this.refresh();
            this.timer = setInterval(() => this.refresh(), 5000);
            // Update relative time without re-fetching
            setInterval(() => this.computeRelative(), 1000);
        },

        async refresh() {
            try {
                const r = await fetch('/dashboard/live-activity', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();

                let anyChange = false;
                for (const kpi of this.kpis) {
                    const newVal = d[kpi.key] ?? 0;
                    const oldVal = this.prevValues[kpi.key];
                    if (oldVal !== undefined && oldVal !== newVal) {
                        kpi.flash = true;
                        setTimeout(() => kpi.flash = false, 1500);
                        anyChange = true;
                    }
                    this.prevValues[kpi.key] = newVal;
                    kpi.value = String(newVal);
                }

                if (anyChange) {
                    this.flash = true;
                    setTimeout(() => this.flash = false, 1200);
                }

                this.latest = d.latest;
                this.computeRelative();
                const now = new Date();
                this.lastUpdated = '· ' + now.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } catch (e) {}
        },

        computeRelative() {
            if (!this.latest?.at) {
                this.latestRelative = '';
                return;
            }
            const now = Date.now();
            const then = new Date(this.latest.at).getTime();
            const sec = Math.max(0, Math.floor((now - then) / 1000));
            if (sec < 60) this.latestRelative = sec + 's';
            else if (sec < 3600) this.latestRelative = Math.floor(sec / 60) + 'm';
            else this.latestRelative = Math.floor(sec / 3600) + 'h';
        },
    };
}
</script>
