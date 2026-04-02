@php $policy = $policy ?? new \App\Models\ConversationPolicy(); @endphp
{{-- Personality & Behavior Settings — fully interactive via Alpine.js --}}
<div x-data="personalitySettings()" class="space-y-6">

    {{-- Tone --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-l-4 border-red-500 p-5">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Tonul conversatiei</label>
            <p class="text-xs text-slate-500 mb-4">Cum se adreseaza botul clientilor tai?</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="t in tones">
                    <button type="button" @click="form.tone = t.v; dirty = true"
                        :class="form.tone === t.v ? 'border-red-400 bg-red-50/60 shadow-md ring-1 ring-red-200 scale-[1.02]' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm'"
                        class="text-left rounded-xl border-2 p-4 transition-all duration-200">
                        <div class="flex items-center gap-2.5 mb-2">
                            <div class="w-4 h-4 rounded-full shadow-sm" :class="t.color"></div>
                            <span class="text-lg" x-text="t.emoji"></span>
                        </div>
                        <p class="text-sm font-bold text-slate-900" x-text="t.l"></p>
                        <p class="text-[11px] text-slate-500 leading-tight mt-1" x-text="t.d"></p>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Verbosity Slider --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-l-4 border-blue-500 p-5">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Lungimea raspunsurilor</label>
            <p class="text-xs text-slate-500 mb-5">Cat de detaliate sunt raspunsurile?</p>
            <div class="relative px-4">
                <div class="h-4 rounded-full bg-slate-200 relative overflow-hidden shadow-inner">
                    <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-300" :style="'width:' + (verbIdx * 50) + '%; background:' + verbLabels[verbIdx][2]"></div>
                </div>
                <input type="range" min="0" max="2" step="1" x-model="verbIdx" @input="form.verbosity = ['concise','detailed','verbose'][verbIdx]; dirty = true" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" style="margin:0">
                <div class="flex justify-between mt-3">
                    <template x-for="(l, i) in verbLabels">
                        <button type="button" @click="verbIdx = i; form.verbosity = ['concise','detailed','verbose'][i]; dirty = true" class="flex flex-col items-center gap-1.5 transition-all" :class="verbIdx === i ? 'opacity-100' : 'opacity-40 hover:opacity-70'">
                            <div class="w-5 h-5 rounded-full border-2 transition-all shadow-sm" :style="verbIdx >= i ? 'background:' + verbLabels[i][2] + ';border-color:' + verbLabels[i][2] : ''" :class="verbIdx < i ? 'border-slate-300 bg-white' : ''"></div>
                            <span class="text-[10px] font-medium text-slate-600" x-text="['Scurt', 'Echilibrat', 'Detaliat'][i]"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="mt-4 px-4 py-3 rounded-lg transition-all duration-300 border" :style="'background:' + verbLabels[verbIdx][2] + '15; border-color:' + verbLabels[verbIdx][2] + '30'">
                <p class="text-sm font-semibold" :style="'color:' + verbLabels[verbIdx][2]" x-text="verbLabels[verbIdx][0]"></p>
                <p class="text-xs text-slate-600 mt-0.5" x-text="verbLabels[verbIdx][1]"></p>
            </div>
        </div>
    </div>

    {{-- Emoji Toggle --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-l-4 border-purple-500 px-5 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Foloseste emoji-uri</p>
                    <p class="text-xs text-slate-500 mt-0.5">Botul va adauga emoji-uri ocazional pentru un ton mai prietenos.</p>
                </div>
                <button type="button" @click="form.emoji_allowed = !form.emoji_allowed; dirty = true"
                        :class="form.emoji_allowed ? 'bg-purple-500' : 'bg-slate-300'"
                        class="relative w-16 h-9 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:ring-offset-1 flex-shrink-0">
                    <div class="absolute top-1 left-1 w-7 h-7 bg-white rounded-full shadow-md transition-transform duration-200 flex items-center justify-center"
                         :class="form.emoji_allowed ? 'translate-x-7' : ''">
                        <span class="text-sm" x-text="form.emoji_allowed ? '😊' : '😐'"></span>
                    </div>
                </button>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg px-4 py-3 transition-all duration-200"
                     :class="form.emoji_allowed ? 'bg-purple-50 border border-purple-200 shadow-sm' : 'bg-slate-50 border border-slate-200'">
                    <p class="text-[10px] font-semibold uppercase tracking-wider mb-1" :class="form.emoji_allowed ? 'text-purple-600' : 'text-slate-400'">Cu emoji</p>
                    <p class="text-sm text-slate-700">Buna! 👋 Cu ce te pot ajuta?</p>
                </div>
                <div class="rounded-lg px-4 py-3 transition-all duration-200"
                     :class="!form.emoji_allowed ? 'bg-purple-50 border border-purple-200 shadow-sm' : 'bg-slate-50 border border-slate-200'">
                    <p class="text-[10px] font-semibold uppercase tracking-wider mb-1" :class="!form.emoji_allowed ? 'text-purple-600' : 'text-slate-400'">Fara emoji</p>
                    <p class="text-sm text-slate-700">Buna! Cu ce te pot ajuta?</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Aggressiveness --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-l-4 border-amber-500 p-5">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Cat de insistent vinde botul?</label>
            <p class="text-xs text-slate-500 mb-5">Cat de des sugereaza produse sau actiuni de cumparare?</p>
            <div class="relative px-4">
                <div class="h-4 rounded-full bg-slate-200 relative overflow-hidden shadow-inner">
                    <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-300" :style="'width:' + (ctaIdx * 50) + '%; background:' + ctaLabels[ctaIdx][2]"></div>
                </div>
                <input type="range" min="0" max="2" step="1" x-model="ctaIdx" @input="form.cta_aggressiveness = ['soft','moderate','aggressive'][ctaIdx]; dirty = true" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" style="margin:0">
                <div class="flex justify-between mt-3">
                    <template x-for="(l, i) in ctaLabels">
                        <button type="button" @click="ctaIdx = i; form.cta_aggressiveness = ['soft','moderate','aggressive'][i]; dirty = true" class="flex flex-col items-center gap-1.5 transition-all" :class="ctaIdx === i ? 'opacity-100' : 'opacity-40 hover:opacity-70'">
                            <div class="w-5 h-5 rounded-full border-2 transition-all shadow-sm" :style="ctaIdx >= i ? 'background:' + ctaLabels[i][2] + ';border-color:' + ctaLabels[i][2] : ''" :class="ctaIdx < i ? 'border-slate-300 bg-white' : ''"></div>
                            <span class="text-[10px] font-medium text-slate-600" x-text="['Subtil', 'Echilibrat', 'Proactiv'][i]"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="mt-4 px-4 py-3 rounded-lg transition-all duration-300 border" :style="'background:' + ctaLabels[ctaIdx][2] + '15; border-color:' + ctaLabels[ctaIdx][2] + '30'">
                <p class="text-sm font-semibold" :style="'color:' + ctaLabels[ctaIdx][2]" x-text="ctaLabels[ctaIdx][0]"></p>
                <p class="text-xs text-slate-600 mt-0.5" x-text="ctaLabels[ctaIdx][1]"></p>
            </div>
        </div>
    </div>

    {{-- Lead Capture --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="border-l-4 border-emerald-500 p-5">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Capturare date de contact</label>
            <p class="text-xs text-slate-500 mb-5">Cat de insistent cere botul emailul sau telefonul?</p>
            <div class="relative px-4">
                <div class="h-4 rounded-full bg-slate-200 relative overflow-hidden shadow-inner">
                    <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-300" :style="'width:' + (leadIdx * 50) + '%; background:' + leadLabels[leadIdx][2]"></div>
                </div>
                <input type="range" min="0" max="2" step="1" x-model="leadIdx" @input="form.lead_aggressiveness = ['soft','moderate','aggressive'][leadIdx]; dirty = true" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" style="margin:0">
                <div class="flex justify-between mt-3">
                    <template x-for="(l, i) in leadLabels">
                        <button type="button" @click="leadIdx = i; form.lead_aggressiveness = ['soft','moderate','aggressive'][i]; dirty = true" class="flex flex-col items-center gap-1.5 transition-all" :class="leadIdx === i ? 'opacity-100' : 'opacity-40 hover:opacity-70'">
                            <div class="w-5 h-5 rounded-full border-2 transition-all shadow-sm" :style="leadIdx >= i ? 'background:' + leadLabels[i][2] + ';border-color:' + leadLabels[i][2] : ''" :class="leadIdx < i ? 'border-slate-300 bg-white' : ''"></div>
                            <span class="text-[10px] font-medium text-slate-600" x-text="['Discret', 'Moderat', 'Insistent'][i]"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="mt-4 px-4 py-3 rounded-lg transition-all duration-300 border" :style="'background:' + leadLabels[leadIdx][2] + '15; border-color:' + leadLabels[leadIdx][2] + '30'">
                <p class="text-sm font-semibold" :style="'color:' + leadLabels[leadIdx][2]" x-text="leadLabels[leadIdx][0]"></p>
                <p class="text-xs text-slate-600 mt-0.5" x-text="leadLabels[leadIdx][1]"></p>
            </div>
        </div>
    </div>

    {{-- Custom Messages --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <button type="button" @click="showCustom = !showCustom" class="w-full px-5 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                </div>
                <span class="text-sm font-semibold text-slate-700">Mesaje personalizate</span>
            </div>
            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="showCustom ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div x-show="showCustom" x-transition class="px-5 pb-5 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Cand nu stie raspunsul</label>
                <input type="text" x-model="form.fallback_message" @input="dirty = true" placeholder="Ex: Nu am gasit informatia, dar te pot pune in legatura cu echipa noastra." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Cand transfera la om</label>
                <input type="text" x-model="form.escalation_message" @input="dirty = true" placeholder="Ex: Te transfer catre un coleg care te poate ajuta mai bine." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400">
            </div>
        </div>
    </div>

    {{-- Save Bar (sticky when dirty) --}}
    <div x-show="dirty" x-transition class="sticky bottom-4 z-10">
        <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-red-600 to-red-500 text-white px-5 py-3 shadow-lg shadow-red-500/20">
            <span class="text-sm font-medium">Ai modificari nesalvate</span>
            <div class="flex items-center gap-3">
                <button type="button" @click="resetForm()" class="text-sm text-red-200 hover:text-white transition-colors">Anuleaza</button>
                <button type="button" @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors shadow-sm">
                    <template x-if="saving"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                    <template x-if="!saving"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></template>
                    <span x-text="saving ? 'Se salveaza...' : 'Salveaza'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function personalitySettings() {
    @php
        $verbMap = ['concise' => 0, 'detailed' => 1, 'verbose' => 2];
        $ctaMap = ['soft' => 0, 'moderate' => 1, 'aggressive' => 2];
        $leadMap = ['soft' => 0, 'moderate' => 1, 'aggressive' => 2];
    @endphp
    var initial = {
        tone: @js($policy->tone ?? 'professional'),
        verbosity: @js($policy->verbosity ?? 'concise'),
        emoji_allowed: @js((bool)($policy->emoji_allowed ?? false)),
        cta_aggressiveness: @js($policy->cta_aggressiveness ?? 'moderate'),
        lead_aggressiveness: @js($policy->lead_aggressiveness ?? 'soft'),
        fallback_message: @js($policy->fallback_message ?? ''),
        escalation_message: @js($policy->escalation_message ?? ''),
    };

    return {
        form: JSON.parse(JSON.stringify(initial)),
        dirty: false,
        saving: false,
        showCustom: false,
        verbIdx: @js($verbMap[$policy->verbosity ?? 'concise'] ?? 0),
        ctaIdx: @js($ctaMap[$policy->cta_aggressiveness ?? 'moderate'] ?? 1),
        leadIdx: @js($leadMap[$policy->lead_aggressiveness ?? 'soft'] ?? 0),

        tones: [
            {v:'professional', l:'Profesional', d:'Formal si respectuos', color:'bg-blue-500', emoji:'\uD83D\uDC54'},
            {v:'friendly', l:'Prietenos', d:'Cald si apropiabil', color:'bg-emerald-500', emoji:'\uD83D\uDE0A'},
            {v:'casual', l:'Relaxat', d:'Informal si direct', color:'bg-amber-500', emoji:'\u270C\uFE0F'},
            {v:'technical', l:'Tehnic', d:'Precis cu termeni de specialitate', color:'bg-purple-500', emoji:'\uD83D\uDD27'},
        ],
        verbLabels: [
            ['Scurt si la obiect', 'Raspunsuri directe, fara detalii extra.', '#3b82f6'],
            ['Echilibrat', 'Raspunsuri complete cu explicatii scurte.', '#10b981'],
            ['Detaliat', 'Explica pe larg cu context si exemple.', '#8b5cf6'],
        ],
        ctaLabels: [
            ['Subtil', 'Recomanda doar cand clientul cere explicit.', '#10b981'],
            ['Echilibrat', 'Sugereaza natural in conversatie.', '#3b82f6'],
            ['Proactiv', 'Recomanda activ produse si oferte.', '#f59e0b'],
        ],
        leadLabels: [
            ['Discret', 'Cere date doar cand clientul ofera voluntar.', '#10b981'],
            ['Moderat', 'Sugereaza sa lase datele dupa cateva mesaje.', '#3b82f6'],
            ['Insistent', 'Cere datele de contact devreme in conversatie.', '#f59e0b'],
        ],

        resetForm() {
            this.form = JSON.parse(JSON.stringify(initial));
            this.verbIdx = @js($verbMap[$policy->verbosity ?? 'concise'] ?? 0);
            this.ctaIdx = @js($ctaMap[$policy->cta_aggressiveness ?? 'moderate'] ?? 1);
            this.leadIdx = @js($leadMap[$policy->lead_aggressiveness ?? 'soft'] ?? 0);
            this.dirty = false;
        },

        async save() {
            this.saving = true;
            try {
                var resp = await fetch(@js(route('dashboard.bots.updatePolicy', $bot)), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json'},
                    body: JSON.stringify(this.form)
                });
                if (resp.ok) {
                    this.dirty = false;
                    if (typeof showToast === 'function') showToast('Personalitate salvata!');
                } else {
                    throw new Error('Save failed');
                }
            } catch(e) {
                if (typeof showToast === 'function') showToast('Eroare la salvare');
            }
            this.saving = false;
        }
    };
}
</script>
