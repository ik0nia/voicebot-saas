@extends('layouts.dashboard')

@section('title', 'Replay conv #' . $conversation->id)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.conversations.show', $conversation) }}" class="text-muted hover:text-inkSoft">Conv #{{ $conversation->id }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Replay</span>
@endsection

@section('content')
<div x-data="replay({{ \Illuminate\Support\Js::from($timeline) }})" class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">⏯ Replay conversație</h1>
        <p class="mt-2 text-sm text-muted">{{ $conversation->bot?->name ?? '—' }} cu <strong>{{ $conversation->contact_name ?: $conversation->contact_identifier ?: 'Vizitator' }}</strong> · {{ count($timeline) }} mesaje · durată reală {{ $totalDurationSec }}s</p>
    </div>

    {{-- Player controls --}}
    <div class="card p-4 sticky top-4 z-10 backdrop-blur bg-paper/85">
        <div class="flex items-center gap-3">
            <button @click="playing ? pause() : play()" class="btn-coral rounded-pill px-4 py-2 text-sm font-medium">
                <span x-show="!playing">▶ Play</span>
                <span x-show="playing">⏸ Pause</span>
            </button>
            <button @click="restart()" class="text-sm px-3 py-2 rounded-pill border border-line hover:bg-cream">↻</button>
            <div class="flex items-center gap-1">
                <template x-for="s in speeds" :key="s">
                    <button @click="speed = s" :class="speed === s ? 'bg-coral text-cream' : 'bg-cream text-inkSoft hover:bg-sand'"
                            class="text-xs px-2.5 py-1 rounded-pill font-mono transition" x-text="s + 'x'"></button>
                </template>
            </div>
            <div class="flex-1 text-right text-2xs text-muted mono">
                <span x-text="formatTime(currentMs)"></span> / <span x-text="formatTime(totalMs)"></span>
            </div>
        </div>
        <div class="h-1 bg-cream rounded-full mt-3 overflow-hidden cursor-pointer" @click="seekClick($event)">
            <div class="h-full bg-coral transition-all" :style="{ width: progressPct + '%' }"></div>
        </div>
    </div>

    {{-- Messages container — adăugate dinamic în timpul playback-ului --}}
    <div class="card p-5 min-h-96 space-y-3" x-ref="msgContainer">
        <template x-if="visibleMessages.length === 0 && !playing">
            <p class="text-sm text-muted text-center py-12">Apasă „Play" pentru a urmări replay-ul mesaj cu mesaj.</p>
        </template>
        <template x-for="(m, i) in visibleMessages" :key="m.id">
            <div :class="m.role === 'user' || m.role === 'customer' ? 'flex justify-end' : 'flex justify-start'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="max-w-[80%] px-3.5 py-2.5 rounded-2xl text-sm whitespace-pre-wrap"
                     :class="m.role === 'user' || m.role === 'customer' ? 'bg-coral text-cream' : 'bg-cream text-ink'">
                    <div class="text-2xs opacity-60 mb-0.5 mono" x-text="formatTime(m.delta_ms)"></div>
                    <div x-text="m.content"></div>
                </div>
            </div>
        </template>
    </div>

    <div class="flex justify-center">
        <a href="{{ route('dashboard.conversations.show', $conversation) }}" class="text-sm text-muted hover:text-inkSoft">← Vezi transcript static</a>
    </div>
</div>

<script>
function replay(timeline) {
    return {
        timeline: timeline || [],
        playing: false,
        currentMs: 0,
        startedAt: null,
        speed: 4,
        speeds: [1, 2, 4, 8, 16],
        timer: null,

        get totalMs() {
            return this.timeline.length > 0 ? this.timeline[this.timeline.length - 1].delta_ms : 0;
        },

        get progressPct() {
            if (!this.totalMs) return 0;
            return Math.min(100, (this.currentMs / this.totalMs) * 100);
        },

        get visibleMessages() {
            return this.timeline.filter(m => m.delta_ms <= this.currentMs);
        },

        play() {
            if (this.currentMs >= this.totalMs) this.currentMs = 0;
            this.playing = true;
            this.startedAt = Date.now() - (this.currentMs / this.speed);
            this.tick();
        },

        pause() {
            this.playing = false;
            if (this.timer) clearTimeout(this.timer);
        },

        restart() {
            this.pause();
            this.currentMs = 0;
            setTimeout(() => this.play(), 100);
        },

        tick() {
            if (!this.playing) return;
            const elapsedReal = Date.now() - this.startedAt;
            this.currentMs = Math.min(this.totalMs, elapsedReal * this.speed);
            if (this.currentMs >= this.totalMs) {
                this.playing = false;
                return;
            }
            // Auto-scroll
            this.$nextTick(() => {
                const c = this.$refs.msgContainer;
                if (c) c.scrollTop = c.scrollHeight;
            });
            this.timer = setTimeout(() => this.tick(), 50);
        },

        seekClick(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const ratio = (event.clientX - rect.left) / rect.width;
            this.currentMs = Math.max(0, Math.min(this.totalMs, ratio * this.totalMs));
            if (this.playing) this.startedAt = Date.now() - (this.currentMs / this.speed);
        },

        formatTime(ms) {
            const sec = Math.floor(ms / 1000);
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            return m + ':' + (s < 10 ? '0' : '') + s;
        },
    };
}
</script>
@endsection
