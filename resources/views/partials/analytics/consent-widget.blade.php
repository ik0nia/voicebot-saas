{{--
  Cookie consent widget (Consent Mode v2). Alpine-driven, renders
  a bottom banner on first visit and a reopenable settings modal
  with granular per-category toggles.
--}}
@php
    $cfg = app(\App\Services\Analytics\AnalyticsConfig::class);
    $enabled = (bool) $cfg->get('consent_enabled', true) && $cfg->isEnabled();
    $version = config('analytics.consent_version', '1.0');
    $categories = $cfg->consentCategories();
@endphp
@if($enabled)
{{-- Alpine.js — public layout doesn't bundle it, dashboard/admin do.
     Loading a second time on dashboard/admin is harmless; Alpine's
     own guard against double-init means we only pay the fetch cost
     once thanks to browser caching. --}}
<script>
    if (!window.Alpine && !document.getElementById('sambla-alpine-cdn')) {
        var _s = document.createElement('script');
        _s.id = 'sambla-alpine-cdn';
        _s.defer = true;
        _s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js';
        document.head.appendChild(_s);
    }
</script>
<div x-data="samblaConsent({
        version: @js($version),
        categories: @js($categories),
        csrfToken: @js(csrf_token()),
        endpoint: '{{ route('consent.store') }}'
    })"
     x-init="init()"
     x-cloak
     class="font-sans">

    {{-- Banner --}}
    <div x-show="showBanner"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="fixed bottom-0 inset-x-0 z-[9998] p-3 md:p-4">
        <div class="max-w-4xl mx-auto bg-white shadow-2xl rounded-2xl border border-slate-200 p-4 md:p-5">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Respectăm confidențialitatea ta</h2>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                        Folosim cookie-uri pentru a funcționa corect, a măsura traficul și a-ți oferi reclame relevante.
                        Poți accepta toate, respinge toate sau alege ce ne permiți.
                        <a href="/politica-de-confidentialitate" class="text-red-700 hover:underline">Detalii</a>.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                <button type="button" @click="openSettings()"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                    Setări
                </button>
                <button type="button" @click="rejectAll()"
                        class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">
                    Respinge tot
                </button>
                <button type="button" @click="acceptAll()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-700 hover:bg-red-800 rounded-lg shadow-sm transition-colors">
                    Accept tot
                </button>
            </div>
        </div>
    </div>

    {{-- Modal granular --}}
    <div x-show="showModal"
         x-transition.opacity
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60"
         @click.self="showModal = false">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Preferințe cookie-uri</h2>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                <p class="text-sm text-slate-600">
                    Controlezi exact ce categorii de cookie-uri sunt permise.
                    Categoria „necesare" nu poate fi dezactivată — fără ea site-ul nu mai funcționează.
                </p>

                {{-- Necesare --}}
                <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="shrink-0 mt-0.5">
                        <div class="w-10 h-6 bg-slate-400 rounded-full flex items-center justify-end px-0.5 cursor-not-allowed opacity-70">
                            <div class="w-5 h-5 bg-white rounded-full shadow"></div>
                        </div>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-semibold text-slate-900">Necesare <span class="font-normal text-slate-500">(mereu active)</span></p>
                        <p class="mt-0.5 text-slate-600">Sesiune, autentificare, CSRF, preferințe de limbă. Fără ele nu te poți loga sau trimite formulare.</p>
                    </div>
                </div>

                {{-- Analytics --}}
                <label class="flex items-start gap-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 cursor-pointer">
                    <div class="shrink-0 mt-0.5">
                        <input type="checkbox" x-model="choice.analytics" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-300 peer-checked:bg-red-600 rounded-full relative transition-colors">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"
                                 :class="choice.analytics ? 'translate-x-4' : ''"></div>
                        </div>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-semibold text-slate-900">Analitice</p>
                        <p class="mt-0.5 text-slate-600">Ne ajută să înțelegem ce funcționează: ce pagini vizitezi, cât stai, de unde ai venit. Anonim, fără profilare individuală.</p>
                    </div>
                </label>

                {{-- Marketing --}}
                <label class="flex items-start gap-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 cursor-pointer">
                    <div class="shrink-0 mt-0.5">
                        <input type="checkbox" x-model="choice.marketing" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-300 peer-checked:bg-red-600 rounded-full relative transition-colors">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                 :class="choice.marketing ? 'translate-x-4' : ''"></div>
                        </div>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-semibold text-slate-900">Marketing</p>
                        <p class="mt-0.5 text-slate-600">Reclame relevante pe Google, Meta și alte rețele. Măsurăm conversiile ca să nu-ți arătăm aceeași reclamă după ce te-ai înregistrat deja.</p>
                    </div>
                </label>

                {{-- Personalizare --}}
                <label class="flex items-start gap-3 p-4 bg-white rounded-xl border border-slate-200 hover:border-slate-300 cursor-pointer">
                    <div class="shrink-0 mt-0.5">
                        <input type="checkbox" x-model="choice.personalization" class="sr-only peer">
                        <div class="w-10 h-6 bg-slate-300 peer-checked:bg-red-600 rounded-full relative transition-colors">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                 :class="choice.personalization ? 'translate-x-4' : ''"></div>
                        </div>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-semibold text-slate-900">Personalizare</p>
                        <p class="mt-0.5 text-slate-600">Reținem preferințele tale (temă, layout, recomandări) ca să nu reconfigurăm totul la fiecare vizită.</p>
                    </div>
                </label>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" @click="rejectAll()"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg">
                    Respinge tot
                </button>
                <button type="button" @click="savePreferences()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-700 hover:bg-red-800 rounded-lg shadow-sm">
                    Salvează preferințele
                </button>
            </div>
        </div>
    </div>

    {{-- Floating "cookie settings" trigger (bottom-left) — GDPR
         requires an always-accessible way to change consent.  --}}
    <button x-show="!showBanner && !showModal" @click="openSettings()"
            class="fixed bottom-4 left-4 z-[9997] w-11 h-11 bg-white shadow-lg rounded-full border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors"
            aria-label="Setări cookie-uri">
        <svg class="w-5 h-5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 12c0 5.385-4.365 9.75-9.75 9.75a9.75 9.75 0 01-9.75-9.75A9.75 9.75 0 0112 2.25c.79 0 1.56.094 2.297.271a.75.75 0 01.492 1.042 3.75 3.75 0 003.908 5.36.75.75 0 01.97.74A9.75 9.75 0 0121.75 12z"/>
        </svg>
    </button>
</div>

<script>
    // Alpine component — tiny but opinionated. Defaults to the
    // denied-by-default Consent Mode v2 signals; the banner
    // flips signals to granted when the user opts in.
    window.samblaConsent = function(cfg) {
        return {
            version: cfg.version,
            categories: cfg.categories,
            csrfToken: cfg.csrfToken,
            endpoint: cfg.endpoint,
            showBanner: false,
            showModal: false,
            choice: { analytics: false, marketing: false, personalization: false },

            init() {
                // Show banner if no saved consent OR saved version is stale.
                let known = false;
                try {
                    const saved = JSON.parse(localStorage.getItem('sambla_consent') || 'null');
                    if (saved && saved.v === this.version) {
                        this.choice = Object.assign(this.choice, saved.categories || {});
                        known = true;
                    }
                } catch (e) {}
                this.showBanner = !known;

                // React to manual reopen triggers.
                window.addEventListener('sambla:open-consent', () => this.openSettings());
            },

            acceptAll() {
                this.choice = { analytics: true, marketing: true, personalization: true };
                this.persist('banner_accept_all');
            },

            rejectAll() {
                this.choice = { analytics: false, marketing: false, personalization: false };
                this.persist('banner_reject_all');
            },

            openSettings() {
                this.showBanner = false;
                this.showModal = true;
            },

            savePreferences() {
                this.persist('settings_modal');
                this.showModal = false;
            },

            signalsFromChoice() {
                // Map user categories → Consent Mode v2 signals.
                const cats = this.categories;
                const signals = {};
                for (const [cat, signalList] of Object.entries(cats)) {
                    const granted = cat === 'necessary' ? true : !!this.choice[cat];
                    signalList.forEach(s => { signals[s] = granted ? 'granted' : 'denied'; });
                }
                return signals;
            },

            persist(source) {
                const signals = this.signalsFromChoice();
                // Update Consent Mode — active tags re-evaluate storage access.
                if (typeof gtag === 'function') {
                    gtag('consent', 'update', signals);
                }
                // Persist locally (so returning visitors skip the banner).
                try {
                    localStorage.setItem('sambla_consent', JSON.stringify({
                        v: this.version,
                        signals,
                        categories: this.choice,
                        t: Date.now(),
                    }));
                } catch (e) {}

                this.showBanner = false;

                // Server-side audit log (GDPR proof-of-consent).
                fetch(this.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        source,
                        consent: signals,
                        page: window.location.href,
                    }),
                }).catch(() => {});
            },
        };
    };
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endif
