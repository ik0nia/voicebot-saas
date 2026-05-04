@extends('layouts.dashboard')

@section('title', 'Operator console')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Operator</span>
@endsection

@push('styles')
<link rel="manifest" href="/manifest-operator.json">
<meta name="theme-color" content="#DC2626">
@endpush

@section('content')
{{-- Operator console — full-bleed PWA în interiorul layout-ului dashboard.
     Două probleme de rezolvat simultan:
       1. <main> al layout-ului are overflow-y-auto + padding — vrem să-l
          ignorăm aici ca să avem scroll intern, nu de pagină.
       2. Lanțul flex columns trebuie să aibă min-h-0 la fiecare nivel
          ca overflow-y-auto pe copilul terminal (panoul de mesaje) să
          poată shrink-ui sub conținut, în loc să crească parentul.

     Soluția: înălțime explicită pe wrapper (calc viewport - topbar),
     overflow-hidden ca să tăiem orice scroll de pagină, negative margins
     ca să cancelăm padding-ul main-ului, iar inside chain-ul flex
     poartă min-h-0 până la copilul cu overflow auto. --}}
<div x-data="operatorConsole()" x-init="init()"
     class="flex flex-col -mt-6 lg:-mt-10 -mx-4 lg:-mx-8 -mb-6 lg:-mb-10 overflow-hidden"
     style="height: calc(100vh - 64px);">

    <div class="flex flex-1 min-h-0">

        {{-- Left: conversation list --}}
        <aside class="w-full sm:w-80 md:w-96 lg:w-[28rem] border-r border-line bg-paper flex flex-col min-h-0"
               :class="activeId ? 'hidden sm:flex' : 'flex'">

            {{-- Top filter bar --}}
            <div class="px-4 py-3 border-b border-line bg-cream/40">
                <div class="flex items-center justify-between mb-3">
                    <h1 class="display text-lg font-semibold text-ink">Operator</h1>
                    <div class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>
                        <span class="text-2xs text-muted mono" x-text="lastUpdated"></span>
                    </div>
                </div>
                <div class="flex items-center gap-1 text-2xs">
                    <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-ink text-cream' : 'bg-cream text-inkSoft'"
                            class="px-2.5 py-1 rounded-pill font-medium">
                        Toate <span class="ml-1 mono" x-text="conversations.length"></span>
                    </button>
                    <button @click="filter = 'mine'" :class="filter === 'mine' ? 'bg-ink text-cream' : 'bg-cream text-inkSoft'"
                            class="px-2.5 py-1 rounded-pill font-medium">
                        Mie <span class="ml-1 mono" x-text="counters.mine"></span>
                    </button>
                    <button @click="filter = 'unassigned'" :class="filter === 'unassigned' ? 'bg-ink text-cream' : 'bg-cream text-inkSoft'"
                            class="px-2.5 py-1 rounded-pill font-medium">
                        Libere <span class="ml-1 mono" x-text="counters.unassigned"></span>
                    </button>
                    <button @click="filter = 'needs_human'" :class="filter === 'needs_human' ? 'bg-coral text-cream' : 'bg-cream text-inkSoft'"
                            class="px-2.5 py-1 rounded-pill font-medium">
                        Cer ajutor <span class="ml-1 mono" x-text="counters.needs_human"></span>
                    </button>
                </div>

                {{-- Push notification status --}}
                <div class="mt-3 pt-3 border-t border-line flex items-center justify-between text-2xs">
                    <span class="text-muted">Notificări push:</span>
                    <span x-show="pushStatus === 'granted'" class="text-emerald-700 font-medium">✓ active</span>
                    <span x-show="pushStatus === 'denied'" class="text-coralh">✗ blocate (verifică setări browser)</span>
                    <span x-show="pushStatus === 'default'">
                        <button @click="enablePush()" class="text-coralh hover:underline font-medium">Activează push →</button>
                    </span>
                    <span x-show="pushStatus === 'unsupported'" class="text-muted">browser nu suportă</span>
                    <button x-show="pushStatus === 'granted'" @click="testPush()" class="text-coralh hover:underline ml-2">test</button>
                </div>
            </div>

            {{-- Conversation list --}}
            <div class="flex-1 overflow-y-auto">
                <template x-if="filteredConvs.length === 0 && !loading">
                    <p class="text-sm text-muted text-center py-8 px-4">Nicio conversație în filtrul curent.</p>
                </template>
                <template x-for="c in filteredConvs" :key="c.id">
                    {{-- needs_human → highlight major: bg coral pal +
                         border-l coral 4px + pulse pe „cer operator" badge.
                         Imposibil de ratat la o privire pe lista de
                         conversații, ceea ce e exact intenția. --}}
                    <button @click="open(c.id)"
                            :class="[
                                activeId === c.id ? 'bg-coralsoft border-l-coral' : (c.needs_human && !c.is_mine ? 'bg-coralsoft/40 border-l-coral animate-needs-pulse' : 'border-l-transparent')
                            ]"
                            class="w-full text-left px-4 py-3 border-b border-line border-l-4 hover:bg-cream/50 transition-colors">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="text-sm font-semibold truncate flex-1"
                                  :class="c.needs_human && !c.is_mine ? 'text-coralh' : 'text-ink'"
                                  x-text="c.contact"></span>
                            <span class="text-2xs text-muted mono shrink-0" x-text="c.last_activity_relative"></span>
                        </div>
                        <div class="flex items-center gap-1.5 mb-1.5 text-2xs">
                            <span class="text-inkSoft" x-text="c.bot_name"></span>
                            <span class="text-line">·</span>
                            <span class="text-muted mono" x-text="c.messages_count + ' msg'"></span>
                            <template x-if="c.lead_score >= 50">
                                <span class="text-amber-700">· hot</span>
                            </template>
                        </div>
                        <div class="flex items-center gap-1 flex-wrap">
                            {{-- needs_human badge primul, mai mare, cu emoji RGB --}}
                            <template x-if="c.needs_human && !c.is_mine">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill text-2xs font-bold bg-coral text-cream">
                                    🙋 cere operator
                                </span>
                            </template>
                            <template x-if="c.is_mine">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-pill text-2xs bg-emerald-100 text-emerald-700">la mine</span>
                            </template>
                            <template x-if="c.is_unassigned && !c.needs_human">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-pill text-2xs bg-amber-100 text-amber-800">liberă</span>
                            </template>
                            <template x-if="c.urgency === 'critical' || c.urgency === 'high'">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-pill text-2xs bg-coralsoft text-coralh" x-text="c.urgency"></span>
                            </template>
                            <template x-if="c.sentiment === 'frustrated' || c.sentiment === 'negative'">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-pill text-2xs bg-amber-100 text-amber-800" x-text="c.sentiment"></span>
                            </template>
                        </div>
                    </button>
                </template>
            </div>
        </aside>

        {{-- Right: active conversation --}}
        <main class="flex-1 flex flex-col bg-paper min-h-0 min-w-0" :class="activeId ? 'flex' : 'hidden sm:flex'">
            <template x-if="!activeId">
                <div class="flex-1 flex items-center justify-center text-center px-6">
                    <div>
                        <div class="w-16 h-16 rounded-2xl bg-coralsoft text-coralh mx-auto mb-3 flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                        </div>
                        <h2 class="display text-base font-semibold text-ink">Selectează o conversație</h2>
                        <p class="text-sm text-muted mt-1">Sau așteaptă ca un vizitator să ceară operator.</p>
                    </div>
                </div>
            </template>

            <template x-if="activeId">
                {{-- min-h-0 critic în flex column: fără el, copilul cu
                     overflow-y-auto crește container-ul în loc să activeze
                     scroll-ul intern. Asta lăsa form-ul de input să iasă
                     sub fold când conversația era lungă. --}}
                <div class="flex flex-col flex-1 min-h-0">
                    <div class="px-4 py-3 border-b border-line bg-cream/40 flex items-center gap-3">
                        <button @click="activeId = null" class="sm:hidden text-muted hover:text-ink">←</button>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-ink truncate" x-text="activeConv?.contact"></div>
                            <div class="text-2xs text-muted truncate" x-text="activeConv?.bot_name"></div>
                        </div>
                        <template x-if="activeConv?.is_mine">
                            <button @click="release()" class="text-2xs px-3 py-1.5 rounded-pill border border-line bg-white hover:bg-cream font-medium">↩ Înapoi la bot</button>
                        </template>
                        <template x-if="!activeConv?.is_mine">
                            <button @click="takeOver()" class="text-2xs px-3 py-1.5 rounded-pill btn-coral font-medium">⚡ Preia conversația</button>
                        </template>
                    </div>

                    <div class="flex-1 overflow-y-auto min-h-0 px-3 py-2 space-y-0.5" x-ref="msgPane">
                        <template x-if="loadingMsgs">
                            <p class="text-2xs text-muted text-center py-4">se încarcă mesaje…</p>
                        </template>
                        <template x-for="m in messages" :key="m.id">
                            {{-- Group context: timestamp ascuns by default, apare
                                 doar la hover pe rând. Stil iMessage / WhatsApp
                                 — chat dens, fără zgomot temporal repetat. --}}
                            <div class="group" :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                <div class="max-w-[70%] md:max-w-[55%] flex flex-col gap-px"
                                     :class="m.role === 'user' ? 'items-end' : 'items-start'">
                                    {{-- main bubble. Tooltip cu timestamp absolut
                                         pe hover; nu scoatem timestamp-ul de tot,
                                         îl punem în title attribute. --}}
                                    <div :class="[
                                            m.role === 'user' ? 'bg-coral text-cream rounded-tr-sm' : (m.role === 'operator' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200 rounded-tl-sm' : 'bg-cream text-ink rounded-tl-sm')
                                         ]"
                                         :title="m.at_relative"
                                         class="px-3 py-1.5 rounded-2xl text-sm leading-snug whitespace-pre-line break-words">
                                        <template x-if="m.role === 'operator'">
                                            <div class="text-2xs opacity-70 mb-0.5 font-semibold">
                                                👨‍💼 Operator<span x-show="m.operator_name" x-text="': ' + m.operator_name"></span>
                                            </div>
                                        </template>
                                        <div x-text="m.content"></div>
                                    </div>

                                    {{-- product cards (read-only mirror of widget) --}}
                                    <template x-if="m.products && m.products.length">
                                        <div class="flex gap-2 overflow-x-auto pb-1 max-w-full" title="Produse afișate vizitatorului">
                                            <template x-for="p in m.products.slice(0, 8)" :key="p.id || p.name">
                                                <div class="flex-shrink-0 w-32 rounded-lg border border-line bg-white overflow-hidden shadow-sm">
                                                    <template x-if="p.image_url">
                                                        <img :src="p.image_url" :alt="p.name" class="w-full h-20 object-cover" loading="lazy">
                                                    </template>
                                                    <div class="p-1.5">
                                                        <p class="text-2xs font-semibold text-ink leading-tight line-clamp-2" x-text="p.name"></p>
                                                        <template x-if="p.sale_price && p.regular_price">
                                                            <p class="mt-0.5 text-2xs font-bold text-coral">
                                                                <span x-text="p.sale_price + ' ' + (p.currency || 'RON')"></span>
                                                                <span class="text-[10px] text-muted line-through font-normal ml-1" x-text="p.regular_price"></span>
                                                            </p>
                                                        </template>
                                                        <template x-if="!(p.sale_price && p.regular_price)">
                                                            <p class="mt-0.5 text-2xs font-bold text-ink" x-text="(p.price || '') + ' ' + (p.currency || 'RON')"></p>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- quick reply chips (read-only) --}}
                                    <template x-if="m.quick_replies && m.quick_replies.length">
                                        <div class="flex flex-wrap gap-1" title="Sugestii afișate vizitatorului">
                                            <template x-for="(c, i) in m.quick_replies.slice(0, 6)" :key="i">
                                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-2xs"
                                                      :class="c.action ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-white border-line text-inkSoft'">
                                                    <span x-show="c.action" class="text-emerald-600">✓</span>
                                                    <span x-text="(c.label || c.text || '').slice(0, 60)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- timestamp ascuns complet din layout.
                                         Tooltip-ul pe bulă afișează „X minute în
                                         urmă" la hover; nu mai adăugăm un rând
                                         dedicat care ocupă verticală chiar și
                                         transparent. Page context tot tooltip
                                         pe bulă (vezi update mai sus). --}}
                                </div>
                            </div>
                        </template>
                    </div>

                    <form @submit.prevent="sendReply()" class="flex-shrink-0 p-3 border-t border-line bg-paper">
                        <div class="flex items-end gap-2">
                            <textarea x-model="replyText" rows="2" :disabled="!activeConv?.is_mine || sending"
                                      :placeholder="activeConv?.is_mine ? 'Tastează un răspuns…' : 'Preia conversația ca să poți răspunde'"
                                      class="flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-none disabled:bg-cream disabled:cursor-not-allowed"></textarea>
                            <button type="submit" :disabled="!activeConv?.is_mine || sending || !replyText.trim()"
                                    class="btn-coral rounded-pill px-4 py-2.5 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!sending">↑</span>
                                <span x-show="sending">…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </main>
    </div>

    <style>
        .pulse-dot { animation: pulse 1.6s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }

        /* Pulse subtil pe rândurile needs_human ca să atragă privirea. */
        .animate-needs-pulse { animation: needs-pulse 2s ease-in-out infinite; }
        @keyframes needs-pulse {
            0%, 100% { background-color: rgba(220, 38, 38, 0.06); }
            50%      { background-color: rgba(220, 38, 38, 0.14); }
        }
    </style>
</div>

<script>
window.SAMBLA_VAPID_PUBLIC_KEY = @json($vapidPublicKey);
window.SAMBLA_USER_ID = {{ auth()->id() }};

function operatorConsole() {
    return {
        conversations: [],
        counters: { mine: 0, unassigned: 0, needs_human: 0 },
        filter: 'all',
        activeId: null,
        messages: [],
        loadingMsgs: false,
        replyText: '',
        sending: false,
        loading: true,
        lastUpdated: '',
        feedTimer: null,
        msgsTimer: null,
        pushStatus: 'default',

        async init() {
            await this.loadFeed();
            this.feedTimer = setInterval(() => this.loadFeed(), 5000);
            this.checkPush();
            await this.registerSW();
        },

        get filteredConvs() {
            let list;
            if (this.filter === 'mine') list = this.conversations.filter(c => c.is_mine);
            else if (this.filter === 'unassigned') list = this.conversations.filter(c => c.is_unassigned);
            else if (this.filter === 'needs_human') list = this.conversations.filter(c => c.needs_human);
            else list = [...this.conversations];

            // Sort: needs_human (unclaimed) primii, apoi după activitate.
            // Operatorul vede instant ce trebuie atins ACUM, fără să
            // caute prin listă; comparable nu schimbă ordinea când nimic
            // nu cere atenție urgentă.
            return list.sort((a, b) => {
                const aPriority = (a.needs_human && !a.is_mine) ? 1 : 0;
                const bPriority = (b.needs_human && !b.is_mine) ? 1 : 0;
                if (aPriority !== bPriority) return bPriority - aPriority;
                return new Date(b.last_activity || 0) - new Date(a.last_activity || 0);
            });
        },

        get activeConv() {
            return this.conversations.find(c => c.id === this.activeId);
        },

        async loadFeed() {
            try {
                const r = await fetch('/dashboard/operator/feed', { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                this.conversations = d.conversations || [];
                this.counters = d.counters || this.counters;
                const now = new Date();
                this.lastUpdated = now.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                this.loading = false;
            } catch (e) {}
        },

        async open(id) {
            this.activeId = id;
            this.messages = [];
            this.loadingMsgs = true;
            await this.loadMessages();
            this.loadingMsgs = false;
            // Poll messages every 5s while active
            if (this.msgsTimer) clearInterval(this.msgsTimer);
            this.msgsTimer = setInterval(() => this.loadMessages(true), 5000);
        },

        async loadMessages(silent = false) {
            if (!this.activeId) return;
            try {
                const r = await fetch('/dashboard/operator/conv/' + this.activeId, { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                const newCount = d.messages.length;
                const oldCount = this.messages.length;
                this.messages = d.messages;
                if (newCount > oldCount && !silent) {
                    this.scrollDown();
                } else if (newCount > oldCount) {
                    this.scrollDown();
                }
                if (!silent) this.scrollDown();
            } catch (e) {}
        },

        scrollDown() {
            this.$nextTick(() => {
                const p = this.$refs.msgPane;
                if (p) p.scrollTop = p.scrollHeight;
            });
        },

        async takeOver() {
            if (!this.activeId) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            await fetch('/dashboard/operator/conv/' + this.activeId + '/take', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
            });
            await this.loadFeed();
        },

        async release() {
            if (!this.activeId) return;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            await fetch('/dashboard/operator/conv/' + this.activeId + '/release', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
            });
            await this.loadFeed();
        },

        async sendReply() {
            const text = this.replyText.trim();
            if (!text || this.sending) return;
            this.sending = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/operator/conv/' + this.activeId + '/reply', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ content: text }),
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const d = await r.json();
                if (d.message) {
                    this.messages.push({
                        id: d.message.id,
                        role: 'bot',
                        content: d.message.content,
                        at_relative: 'acum',
                        is_operator: true,
                    });
                    this.scrollDown();
                }
                this.replyText = '';
            } catch (e) {
                alert('Eroare la trimitere: ' + e.message);
            } finally {
                this.sending = false;
            }
        },

        // ─── Push notifications ────────────────────────────────

        checkPush() {
            if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                this.pushStatus = 'unsupported';
                return;
            }
            this.pushStatus = Notification.permission;
        },

        async registerSW() {
            if (!('serviceWorker' in navigator)) return;
            try {
                await navigator.serviceWorker.register('/sw-operator.js', { scope: '/dashboard/operator' });
            } catch (e) { console.warn('SW register failed', e); }
        },

        async enablePush() {
            try {
                const perm = await Notification.requestPermission();
                this.pushStatus = perm;
                if (perm !== 'granted') return;

                const reg = await navigator.serviceWorker.ready;
                let sub = await reg.pushManager.getSubscription();
                if (!sub) {
                    sub = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: this.urlBase64ToUint8Array(window.SAMBLA_VAPID_PUBLIC_KEY),
                    });
                }

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                await fetch('/dashboard/operator/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        endpoint: sub.endpoint,
                        keys: {
                            p256dh: this.arrayBufferToBase64(sub.getKey('p256dh')),
                            auth: this.arrayBufferToBase64(sub.getKey('auth')),
                        },
                        label: navigator.platform || 'web',
                    }),
                });
            } catch (e) {
                alert('Activare push eșuată: ' + e.message);
            }
        },

        async testPush() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const r = await fetch('/dashboard/operator/push/test', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const d = await r.json();
            if (d.sent_to_devices === 0) alert('Nicio device înregistrată — re-activează push.');
        },

        urlBase64ToUint8Array(base64) {
            const padding = '='.repeat((4 - base64.length % 4) % 4);
            const base64Std = (base64 + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const raw = atob(base64Std);
            const out = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; ++i) out[i] = raw.charCodeAt(i);
            return out;
        },

        arrayBufferToBase64(buf) {
            const bytes = new Uint8Array(buf);
            let bin = '';
            for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
            return btoa(bin);
        },
    };
}
</script>
@endsection
