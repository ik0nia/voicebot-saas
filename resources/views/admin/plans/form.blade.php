@extends('layouts.admin')

@section('title', $plan ? 'Editează pachet' : 'Pachet nou')
@section('breadcrumb')
    <a href="{{ route('admin.plans.index') }}" class="text-slate-500 hover:text-slate-700">Pachete & Prețuri</a>
    <span class="mx-1.5 text-slate-300">/</span>
    <span class="text-slate-900 font-medium">{{ $plan ? 'Editează: ' . $plan->name : 'Pachet nou' }}</span>
@endsection

@section('content')
<div class="max-w-3xl space-y-6">

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $plan ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="space-y-6">
        @csrf
        @if($plan) @method('PUT') @endif

        {{-- Basic Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Informații de bază</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nume pachet *</label>
                    <input type="text" name="name" id="plan-name" value="{{ old('name', $plan?->name) }}" required
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: Starter Webchat"
                           oninput="generateSlug(this.value)">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Slug</label>
                    <input type="text" name="slug" id="plan-slug" value="{{ old('slug', $plan?->slug) }}"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500 font-mono"
                           placeholder="auto-generat-din-nume">
                    <p class="text-xs text-slate-400 mt-1">Lăsați gol pentru auto-generare din nume.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tip *</label>
                    <select name="type" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Selectează --</option>
                        <option value="webchat" {{ old('type', $plan?->type) === 'webchat' ? 'selected' : '' }}>Webchat</option>
                        <option value="voice" {{ old('type', $plan?->type) === 'voice' ? 'selected' : '' }}>Voice</option>
                        <option value="bundle" {{ old('type', $plan?->type) === 'bundle' ? 'selected' : '' }}>Bundle</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ordine sortare</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}" min="0"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-600 mb-1">Descriere</label>
                <textarea name="description" rows="2"
                          class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                          placeholder="Scurtă descriere a pachetului">{{ old('description', $plan?->description) }}</textarea>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Prețuri</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Preț lunar (RON) *</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="price_monthly" value="{{ old('price_monthly', $plan?->price_monthly ?? 0) }}" required min="0"
                               class="w-full text-sm border border-slate-300 rounded-lg pl-3 pr-14 py-2 focus:ring-red-500 focus:border-red-500">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">RON/lună</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Preț anual (RON) *</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="price_yearly" value="{{ old('price_yearly', $plan?->price_yearly ?? 0) }}" required min="0"
                               class="w-full text-sm border border-slate-300 rounded-lg pl-3 pr-12 py-2 focus:ring-red-500 focus:border-red-500">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">RON/an</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6 mt-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_popular" value="0">
                    <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan?->is_popular) ? 'checked' : '' }}
                           class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                    <span class="text-sm text-slate-700">Marcat ca <strong>Popular</strong></span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                    <span class="text-sm text-slate-700">Activ</span>
                </label>
            </div>
        </div>

        {{-- Limits --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-1">Limite</h3>
            <p class="text-xs text-slate-400 mb-4">Setează -1 pentru nelimitat. Lasă gol pentru a nu include limita.</p>

            @php
                $currentLimits = old('limits', $plan?->limits ?? []);
                if (!is_array($currentLimits)) $currentLimits = [];
                $currentChannels = $currentLimits['channels'] ?? [];
                if (!is_array($currentChannels)) $currentChannels = [];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Boți</label>
                    <input type="number" name="limits[bots]" value="{{ $currentLimits['bots'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 3">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Mesaje / lună</label>
                    <input type="number" name="limits[messages_per_month]" value="{{ $currentLimits['messages_per_month'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 5000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Knowledge entries</label>
                    <input type="number" name="limits[knowledge_entries]" value="{{ $currentLimits['knowledge_entries'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 100">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Produse</label>
                    <input type="number" name="limits[products]" value="{{ $currentLimits['products'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 50">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Minute / lună</label>
                    <input type="number" name="limits[minutes_per_month]" value="{{ $currentLimits['minutes_per_month'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-600 mb-2">Canale disponibile</label>
                @php
                    $allChannels = ['webchat', 'whatsapp', 'facebook', 'instagram', 'voice', 'telegram'];
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach($allChannels as $channel)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="limits[channels][]" value="{{ $channel }}"
                                   {{ in_array($channel, $currentChannels) ? 'checked' : '' }}
                                   class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                            <span class="text-sm text-slate-700">{{ ucfirst($channel) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Overage --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-1">Costuri overage</h3>
            <p class="text-xs text-slate-400 mb-4">Costuri suplimentare când clientul depășește limitele pachetului.</p>

            @php
                $currentOverage = old('overage', $plan?->overage ?? []);
                if (!is_array($currentOverage)) $currentOverage = [];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cost / mesaj (RON)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_message]" value="{{ $currentOverage['cost_per_message'] ?? '' }}" min="0"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 0.05">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cost / cuvânt (RON)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_word]" value="{{ $currentOverage['cost_per_word'] ?? '' }}" min="0"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 0.001">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cost / minut (RON)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_minute]" value="{{ $currentOverage['cost_per_minute'] ?? '' }}" min="0"
                           class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Ex: 0.50">
                </div>
            </div>
        </div>

        {{-- Features --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-1">Funcționalități</h3>
            <p class="text-xs text-slate-400 mb-4">Un feature per linie. Vor fi afișate ca listă pe pagina de prețuri.</p>

            @php
                $currentFeatures = old('features_text', '');
                if (empty($currentFeatures) && $plan && is_array($plan->features)) {
                    $currentFeatures = implode("\n", $plan->features);
                }
            @endphp

            <textarea name="features_text" rows="6"
                      class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-red-500 focus:border-red-500 font-mono"
                      placeholder="Chatbot personalizabil&#10;Integrare website&#10;Suport email&#10;Dashboard analytics">{{ $currentFeatures }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.plans.index') }}" class="text-sm text-slate-500 hover:text-slate-700">
                &larr; Înapoi la pachete
            </a>
            <button type="submit" class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                {{ $plan ? 'Salvează modificările' : 'Creează pachetul' }}
            </button>
        </div>
    </form>

</div>

@push('scripts')
<script>
    function generateSlug(name) {
        const slugField = document.getElementById('plan-slug');
        // Only auto-generate if slug field is empty or was auto-generated
        if (!slugField.dataset.manual) {
            slugField.value = name
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }

    document.getElementById('plan-slug').addEventListener('input', function() {
        this.dataset.manual = this.value ? '1' : '';
    });
</script>
@endpush
@endsection
