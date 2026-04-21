@extends('layouts.dashboard')

@section('title', 'Configurare Agent AI — ' . $channel->getDisplayName())

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Agenți AI</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">Canale</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Widget setup</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Configurare widget</h1>
            <p class="mt-1 text-sm text-slate-500">Alege tema widget-ului și configurează mesajul de întâmpinare — previzualizarea se actualizează pe loc.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $channel->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $channel->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                {{ $channel->is_active ? 'Activ' : 'Inactiv' }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-2 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.bots.channels.chatbot-setup.save', [$bot, $channel]) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Left column: Config & Embed code --}}
            <div class="space-y-6">
                {{-- Channel Info --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-base font-semibold text-slate-900">Informații canal</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-500">Channel ID</span>
                            <code class="text-sm font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">{{ $channel->id }}</code>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-500">Agent asociat</span>
                            <span class="text-sm font-medium text-slate-900">{{ $bot->name }}</span>
                        </div>
                    </div>
                </div>

                {{-- Theme presets --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-base font-semibold text-slate-900">Temă widget</h2>
                        <p class="mt-1 text-xs text-slate-500">Alege o temă din catalog. Culorile, gradientul și forma bulei sunt presetate să arate coerent împreună.</p>
                    </div>
                    <div class="p-5">
                        <input type="hidden" name="theme_preset" id="theme-preset-input" value="{{ $activeTheme['preset'] }}">
                        <input type="hidden" name="color" id="color-input" value="{{ $activeTheme['accent'] }}">

                        <div id="preset-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($themePresets as $preset)
                                <button type="button"
                                        data-preset="{{ $preset['key'] }}"
                                        data-accent="{{ $preset['accent'] }}"
                                        data-accent-soft="{{ $preset['accent_soft'] }}"
                                        data-bubble-radius="{{ $preset['bubble_radius'] }}"
                                        class="preset-card group relative rounded-xl border-2 p-3 text-left transition-all hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-slate-400 {{ $activeTheme['preset'] === $preset['key'] ? 'border-slate-900 shadow-md' : 'border-slate-200' }}"
                                        title="{{ $preset['description'] }}">
                                    <div class="h-8 rounded-md mb-2" style="background: linear-gradient(135deg, {{ $preset['accent'] }}, {{ $preset['accent_soft'] }})"></div>
                                    <div class="text-xs font-semibold text-slate-800 leading-tight">{{ $preset['name'] }}</div>
                                    @if($activeTheme['preset'] === $preset['key'])
                                        <span class="absolute top-2 right-2 w-4 h-4 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px]">✓</span>
                                    @endif
                                </button>
                            @endforeach

                            {{-- Custom option --}}
                            <button type="button"
                                    data-preset="custom"
                                    class="preset-card group relative rounded-xl border-2 border-dashed p-3 text-left transition-all hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-slate-400 {{ $activeTheme['preset'] === 'custom' ? 'border-slate-900 shadow-md' : 'border-slate-300' }}">
                                <div class="h-8 rounded-md mb-2 bg-gradient-to-br from-pink-400 via-purple-400 to-blue-400 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="text-xs font-semibold text-slate-800 leading-tight">Custom</div>
                            </button>
                        </div>

                        {{-- Custom color picker (hidden unless preset=custom) --}}
                        <div id="custom-color-panel" class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200 {{ $activeTheme['preset'] === 'custom' ? '' : 'hidden' }}">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Culoare principală (hex)</label>
                            <div class="flex items-center gap-3">
                                <input type="color" id="custom-color-picker" value="{{ $activeTheme['accent'] }}"
                                       class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                                <input type="text" id="custom-color-text" value="{{ $activeTheme['accent'] }}"
                                       class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono text-slate-700 outline-none focus:border-slate-500">
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">Gradientul secundar se generează automat din culoarea aleasă.</p>
                        </div>
                    </div>
                </div>

                {{-- Greeting + position --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-base font-semibold text-slate-900">Text și poziționare</h2>
                    </div>
                    <div class="p-5 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mesaj de întâmpinare</label>
                            <textarea name="greeting" id="widget-greeting" rows="2"
                                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 outline-none resize-none">{{ $channel->config['greeting'] ?? 'Bună! Cu ce te pot ajuta?' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Poziție widget</label>
                            <select name="position" id="widget-position"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 outline-none">
                                <option value="bottom-right" {{ ($channel->config['position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Dreapta jos</option>
                                <option value="bottom-left" {{ ($channel->config['position'] ?? 'bottom-right') === 'bottom-left' ? 'selected' : '' }}>Stânga jos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Anulează
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition-colors">
                        Salvează
                    </button>
                </div>

                {{-- Embed Code --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-900">Cod de integrare</h2>
                        <button type="button" onclick="copyEmbedCode()" id="copy-btn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span id="copy-text">Copiază</span>
                        </button>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-slate-500 mb-3">Adaugă acest cod înainte de <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">&lt;/body&gt;</code> pe site-ul tău. Tema salvată aici se aplică automat — nu e nevoie să o duplici în atribute.</p>
                        <pre id="embed-code" class="bg-slate-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto whitespace-pre-wrap break-all leading-relaxed select-all"></pre>
                    </div>
                </div>
            </div>

            {{-- Right column: Preview --}}
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm sticky top-6">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-base font-semibold text-slate-900">Previzualizare widget</h2>
                    </div>
                    <div class="p-5">
                        <div id="preview-container" class="relative bg-slate-100 rounded-lg overflow-hidden" style="min-height: 560px;">
                            <div class="p-6">
                                <div class="h-4 bg-slate-200 rounded w-3/4 mb-3"></div>
                                <div class="h-4 bg-slate-200 rounded w-1/2 mb-3"></div>
                                <div class="h-4 bg-slate-200 rounded w-2/3 mb-6"></div>
                                <div class="h-24 bg-slate-200 rounded mb-4"></div>
                                <div class="h-4 bg-slate-200 rounded w-5/6 mb-3"></div>
                                <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                            </div>

                            <div id="preview-window" class="absolute bottom-16 right-4 w-[300px] bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                                <div id="preview-header" class="px-4 py-3 text-white flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold">{{ $bot->name }}</div>
                                        <div class="text-xs opacity-80">Online</div>
                                    </div>
                                </div>
                                <div class="p-3 bg-slate-50 space-y-2" style="min-height: 160px;">
                                    <div id="preview-greeting" class="bg-white border border-slate-200 rounded-xl rounded-bl-sm px-3 py-2 text-xs text-slate-700 max-w-[80%]">
                                        {{ $channel->config['greeting'] ?? 'Bună! Cu ce te pot ajuta?' }}
                                    </div>
                                    <div id="preview-user-msg" class="text-white px-3 py-2 text-xs max-w-[80%] ml-auto">
                                        Vreau mai multe informații
                                    </div>
                                    <div class="bg-white border border-slate-200 rounded-xl rounded-bl-sm px-3 py-2 text-xs text-slate-700 max-w-[80%]">
                                        Sigur! Cu ce anume te pot ajuta?
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 p-2.5 border-t border-slate-200">
                                    <div class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-400">Scrie un mesaj...</div>
                                    <div id="preview-send" class="w-8 h-8 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="white" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div id="preview-bubble" class="absolute bottom-3 right-4 w-12 h-12 rounded-full flex items-center justify-center shadow-lg cursor-default">
                                <svg class="w-6 h-6" fill="white" viewBox="0 0 24 24">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>
                                    <path d="M7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function() {
    var channelId = '{{ $channel->id }}';
    var presetInput = document.getElementById('theme-preset-input');
    var colorInput = document.getElementById('color-input');
    var customPanel = document.getElementById('custom-color-panel');
    var customPicker = document.getElementById('custom-color-picker');
    var customText = document.getElementById('custom-color-text');
    var greetingEl = document.getElementById('widget-greeting');
    var positionEl = document.getElementById('widget-position');

    var state = {
        preset: '{{ $activeTheme['preset'] }}',
        accent: '{{ $activeTheme['accent'] }}',
        accentSoft: '{{ $activeTheme['accent_soft'] }}',
        bubbleRadius: '{{ $activeTheme['bubble_radius'] }}'
    };

    function lighten(hex, ratio) {
        hex = hex.replace('#', '');
        if (hex.length !== 6) return '#' + hex;
        var r = parseInt(hex.substr(0, 2), 16);
        var g = parseInt(hex.substr(2, 2), 16);
        var b = parseInt(hex.substr(4, 2), 16);
        r = Math.min(255, Math.round(r + (255 - r) * ratio));
        g = Math.min(255, Math.round(g + (255 - g) * ratio));
        b = Math.min(255, Math.round(b + (255 - b) * ratio));
        return '#' + [r, g, b].map(function(n) { return n.toString(16).padStart(2, '0'); }).join('');
    }

    function applyPreview() {
        var gradient = 'linear-gradient(135deg, ' + state.accent + ', ' + state.accentSoft + ')';
        document.getElementById('preview-header').style.background = gradient;
        document.getElementById('preview-bubble').style.background = gradient;
        document.getElementById('preview-send').style.background = gradient;
        var userMsg = document.getElementById('preview-user-msg');
        userMsg.style.background = gradient;
        userMsg.style.borderRadius = state.bubbleRadius;
        userMsg.style.borderBottomRightRadius = '4px';
    }

    function updateEmbedCode() {
        var code = '<script src="https://sambla.ro/widget/sambla-chat.min.js" data-channel-id="' + channelId + '"><\/script>';
        document.getElementById('embed-code').textContent = code;
    }

    function selectPreset(card) {
        var presetKey = card.dataset.preset;
        state.preset = presetKey;

        document.querySelectorAll('.preset-card').forEach(function(c) {
            c.classList.remove('border-slate-900', 'shadow-md');
            if (c.dataset.preset === 'custom') {
                c.classList.add('border-slate-300');
                c.classList.remove('border-slate-200');
            } else {
                c.classList.add('border-slate-200');
            }
            var badge = c.querySelector('.preset-check');
            if (badge) badge.remove();
        });
        card.classList.remove('border-slate-200', 'border-slate-300');
        card.classList.add('border-slate-900', 'shadow-md');

        if (presetKey === 'custom') {
            customPanel.classList.remove('hidden');
            var hex = customText.value;
            state.accent = hex;
            state.accentSoft = lighten(hex, 0.18);
            state.bubbleRadius = '16px';
        } else {
            customPanel.classList.add('hidden');
            state.accent = card.dataset.accent;
            state.accentSoft = card.dataset.accentSoft;
            state.bubbleRadius = card.dataset.bubbleRadius;
        }

        presetInput.value = state.preset;
        colorInput.value = state.accent;
        applyPreview();
    }

    document.querySelectorAll('.preset-card').forEach(function(card) {
        card.addEventListener('click', function() { selectPreset(card); });
    });

    customPicker.addEventListener('input', function() {
        customText.value = this.value;
        state.accent = this.value;
        state.accentSoft = lighten(this.value, 0.18);
        colorInput.value = this.value;
        applyPreview();
    });

    customText.addEventListener('input', function() {
        if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
            customPicker.value = this.value;
            state.accent = this.value;
            state.accentSoft = lighten(this.value, 0.18);
            colorInput.value = this.value;
            applyPreview();
        }
    });

    greetingEl.addEventListener('input', function() {
        document.getElementById('preview-greeting').textContent = this.value || 'Bună! Cu ce te pot ajuta?';
    });

    window.copyEmbedCode = function() {
        var code = document.getElementById('embed-code').textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                var btn = document.getElementById('copy-text');
                btn.textContent = 'Copiat!';
                setTimeout(function() { btn.textContent = 'Copiază'; }, 2000);
            });
        }
    };

    applyPreview();
    updateEmbedCode();
})();
</script>
@endpush
