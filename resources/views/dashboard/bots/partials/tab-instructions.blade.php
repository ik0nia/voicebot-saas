{{-- Tab: Instructiuni --}}
<div x-data="instructionsTab()" class="space-y-6">

    {{-- Step 1: Template Grid OR Custom --}}
    <div x-show="step === 'choose'" x-transition class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Alege domeniul afacerii</h2>
            <p class="text-xs text-slate-500 mt-0.5">Selecteaza un sablon pregatit sau descrie-ti afacerea pentru instruciuni personalizate.</p>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 mb-4">
                @foreach(config('industry-templates') as $key => $tpl)
                <button type="button" @click="selectTemplate('{{ $key }}')"
                    class="text-left rounded-xl border-2 border-slate-200 p-3 transition-all hover:border-blue-300 hover:shadow-sm hover:bg-blue-50/30"
                    :class="selectedTemplate === '{{ $key }}' ? 'border-blue-500 bg-blue-50 shadow-sm ring-1 ring-blue-200' : ''">
                    <div class="text-xl mb-1.5">{{ $tpl['icon'] }}</div>
                    <p class="text-xs font-semibold text-slate-900 leading-tight">{{ $tpl['label'] }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-tight line-clamp-2">{{ $tpl['description'] }}</p>
                </button>
                @endforeach
                {{-- Custom --}}
                <button type="button" @click="step = 'custom'"
                    class="text-left rounded-xl border-2 border-dashed border-slate-300 p-3 transition-all hover:border-blue-400 hover:bg-blue-50/30">
                    <div class="text-xl mb-1.5">✨</div>
                    <p class="text-xs font-semibold text-slate-900 leading-tight">Alt domeniu</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-tight">Descrie afacerea si AI genereaza</p>
                </button>
            </div>

            @if($bot->system_prompt)
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-slate-600">Instructiunile actuale ale botului:</span>
                    <button type="button" @click="step = 'edit'; prompt = currentPrompt; greeting = currentGreeting" class="text-xs text-blue-600 hover:underline font-medium">Editeaza direct &rarr;</button>
                </div>
                <div class="bg-slate-50 rounded-lg p-3 text-xs text-slate-600 max-h-24 overflow-hidden relative">
                    <div class="whitespace-pre-wrap">{{ Str::limit($bot->system_prompt, 300) }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Step: Custom AI generation --}}
    <div x-show="step === 'custom'" x-transition class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Descrie afacerea ta</h2>
                <p class="text-xs text-slate-500 mt-0.5">AI-ul va genera instructiuni personalizate pentru botul tau.</p>
            </div>
            <button type="button" @click="step = 'choose'" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Inapoi
            </button>
        </div>
        <div class="p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-800 mb-1">Numele afacerii</label>
                <input type="text" x-model="businessName" placeholder="Ex: Floraria Magnolia" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-800 mb-1">Cu ce se ocupa afacerea?</label>
                <textarea x-model="businessDesc" rows="3" placeholder="Ex: Vindem buchete de flori proaspete, aranjamente florale pentru evenimente si livram in tot orasul. Avem si plante de apartament." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-800 mb-2">Ce ton vrei sa aiba botul?</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <template x-for="t in toneOptions">
                        <label class="cursor-pointer">
                            <input type="radio" x-model="customTone" :value="t.v" class="peer sr-only">
                            <div class="rounded-lg border-2 border-slate-200 p-2.5 text-center transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-slate-300">
                                <p class="text-xs font-semibold text-slate-900" x-text="t.l"></p>
                                <p class="text-[10px] text-slate-500" x-text="t.d"></p>
                            </div>
                        </label>
                    </template>
                </div>
            </div>
            <button type="button" @click="generatePrompt()" :disabled="generating || !businessName || !businessDesc"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                <template x-if="generating"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                <template x-if="!generating"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg></template>
                <span x-text="generating ? 'Se genereaza...' : 'Genereaza cu AI'"></span>
            </button>
        </div>
    </div>

    {{-- Step: Edit prompt --}}
    <div x-show="step === 'edit'" x-transition class="space-y-4">
        {{-- Back button --}}
        <div class="flex items-center justify-between">
            <button type="button" @click="step = 'choose'" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Schimba sablonul
            </button>
        </div>

        {{-- Greeting --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <label class="block text-sm font-medium text-slate-800 mb-1">Mesaj de intampinare</label>
            <p class="text-[11px] text-slate-500 mb-2">Primul mesaj pe care il vede clientul cand deschide chat-ul.</p>
            <input type="text" x-model="greeting" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Ex: Buna! Cu ce te pot ajuta?">
        </div>

        {{-- System prompt --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <label class="block text-sm font-medium text-slate-800">Instructiuni pentru bot</label>
                    <p class="text-[11px] text-slate-500">Regulile pe care le urmeaza botul in fiecare conversatie.</p>
                </div>
                <span class="text-[10px] text-slate-400" x-text="prompt.length + ' caractere'"></span>
            </div>
            <textarea x-model="prompt" rows="14" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-mono leading-relaxed focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Descrie cum se comporta botul..."></textarea>
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-3">
            <button type="button" @click="saveAll()" :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-900 disabled:opacity-50 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <span x-text="saving ? 'Se salveaza...' : 'Salveaza instructiunile'"></span>
            </button>
            <span x-show="saved" x-transition class="text-sm text-emerald-600 font-medium">Salvat!</span>
        </div>
    </div>
</div>

<script>
function instructionsTab() {
    return {
        step: @js($bot->system_prompt ? 'edit' : 'choose'),
        selectedTemplate: null,
        currentPrompt: @js($bot->system_prompt ?? ''),
        currentGreeting: @js($bot->greeting_message ?? ''),
        prompt: @js($bot->system_prompt ?? ''),
        greeting: @js($bot->greeting_message ?? ''),
        businessName: @js($bot->name ?? ''),
        businessDesc: '',
        customTone: 'friendly',
        generating: false,
        saving: false,
        saved: false,
        templates: @js(config('industry-templates')),
        toneOptions: [
            {v:'professional',l:'Profesional',d:'Formal, serios'},
            {v:'friendly',l:'Prietenos',d:'Cald, apropiabil'},
            {v:'casual',l:'Relaxat',d:'Informal, direct'},
            {v:'technical',l:'Tehnic',d:'Precis, detaliat'}
        ],

        selectTemplate(key) {
            this.selectedTemplate = key;
            var tpl = this.templates[key];
            if (!tpl) return;
            var name = @js($bot->name);
            this.prompt = tpl.prompt.replace(/\{business_name\}/g, name);
            this.greeting = tpl.greeting.replace(/\{business_name\}/g, name);
            this.step = 'edit';
        },

        async generatePrompt() {
            if (!this.businessName || !this.businessDesc) return;
            this.generating = true;
            try {
                var resp = await fetch(@js(route('dashboard.setup.generatePrompt')), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json'},
                    body: JSON.stringify({ business_name: this.businessName, business_description: this.businessDesc, business_type: 'hybrid', tone: this.customTone })
                });
                var data = await resp.json();
                if (data.prompt) this.prompt = data.prompt;
                if (data.greeting) this.greeting = data.greeting;
                this.step = 'edit';
            } catch(e) { alert('Eroare la generare. Incearca din nou.'); }
            this.generating = false;
        },

        async saveAll() {
            this.saving = true; this.saved = false;
            try {
                await fetch(@js(route('dashboard.bots.updateField', $bot)), {
                    method: 'PATCH', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json'},
                    body: JSON.stringify({field: 'system_prompt', value: this.prompt})
                });
                if (this.greeting) {
                    await fetch(@js(route('dashboard.bots.updateField', $bot)), {
                        method: 'PATCH', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json'},
                        body: JSON.stringify({field: 'greeting_message', value: this.greeting})
                    });
                }
                this.currentPrompt = this.prompt;
                this.currentGreeting = this.greeting;
                this.saved = true;
                if (typeof showToast === 'function') showToast('Instructiunile au fost salvate!');
                setTimeout(() => this.saved = false, 3000);
            } catch(e) { alert('Eroare la salvare.'); }
            this.saving = false;
        }
    };
}
</script>
