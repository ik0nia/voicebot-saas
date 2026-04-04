@extends('layouts.dashboard')

@section('title', 'Configurare Chatbot — ' . $channel->getDisplayName())

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boti</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Chatbot Setup</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Configurare Widget Chatbot</h1>
            <p class="mt-1 text-sm text-slate-500">Configurati si integrati widget-ul de chat pe site-ul dumneavoastra.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $channel->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $channel->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                {{ $channel->is_active ? 'Activ' : 'Inactiv' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left column: Config & Embed code --}}
        <div class="space-y-6">
            {{-- Channel Info --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Informatii Canal</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Channel ID</span>
                        <code class="text-sm font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">{{ $channel->id }}</code>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Bot asociat</span>
                        <span class="text-sm font-medium text-slate-900">{{ $bot->name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Webhook URL</span>
                        <code class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600 max-w-[200px] truncate">{{ url('/api/v1/chatbot/' . $channel->id . '/message') }}</code>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Config URL</span>
                        <code class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600 max-w-[200px] truncate">{{ url('/api/v1/chatbot/' . $channel->id . '/config') }}</code>
                    </div>
                </div>
            </div>

            {{-- Customization --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Personalizare</h2>
                </div>
                <div class="p-5 space-y-5">
                    {{-- Color --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Culoare principala</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="widget-color" value="{{ $channel->config['color'] ?? '#991b1b' }}"
                                   class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                            <input type="text" id="widget-color-text" value="{{ $channel->config['color'] ?? '#991b1b' }}"
                                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono text-slate-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                    </div>

                    {{-- Greeting --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mesaj de intampinare</label>
                        <textarea id="widget-greeting" rows="2"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none resize-none">{{ $channel->config['greeting'] ?? 'Buna! Cu ce te pot ajuta?' }}</textarea>
                    </div>

                    {{-- Position --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Pozitie widget</label>
                        <select id="widget-position"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                            <option value="bottom-right" {{ ($channel->config['position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Dreapta jos</option>
                            <option value="bottom-left" {{ ($channel->config['position'] ?? 'bottom-right') === 'bottom-left' ? 'selected' : '' }}>Stanga jos</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Embed Code --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Cod de integrare</h2>
                    <button onclick="copyEmbedCode()" id="copy-btn"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span id="copy-text">Copiaza</span>
                    </button>
                </div>
                <div class="p-5">
                    <p class="text-sm text-slate-500 mb-3">Adaugati acest cod inainte de <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">&lt;/body&gt;</code> in pagina dumneavoastra:</p>
                    <pre id="embed-code" class="bg-slate-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto whitespace-pre-wrap break-all leading-relaxed select-all"></pre>
                </div>
            </div>
        </div>

        {{-- Right column: Preview --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Previzualizare Widget</h2>
                </div>
                <div class="p-5">
                    <div id="preview-container" class="relative bg-slate-100 rounded-lg overflow-hidden" style="min-height: 560px;">
                        {{-- Simulated website background --}}
                        <div class="p-6">
                            <div class="h-4 bg-slate-200 rounded w-3/4 mb-3"></div>
                            <div class="h-4 bg-slate-200 rounded w-1/2 mb-3"></div>
                            <div class="h-4 bg-slate-200 rounded w-2/3 mb-6"></div>
                            <div class="h-24 bg-slate-200 rounded mb-4"></div>
                            <div class="h-4 bg-slate-200 rounded w-5/6 mb-3"></div>
                            <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                        </div>

                        {{-- Preview chat window (static mockup) --}}
                        <div id="preview-window" class="absolute bottom-16 right-4 w-[300px] bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                            <div id="preview-header" class="px-4 py-3 text-white flex items-center gap-3" style="background: {{ $channel->config['color'] ?? '#991b1b' }}">
                                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold" id="preview-bot-name">{{ $bot->name }}</div>
                                    <div class="text-xs opacity-80">Online</div>
                                </div>
                            </div>
                            <div class="text-center text-[9px] py-0.5 text-white/70" id="preview-powered" style="background: {{ $channel->config['color'] ?? '#991b1b' }}; border-top: 1px solid rgba(255,255,255,0.1)">
                                Powered by <span class="font-semibold text-white/90">Sambla</span>
                            </div>
                            <div class="p-3 bg-slate-50 space-y-2" style="min-height: 160px;">
                                <div id="preview-greeting" class="bg-white border border-slate-200 rounded-xl rounded-bl-sm px-3 py-2 text-xs text-slate-700 max-w-[80%]">
                                    {{ $channel->config['greeting'] ?? 'Buna! Cu ce te pot ajuta?' }}
                                </div>
                                <div id="preview-user-msg" class="text-white rounded-xl rounded-br-sm px-3 py-2 text-xs max-w-[80%] ml-auto" style="background: {{ $channel->config['color'] ?? '#991b1b' }}">
                                    Vreau mai multe informatii
                                </div>
                                <div class="bg-white border border-slate-200 rounded-xl rounded-bl-sm px-3 py-2 text-xs text-slate-700 max-w-[80%]">
                                    Sigur! Cu ce anume te pot ajuta?
                                </div>
                            </div>
                            <div class="flex items-center gap-2 p-2.5 border-t border-slate-200">
                                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-400">Scrie un mesaj...</div>
                                <div id="preview-send" class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: {{ $channel->config['color'] ?? '#991b1b' }}">
                                    <svg class="w-4 h-4" fill="white" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Preview bubble --}}
                        <div id="preview-bubble" class="absolute bottom-3 right-4 w-12 h-12 rounded-full flex items-center justify-center shadow-lg cursor-default" style="background: {{ $channel->config['color'] ?? '#991b1b' }}">
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
@endsection

@push('scripts')
<script>
    var channelId = '{{ $channel->id }}';
    var botName = '{{ addslashes($bot->name) }}';

    function getColor() {
        return document.getElementById('widget-color').value;
    }

    function getGreeting() {
        return document.getElementById('widget-greeting').value;
    }

    function getPosition() {
        return document.getElementById('widget-position').value;
    }

    function updateEmbedCode() {
        var color = getColor();
        var greeting = getGreeting();
        var position = getPosition();

        var attrs = 'data-channel-id="' + channelId + '"';
        attrs += ' data-color="' + color + '"';
        if (greeting && greeting !== 'Buna! Cu ce te pot ajuta?') {
            attrs += ' data-greeting="' + greeting.replace(/"/g, '&quot;') + '"';
        }
        if (position !== 'bottom-right') {
            attrs += ' data-position="' + position + '"';
        }

        var code = '<script src="https://sambla.ro/widget/sambla-chat.min.js" ' + attrs + '><\/script>';
        document.getElementById('embed-code').textContent = code;
    }

    function updatePreview() {
        var color = getColor();
        var greeting = getGreeting();

        // Update header
        document.getElementById('preview-header').style.background = color;
        document.getElementById('preview-powered').style.background = color;
        document.getElementById('preview-bubble').style.background = color;
        document.getElementById('preview-send').style.background = color;
        document.getElementById('preview-user-msg').style.background = color;

        // Update greeting
        document.getElementById('preview-greeting').textContent = greeting;
    }

    function copyEmbedCode() {
        var code = document.getElementById('embed-code').textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                var btn = document.getElementById('copy-text');
                btn.textContent = 'Copiat!';
                setTimeout(function() { btn.textContent = 'Copiaza'; }, 2000);
            });
        } else {
            // Fallback
            var textarea = document.createElement('textarea');
            textarea.value = code;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            var btn = document.getElementById('copy-text');
            btn.textContent = 'Copiat!';
            setTimeout(function() { btn.textContent = 'Copiaza'; }, 2000);
        }
    }

    // Sync color picker and text input
    document.getElementById('widget-color').addEventListener('input', function() {
        document.getElementById('widget-color-text').value = this.value;
        updatePreview();
        updateEmbedCode();
    });

    document.getElementById('widget-color-text').addEventListener('input', function() {
        var val = this.value;
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            document.getElementById('widget-color').value = val;
            updatePreview();
            updateEmbedCode();
        }
    });

    document.getElementById('widget-greeting').addEventListener('input', function() {
        updatePreview();
        updateEmbedCode();
    });

    document.getElementById('widget-position').addEventListener('change', function() {
        updateEmbedCode();
    });

    // Initialize
    updateEmbedCode();
</script>
@endpush
