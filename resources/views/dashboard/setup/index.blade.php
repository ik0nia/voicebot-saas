@extends('layouts.app')

@section('title', 'Configurează Sambla')

@section('content')
<div x-data="setupWizard()" class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-2">
                <svg class="w-8 h-8" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="8" fill="#991b1b"/><path d="M18 8L26 18L18 28L10 18Z" fill="white" fill-opacity="0.9"/><path d="M18 12L22 18L18 24L14 18Z" fill="#991b1b"/></svg>
                <span class="text-xl font-bold text-slate-900">Sambla</span>
            </a>
        </div>

        {{-- Progress --}}
        <div class="flex items-center justify-center gap-2 mb-8">
            <template x-for="i in totalSteps" :key="i">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300"
                         :class="i < step ? 'bg-green-500 text-white' : (i === step ? 'bg-red-800 text-white ring-4 ring-red-100' : 'bg-slate-200 text-slate-400')">
                        <span x-show="i < step">✓</span>
                        <span x-show="i >= step" x-text="i"></span>
                    </div>
                    <div x-show="i < totalSteps" class="w-12 h-0.5 rounded" :class="i < step ? 'bg-green-500' : 'bg-slate-200'"></div>
                </div>
            </template>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">

            {{-- Step 1: Business Type --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="p-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Ce tip de afacere ai?</h2>
                <p class="text-slate-500 mb-8">Vom personaliza totul pentru tine.</p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach(config('business-presets') as $key => $preset)
                    <button @click="selectBusinessType('{{ $key }}')"
                            class="group relative p-6 rounded-xl border-2 transition-all duration-200 text-left"
                            :class="businessType === '{{ $key }}' ? 'border-red-800 bg-red-50 shadow-md' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm'">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                             :class="businessType === '{{ $key }}' ? 'bg-red-800 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'">
                            @if($key === 'ecommerce')
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                            @elseif($key === 'services')
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
                            @else
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                            @endif
                        </div>
                        <h3 class="font-bold text-slate-900 mb-1">{{ $preset['label'] }}</h3>
                        <p class="text-sm text-slate-500">{{ $preset['description'] }}</p>
                        <div x-show="businessType === '{{ $key }}'" class="absolute top-3 right-3">
                            <svg class="w-5 h-5 text-red-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        </div>
                    </button>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-end">
                    <button @click="nextStep()" :disabled="!businessType"
                            class="px-6 py-2.5 bg-red-800 text-white rounded-lg font-semibold text-sm hover:bg-red-900 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm">
                        Continuă →
                    </button>
                </div>
            </div>

            {{-- Step 2: Describe Business --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="p-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Spune-ne despre afacerea ta</h2>
                <p class="text-slate-500 mb-6">AI-ul va genera prompt-ul și personalitatea bot-ului automat.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Numele afacerii</label>
                        <input x-model="businessName" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition" placeholder="Ex: MagicBricolaj">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descrie afacerea ta în 2-3 propoziții</label>
                        <textarea x-model="businessDescription" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition resize-none" placeholder="Ex: Suntem un magazin online de materiale de construcții cu livrare în toată țara. Avem peste 5000 de produse și oferim consultanță gratuită."></textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button @click="prevStep()" class="text-sm text-slate-500 hover:text-slate-700 transition">← Înapoi</button>
                    <button @click="generatePrompt()" :disabled="!businessName || !businessDescription || generating"
                            class="px-6 py-2.5 bg-red-800 text-white rounded-lg font-semibold text-sm hover:bg-red-900 transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-sm flex items-center gap-2">
                        <svg x-show="generating" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="generating ? 'Se generează...' : 'Generează AI →'"></span>
                    </button>
                </div>
            </div>

            {{-- Step 3: Review & Complete --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" class="p-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Revizuiește configurația</h2>
                <p class="text-slate-500 mb-6">Poți modifica oricând mai târziu.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Numele bot-ului</label>
                        <input x-model="botName" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mesaj de întâmpinare</label>
                        <input x-model="greeting" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prompt de sistem <span class="text-xs text-slate-400">(generat de AI)</span></label>
                        <textarea x-model="systemPrompt" rows="6" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition resize-none font-mono text-xs leading-relaxed"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Domeniu site (opțional)</label>
                        <input x-model="domain" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-red-300 focus:ring-2 focus:ring-red-100 transition" placeholder="exemplu.ro">
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button @click="prevStep()" class="text-sm text-slate-500 hover:text-slate-700 transition">← Înapoi</button>
                    <button @click="completeSetup()" :disabled="completing"
                            class="px-8 py-3 bg-red-800 text-white rounded-lg font-bold text-sm hover:bg-red-900 transition-colors disabled:opacity-40 shadow-lg flex items-center gap-2">
                        <svg x-show="completing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="completing ? 'Se creează...' : 'Creează bot-ul 🚀'"></span>
                    </button>
                </div>
            </div>

        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Salt peste setup →</a>
        </p>
    </div>
</div>

<script>
function setupWizard() {
    return {
        step: 1,
        totalSteps: 3,
        businessType: '',
        businessName: '',
        businessDescription: '',
        botName: '',
        greeting: '',
        systemPrompt: '',
        domain: '',
        generating: false,
        completing: false,

        selectBusinessType(type) {
            this.businessType = type;
        },

        nextStep() {
            if (this.step < this.totalSteps) this.step++;
        },

        prevStep() {
            if (this.step > 1) this.step--;
        },

        async generatePrompt() {
            this.generating = true;
            try {
                const res = await fetch('{{ route("dashboard.setup.generatePrompt") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        business_type: this.businessType,
                        business_name: this.businessName,
                        business_description: this.businessDescription,
                    }),
                });
                const data = await res.json();
                if (data.prompt) {
                    this.systemPrompt = data.prompt;
                    this.greeting = data.greeting;
                    this.botName = data.bot_name;
                    this.step = 3;
                }
            } catch (e) {
                alert('Eroare la generare. Încearcă din nou.');
            }
            this.generating = false;
        },

        async completeSetup() {
            this.completing = true;
            try {
                const res = await fetch('{{ route("dashboard.setup.complete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        business_type: this.businessType,
                        business_name: this.businessName,
                        bot_name: this.botName,
                        system_prompt: this.systemPrompt,
                        greeting: this.greeting,
                        domain: this.domain,
                    }),
                });
                const data = await res.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (e) {
                alert('Eroare la creare. Încearcă din nou.');
            }
            this.completing = false;
        },
    };
}
</script>
@endsection
