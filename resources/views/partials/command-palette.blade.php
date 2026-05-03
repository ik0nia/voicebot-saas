{{-- ⌘K command palette — descoperire features rapidă pentru power users
     și newbie-friendly (tastezi „adaug agent" → CTA direct).

     Lazy-mount: nu randează nimic în DOM până la prima apăsare ⌘K, ca
     să nu îngreuneze first-paint dashboard. --}}
<div x-data="commandPalette()"
     x-init="bindShortcuts()"
     @keydown.window.meta.k.prevent="open()"
     @keydown.window.ctrl.k.prevent="open()">

    <template x-if="visible">
        <div x-cloak
             class="fixed inset-0 z-[60] flex items-start justify-center pt-[15vh] px-4"
             @click.self="close()"
             @keydown.escape.window="close()">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-ink/40 backdrop-blur-sm" @click="close()"></div>

            {{-- Panel --}}
            <div class="relative w-full max-w-xl bg-paper rounded-card border border-line shadow-2xl overflow-hidden">

                {{-- Search input --}}
                <div class="flex items-center gap-3 px-4 py-3 border-b border-line">
                    <svg class="w-4 h-4 text-muted shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                    <input x-ref="search" type="text" x-model="query" @input="updateMatches()"
                           @keydown.arrow-down.prevent="moveCursor(1)"
                           @keydown.arrow-up.prevent="moveCursor(-1)"
                           @keydown.enter.prevent="executeCursor()"
                           placeholder="Caută o acțiune sau o pagină..."
                           class="flex-1 bg-transparent text-sm placeholder-muted outline-none">
                    <span class="hidden sm:inline-flex items-center gap-1 text-2xs text-muted mono px-1.5 py-0.5 rounded border border-line">esc</span>
                </div>

                {{-- Matches --}}
                <div class="max-h-[60vh] overflow-y-auto" x-ref="matchesPane">
                    <template x-if="matches.length === 0">
                        <div class="px-4 py-6 text-center text-sm text-muted">
                            Niciun rezultat. Încearcă: <em>agent</em>, <em>inbox</em>, <em>facturare</em>, <em>webhook</em>.
                        </div>
                    </template>

                    <template x-for="(match, idx) in matches" :key="match.id">
                        <a :href="match.href"
                           @click="close()"
                           @mouseenter="cursor = idx"
                           :class="cursor === idx ? 'bg-cream' : ''"
                           class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-cream transition-colors border-b border-line/40 last:border-b-0">
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                  :class="match.tint || 'bg-cream text-inkSoft'">
                                <span x-html="match.icon" class="w-4 h-4 inline-flex"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-ink truncate" x-text="match.title"></div>
                                <div class="text-2xs text-muted truncate" x-text="match.subtitle"></div>
                            </div>
                            <span x-show="match.shortcut" class="hidden sm:inline-flex items-center gap-0.5 text-2xs text-muted mono px-1.5 py-0.5 rounded border border-line"
                                  x-text="match.shortcut"></span>
                        </a>
                    </template>
                </div>

                {{-- Footer hint --}}
                <div class="px-4 py-2 border-t border-line bg-cream flex items-center gap-3 text-2xs text-muted">
                    <span class="inline-flex items-center gap-1"><kbd class="mono px-1 py-0.5 rounded border border-line">↑↓</kbd> navigare</span>
                    <span class="inline-flex items-center gap-1"><kbd class="mono px-1 py-0.5 rounded border border-line">↵</kbd> deschide</span>
                    <span class="inline-flex items-center gap-1"><kbd class="mono px-1 py-0.5 rounded border border-line">esc</kbd> închide</span>
                    <span class="ml-auto opacity-60">Sambla command palette</span>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function commandPalette() {
    return {
        visible: false,
        query: '',
        cursor: 0,
        matches: [],

        actions: [
            // Pagini principale
            { id: 'p_dashboard', title: 'Dashboard', subtitle: 'Pagina de start', href: '/dashboard',
              tint: 'bg-coralsoft text-coralh', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>' },
            { id: 'p_agenti', title: 'Agenți AI', subtitle: 'Lista agenților', href: '/dashboard/agenti',
              tint: 'bg-coralsoft text-coralh', icon: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>' },
            { id: 'p_inbox', title: 'Inbox', subtitle: 'Conversații pe toate canalele', href: '/dashboard/inbox',
              tint: 'bg-[#DCEBFA] text-[#1E40AF]', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2"/></svg>' },
            { id: 'p_apeluri', title: 'Apeluri', subtitle: 'Istoric apeluri telefonice', href: '/dashboard/apeluri',
              tint: 'bg-[#DCEBFA] text-[#1E40AF]', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>' },
            { id: 'p_leads', title: 'Leads', subtitle: 'Pipeline lead-uri', href: '/dashboard/leads',
              tint: 'bg-[#FDE2D0] text-[#9A3412]', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v6M12 22v-6"/></svg>' },
            { id: 'p_callbacks', title: 'Programări (callbacks)', subtitle: 'Cereri de a fi sunat', href: '/dashboard/callbacks',
              tint: 'bg-[#D7EFE0] text-[#047857]', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
            { id: 'p_analiza', title: 'Analiză', subtitle: 'Statistici și grafice', href: '/dashboard/analiza',
              tint: 'bg-[#E6DFF3] text-[#5B21B6]', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zM15 19V9a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
            { id: 'p_numere', title: 'Numere telefon', subtitle: 'Numerele tale Twilio/Telnyx', href: '/dashboard/numere',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>' },
            { id: 'p_sites', title: 'Site-uri', subtitle: 'Domeniile conectate', href: '/dashboard/sites',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>' },
            { id: 'p_echipa', title: 'Echipă', subtitle: 'Membri și roluri', href: '/dashboard/echipa',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/></svg>' },
            { id: 'p_facturare', title: 'Facturare', subtitle: 'Plan, facturi, top-up', href: '/dashboard/facturare',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>' },
            { id: 'p_setari', title: 'Setări', subtitle: 'Profil, organizație', href: '/dashboard/setari',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/></svg>' },
            { id: 'p_activitate', title: 'Activitate (audit log)', subtitle: 'Cine a editat ce, când', href: '/dashboard/activitate',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            { id: 'p_webhooks', title: 'Webhooks', subtitle: 'Integrări outbound', href: '/dashboard/webhooks',
              tint: 'bg-coralsoft text-coralh', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>' },
            { id: 'p_canale', title: 'Toate canalele', subtitle: 'WhatsApp · Facebook · Instagram per agent', href: '/dashboard/canale',
              tint: 'bg-[#D7EFE0] text-emerald-700', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2"/></svg>',
              keywords: 'canal channel whatsapp facebook instagram meta connect conecta' },
            // Acțiuni
            { id: 'a_new_agent', title: 'Creează agent AI nou', subtitle: 'Configurează un agent vocal/chat', href: '/dashboard/agenti/nou',
              tint: 'bg-coral text-cream', icon: '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>',
              keywords: 'add adaug new nou bot' },
            { id: 'a_new_site', title: 'Adaugă site', subtitle: 'Verifică un domeniu nou', href: '/dashboard/sites/new',
              tint: 'bg-coral text-cream', icon: '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>',
              keywords: 'add adaug new site domeniu' },
            { id: 'a_new_webhook', title: 'Configurează webhook', subtitle: 'Trimite evenimente la sistemul tău', href: '/dashboard/webhooks/nou',
              tint: 'bg-coral text-cream', icon: '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>',
              keywords: 'add adaug new webhook integrare crm' },
            { id: 'a_invite', title: 'Invită un coleg', subtitle: 'Adaugă membru în echipă', href: '/dashboard/echipa',
              tint: 'bg-coral text-cream', icon: '<svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>',
              keywords: 'add adaug invita coleg user' },
            { id: 'a_logout', title: 'Deconectare', subtitle: 'Ieși din cont', href: '#',
              tint: 'bg-cream text-inkSoft', icon: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>',
              keywords: 'logout iesi exit' },
        ],

        bindShortcuts() {
            this.matches = this.actions.slice(0, 10);
        },

        open() {
            this.visible = true;
            this.cursor = 0;
            this.query = '';
            this.matches = this.actions.slice(0, 10);
            this.$nextTick(() => this.$refs.search?.focus());
        },

        close() {
            this.visible = false;
        },

        normalize(s) {
            return (s || '').toString().toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, ''); // strip diacritics
        },

        updateMatches() {
            const q = this.normalize(this.query).trim();
            if (!q) {
                this.matches = this.actions.slice(0, 10);
                this.cursor = 0;
                return;
            }
            const terms = q.split(/\s+/);
            const scored = this.actions.map(a => {
                const haystack = this.normalize([a.title, a.subtitle, a.keywords].join(' '));
                let score = 0;
                for (const t of terms) {
                    if (haystack.includes(t)) score += t.length;
                    // Bonus for matching at the start of title
                    if (this.normalize(a.title).startsWith(t)) score += 10;
                }
                return { a, score };
            }).filter(s => s.score > 0)
              .sort((x, y) => y.score - x.score)
              .map(s => s.a);
            this.matches = scored.slice(0, 12);
            this.cursor = 0;
        },

        moveCursor(delta) {
            if (this.matches.length === 0) return;
            this.cursor = (this.cursor + delta + this.matches.length) % this.matches.length;
            this.$nextTick(() => {
                const el = this.$refs.matchesPane?.children[this.cursor];
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },

        executeCursor() {
            const m = this.matches[this.cursor];
            if (!m) return;
            if (m.id === 'a_logout') {
                // Submit logout form
                const f = document.createElement('form');
                f.method = 'POST'; f.action = '/logout';
                const t = document.createElement('input');
                t.type = 'hidden'; t.name = '_token';
                t.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
                f.appendChild(t);
                document.body.appendChild(f); f.submit();
                return;
            }
            window.location.href = m.href;
        },
    };
}
</script>
