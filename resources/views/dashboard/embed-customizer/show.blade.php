@extends('layouts.dashboard')

@section('title', 'Customizer widget — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Embed customizer</span>
@endsection

@section('content')
<div x-data="embedCustomizer()" class="space-y-6">

    <div>
        <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Embed customizer</h1>
        <p class="mt-2 text-sm text-muted">Configurează widget-ul pentru <strong>{{ $bot->name }}</strong> și vezi schimbările LIVE în preview-ul din dreapta. Salvarea aplică pe toate site-urile cu acest widget.</p>
    </div>

    @if(session('success'))
        <div class="card p-4 border-emerald-200 bg-emerald-50 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('dashboard.embed-customizer.update', $bot) }}" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @csrf

        {{-- LEFT: settings panel --}}
        <div class="card p-5 space-y-5">
            <h2 class="display text-base font-semibold text-ink">Personalizare</h2>

            <div>
                <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Culoare brand</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" x-model="color" @input="bumpKey()"
                           class="w-14 h-12 rounded-lg border border-line cursor-pointer">
                    <input type="text" x-model="color" @input="bumpKey()" maxlength="7"
                           class="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm font-mono focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                </div>
                <div class="flex items-center gap-1 mt-2">
                    <template x-for="preset in colorPresets" :key="preset">
                        <button type="button" @click="color = preset; bumpKey()"
                                :title="preset"
                                :style="{ backgroundColor: preset }"
                                class="w-6 h-6 rounded-md border border-line hover:scale-110 transition"></button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Mesaj salut</label>
                <textarea name="greeting" x-model="greeting" @input.debounce.500ms="bumpKey()" rows="3" maxlength="300"
                          class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                <div class="text-2xs text-muted mt-1 mono" x-text="greeting.length + ' / 300'"></div>
            </div>

            <div>
                <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Poziție pe pagină</label>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="pos in positions" :key="pos.value">
                        <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition"
                               :class="position === pos.value ? 'border-coral bg-coralsoft' : 'border-line hover:border-coral/40'">
                            <input type="radio" name="position" :value="pos.value" x-model="position" @change="bumpKey()"
                                   class="w-4 h-4 text-coralh focus:ring-coral/20">
                            <div class="flex-1">
                                <div class="text-xs font-medium text-ink" x-text="pos.label"></div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Limbă UI widget</label>
                <select name="lang" x-model="lang" @change="bumpKey()"
                        class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                    <option value="ro">Română 🇷🇴</option>
                    <option value="en">English 🇬🇧</option>
                    <option value="de">Deutsch 🇩🇪</option>
                    <option value="fr">Français 🇫🇷</option>
                    <option value="es">Español 🇪🇸</option>
                </select>
            </div>

            <div class="pt-4 border-t border-line">
                <p class="text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Snippet de instalare</p>
                <pre class="text-2xs font-mono text-inkSoft bg-cream p-2 rounded-lg overflow-x-auto whitespace-pre-wrap break-all" x-text="snippet"></pre>
                <button type="button" @click="copySnippet()" class="mt-2 text-2xs text-coralh hover:underline">📋 copiază snippet</button>
            </div>

            <div class="pt-4 border-t border-line flex items-center justify-end">
                <button type="submit" class="btn-coral rounded-pill px-5 py-2.5 text-sm font-medium">
                    Salvează configurația →
                </button>
            </div>
        </div>

        {{-- RIGHT: live preview --}}
        <div class="card overflow-hidden flex flex-col" style="min-height: 70vh;">
            <div class="px-4 py-3 border-b border-line bg-cream/40 flex items-center justify-between">
                <h2 class="display text-base font-semibold text-ink">📱 Preview live</h2>
                <span class="text-2xs text-muted mono">se actualizează auto</span>
            </div>
            <div class="flex-1 bg-[#1C1917] relative">
                <iframe :src="previewUrl" :key="previewKey"
                        class="w-full h-full border-0"
                        style="background: #fff;"
                        sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
                <div class="absolute top-2 left-2 px-2 py-0.5 rounded bg-ink/70 text-cream text-2xs font-mono pointer-events-none">live preview</div>
            </div>
        </div>
    </form>

    <div class="card p-4 bg-cream/40 border-dashed text-2xs text-inkSoft">
        💡 După salvare, widget-ul de pe site-urile clienților se actualizează automat la următoarea încărcare a paginii (cache 60s pe widget config). Nu trebuie să schimbi codul de pe site.
    </div>
</div>

<script>
function embedCustomizer() {
    return {
        color: @json($currentColor),
        greeting: @json($currentGreeting),
        position: @json($currentPosition),
        lang: @json($currentLang),
        previewKey: 0,
        botId: {{ $bot->id }},
        channelId: {{ $channel->id }},
        widgetUrl: '@samblaWidgetUrl',

        colorPresets: ['#DC2626', '#991B1B', '#1E40AF', '#047857', '#5B21B6', '#9A3412', '#1F2937'],

        positions: [
            { value: 'bottom-right', label: '↘ Dreapta jos (default)' },
            { value: 'bottom-left',  label: '↙ Stânga jos' },
            { value: 'top-right',    label: '↗ Dreapta sus' },
            { value: 'top-left',     label: '↖ Stânga sus' },
        ],

        bumpKey() {
            // Force iframe reload with new params
            this.previewKey++;
        },

        get previewUrl() {
            const qs = new URLSearchParams({
                color: this.color,
                greeting: this.greeting,
                position: this.position,
                lang: this.lang,
                _: this.previewKey,
            });
            return '/dashboard/agenti/' + this.botId + '/embed-customizer/preview?' + qs.toString();
        },

        get snippet() {
            return '<script src="' + this.widgetUrl
                + '" data-channel-id="' + this.channelId
                + '" data-color="' + this.color
                + '" data-position="' + this.position
                + '" data-lang="' + this.lang
                + '" data-greeting="' + this.greeting.replace(/"/g, '&quot;')
                + '" async defer><\/script>';
        },

        async copySnippet() {
            try { await navigator.clipboard.writeText(this.snippet); } catch (e) {}
        },
    };
}
</script>
@endsection
