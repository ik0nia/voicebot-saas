@extends('layouts.dashboard')

@section('title', 'Playground — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Playground</span>
@endsection

@section('content')
<div x-data="playground()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Playground</h1>
            <p class="mt-2 text-sm text-muted">Testează rapid <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-coralh hover:underline font-medium">{{ $bot->name }}</a> · chat live · preview voce · cod de instalare cu preview pe site simulat.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('dashboard.embed-customizer.show', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium" title="Customizer culoare/poziție widget">
                🎨 Embed
            </a>
            <a href="{{ route('dashboard.mock-customer.show', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium" title="AI joacă rol de client">
                🤖 Mock
            </a>
            <a href="{{ route('dashboard.personality-wizard.show', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">
                🎭 Personalitate
            </a>
            <a href="{{ route('dashboard.knowledge-gaps.show', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">
                🔍 Gap-uri KB
            </a>
            <a href="{{ route('dashboard.ab-prompt.show', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-coral/30 bg-coralsoft text-coralh hover:bg-coral hover:text-cream font-medium transition">
                A/B prompt
            </a>
            <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-sm px-3 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">
                Editează
            </a>
        </div>
    </div>

    {{-- 3 panel grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- ============ PANEL 1: CHAT TESTER ============ --}}
        <div class="card overflow-hidden flex flex-col" style="height: 70vh;">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between bg-cream/50">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                    </div>
                    <h2 class="display text-base font-semibold text-ink">Chat tester</h2>
                </div>
                <button @click="resetChat()" class="text-2xs text-muted hover:text-inkSoft">↻ reset</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="messages">
                <template x-if="messages.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-center px-6">
                        <div class="w-14 h-14 rounded-2xl bg-coralsoft/60 text-coralh mx-auto mb-3 flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-ink mb-1">Începe o conversație de test</p>
                        <p class="text-2xs text-muted">Răspunsurile vin de la agentul real, exact ca pe site.</p>
                        <div class="mt-4 flex flex-wrap gap-1.5 justify-center">
                            <button @click="sendQuick('Bună, ce program aveți?')" class="px-2.5 py-1 rounded-pill text-2xs bg-cream border border-line hover:border-coral/40">Ce program aveți?</button>
                            <button @click="sendQuick('Aș dori să fac o programare')" class="px-2.5 py-1 rounded-pill text-2xs bg-cream border border-line hover:border-coral/40">Vreau o programare</button>
                            <button @click="sendQuick('Care sunt prețurile?')" class="px-2.5 py-1 rounded-pill text-2xs bg-cream border border-line hover:border-coral/40">Care-s prețurile?</button>
                        </div>
                    </div>
                </template>

                <template x-for="(m, i) in messages" :key="i">
                    <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="m.role === 'user' ? 'bg-coral text-cream' : 'bg-cream text-ink'"
                             class="max-w-[85%] px-3.5 py-2 rounded-2xl text-sm whitespace-pre-wrap leading-relaxed">
                            <span x-text="m.content"></span>
                            <span x-show="m.streaming" class="inline-block w-1.5 h-3 bg-current opacity-60 ml-0.5 animate-pulse"></span>
                        </div>
                    </div>
                </template>

                <template x-if="loading && !messages.some(m => m.streaming)">
                    <div class="flex justify-start">
                        <div class="bg-cream px-3.5 py-2 rounded-2xl text-muted text-sm flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-muted rounded-full animate-pulse"></span>
                            <span class="w-1.5 h-1.5 bg-muted rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                            <span class="w-1.5 h-1.5 bg-muted rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                        </div>
                    </div>
                </template>
            </div>

            <form @submit.prevent="send()" class="p-3 border-t border-line bg-paper">
                <div class="flex gap-2">
                    <input type="text" x-model="input" :disabled="loading"
                           placeholder="Tastează un mesaj…"
                           class="flex-1 rounded-pill border border-line bg-white px-4 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                    <button type="submit" :disabled="loading || !input.trim()"
                            class="btn-coral rounded-pill px-4 py-2 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading">Trimite →</span>
                        <span x-show="loading">…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ============ PANEL 2: VOICE PREVIEW ============ --}}
        <div class="card overflow-hidden flex flex-col" style="height: 70vh;">
            <div class="px-4 py-3 border-b border-line flex items-center gap-2 bg-cream/50">
                <div class="w-7 h-7 rounded-lg bg-[#DCEBFA] text-[#1E40AF] flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11a7 7 0 01-14 0M12 18v4M8 22h8M15 11a3 3 0 11-6 0V5a3 3 0 016 0z"/></svg>
                </div>
                <h2 class="display text-base font-semibold text-ink">Preview voce</h2>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <div>
                    <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Voce</label>
                    <select x-model="voice"
                            class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        @foreach($voices as $v)
                            <option value="{{ $v['key'] }}" {{ $bot->voice === $v['key'] ? 'selected' : '' }}>{{ $v['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-2">Text de citit (max 500)</label>
                    <textarea x-model="ttsText" rows="6" maxlength="500"
                              class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y font-mono"></textarea>
                    <div class="text-2xs text-muted mt-1 mono" x-text="ttsText.length + ' / 500'"></div>
                </div>

                <button @click="generateTTS()" :disabled="ttsLoading || !ttsText.trim()"
                        class="btn-coral w-full rounded-pill px-4 py-2.5 text-sm font-medium disabled:opacity-50">
                    <span x-show="!ttsLoading">▶ Generează și ascultă</span>
                    <span x-show="ttsLoading">Generez audio…</span>
                </button>

                <template x-if="ttsAudioUrl">
                    <div class="space-y-2">
                        <audio :src="ttsAudioUrl" controls autoplay class="w-full" style="height: 40px;"></audio>
                        <div class="text-2xs text-muted text-center">
                            Voce: <strong x-text="voice"></strong> · ~<span x-text="Math.round(ttsText.length / 15)"></span>s estimat
                        </div>
                    </div>
                </template>

                <template x-if="ttsError">
                    <div class="p-3 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="ttsError"></div>
                </template>

                <div class="pt-3 border-t border-line text-2xs text-muted">
                    <strong class="text-inkSoft">Tip:</strong> dacă vocea pe care o testezi sună bine, schimbă-o ca default agentului din <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-coralh hover:underline">Editează prompt</a> → tab Identitate.
                </div>
            </div>
        </div>

        {{-- ============ PANEL 3: EMBED LIVE PREVIEW ============ --}}
        <div class="card overflow-hidden flex flex-col" style="height: 70vh;">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between bg-cream/50">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#D7EFE0] text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
                    </div>
                    <h2 class="display text-base font-semibold text-ink">Embed pe site</h2>
                </div>
                <a :href="'{{ $previewIframeUrl }}'" target="_blank" class="text-2xs text-coralh hover:underline">deschide standalone ↗</a>
            </div>

            {{-- Tabs HTML/WordPress/React/Shopify --}}
            <div class="flex border-b border-line">
                @foreach(['html' => 'HTML', 'wordpress' => 'WordPress', 'react' => 'React', 'shopify' => 'Shopify'] as $key => $label)
                    <button @click="snippetTab = '{{ $key }}'"
                            :class="snippetTab === '{{ $key }}' ? 'border-b-2 border-coral text-coralh font-semibold' : 'text-muted hover:text-inkSoft'"
                            class="flex-1 px-3 py-2 text-2xs uppercase tracking-wider transition-colors">{{ $label }}</button>
                @endforeach
            </div>

            <div class="p-3 bg-cream/30 border-b border-line relative">
                <pre class="text-2xs font-mono text-inkSoft overflow-x-auto whitespace-pre" x-text="snippets[snippetTab]" style="max-height: 8rem;"></pre>
                <button @click="copySnippet()" class="absolute top-2 right-2 px-2 py-1 rounded text-2xs bg-paper border border-line hover:bg-coral hover:text-cream transition">
                    <span x-show="!copied">copy</span>
                    <span x-show="copied">✓ copiat</span>
                </button>
            </div>

            <div class="flex-1 overflow-hidden bg-[#1C1917] relative">
                <iframe :src="'{{ $previewIframeUrl }}'"
                        class="w-full h-full border-0"
                        style="background: #fff;"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                <div class="absolute top-2 left-2 px-2 py-0.5 rounded bg-ink/70 text-cream text-2xs font-mono pointer-events-none">site exemplu · live preview</div>
            </div>
        </div>
    </div>

    {{-- Mobile preview QR — scan cu telefonul, deschide widget pe mobil real --}}
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="shrink-0 bg-white p-2 rounded-lg border border-line">
                <img src="{{ $qrUrl }}" alt="QR mobile preview" class="w-32 h-32 block" loading="lazy">
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="display text-base font-semibold text-ink mb-1">📱 Testează pe telefonul tău</h3>
                <p class="text-sm text-muted mb-3">Scanează QR-ul cu camera telefonului → se deschide widget-ul exact cum îl vor vedea vizitatorii pe mobil. Link-ul e valabil 1 oră, fără login.</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <input type="text" readonly value="{{ $mobileUrl }}"
                           class="flex-1 min-w-[200px] rounded-lg border border-line bg-cream px-3 py-1.5 text-2xs font-mono text-inkSoft" onclick="this.select()">
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $mobileUrl }}'); this.innerText='✓ copiat'; setTimeout(()=>this.innerText='copiază link',2000)" class="px-3 py-1.5 rounded-pill border border-line bg-white hover:bg-cream text-2xs font-medium">copiază link</button>
                    <a href="{{ $mobileUrl }}" target="_blank" class="px-3 py-1.5 rounded-pill bg-coralsoft text-coralh hover:bg-coral hover:text-cream text-2xs font-medium transition">deschide ↗</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Notă subsol --}}
    <div class="card p-4 bg-cream/40 border-dashed">
        <div class="flex items-start gap-3">
            <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-2xs text-inkSoft">
                <strong class="text-ink">Conversațiile de test</strong> nu se contorizează la planul tău + nu apar în Inbox/Leads. Voce preview folosește OpenAI TTS în RON 0.04/min — minim cost.
            </div>
        </div>
    </div>
</div>

<script>
function playground() {
    return {
        // --- chat state ---
        channelId: {{ $webChannel->id }},
        messages: [],
        input: '',
        loading: false,
        sessionId: 'pg-' + Math.random().toString(36).slice(2, 12),

        // --- voice state ---
        voice: '{{ $bot->voice ?? 'coral' }}',
        ttsText: @json($sampleText),
        ttsLoading: false,
        ttsAudioUrl: null,
        ttsError: null,

        // --- embed state ---
        snippetTab: 'html',
        snippets: @json($snippets),
        copied: false,

        sendQuick(text) {
            this.input = text;
            this.send();
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;
            this.input = '';
            this.messages.push({ role: 'user', content: text });
            this.loading = true;
            this.scrollDown();

            const reply = { role: 'assistant', content: '', streaming: true };
            this.messages.push(reply);

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch('/api/v1/chatbot/' + this.channelId + '/message-stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        message: text,
                        session_id: this.sessionId,
                        contact_identifier: 'playground@sambla.ro',
                    }),
                });

                if (!resp.ok) throw new Error('HTTP ' + resp.status);

                const reader = resp.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const events = buffer.split(/\n\n/);
                    buffer = events.pop() || '';
                    for (const ev of events) {
                        const dataLine = ev.split('\n').find(l => l.startsWith('data: '));
                        if (!dataLine) continue;
                        const json = dataLine.slice(6).trim();
                        if (!json || json === '[DONE]') continue;
                        try {
                            const obj = JSON.parse(json);
                            if (obj.delta) {
                                reply.content += obj.delta;
                                this.scrollDown();
                            } else if (obj.content) {
                                reply.content = obj.content;
                            }
                        } catch (e) {}
                    }
                }
            } catch (e) {
                reply.content = '⚠ Eroare: ' + e.message;
            } finally {
                reply.streaming = false;
                this.loading = false;
                this.scrollDown();
            }
        },

        scrollDown() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        resetChat() {
            this.messages = [];
            this.sessionId = 'pg-' + Math.random().toString(36).slice(2, 12);
        },

        async generateTTS() {
            if (!this.ttsText.trim() || this.ttsLoading) return;
            this.ttsLoading = true;
            this.ttsError = null;
            this.ttsAudioUrl = null;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch('/dashboard/agenti/{{ $bot->id }}/playground/tts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'audio/mpeg',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        text: this.ttsText,
                        voice: this.voice,
                    }),
                });

                if (!resp.ok) {
                    const err = await resp.text();
                    throw new Error(err || ('HTTP ' + resp.status));
                }

                const blob = await resp.blob();
                this.ttsAudioUrl = URL.createObjectURL(blob);
            } catch (e) {
                this.ttsError = 'TTS eșuat: ' + e.message;
            } finally {
                this.ttsLoading = false;
            }
        },

        async copySnippet() {
            try {
                await navigator.clipboard.writeText(this.snippets[this.snippetTab]);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {}
        },
    };
}
</script>
@endsection
