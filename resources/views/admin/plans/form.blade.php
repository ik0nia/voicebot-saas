@extends('layouts.admin')

@section('title', $plan ? 'Editează pachet' : 'Pachet nou')
@section('breadcrumb')
    <a href="{{ route('admin.plans.index') }}" class="text-muted hover:text-inkSoft">Pachete & Prețuri</a>
    <span class="mx-1.5 text-slate-300">/</span>
    <span class="text-ink font-medium">{{ $plan ? 'Editează: ' . $plan->name : 'Pachet nou' }}</span>
@endsection

@section('content')
<div class="max-w-3xl space-y-6">

    @if($errors->any())
        <div class="rounded-lg border border-coral/30 bg-coralsoft p-4">
            <ul class="text-sm text-coralh space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $plan ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="space-y-6">
        @csrf
        @if($plan) @method('PUT') @endif

        {{-- Basic Info --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-4">Informații de bază</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Nume pachet *</label>
                    <input type="text" name="name" id="plan-name" value="{{ old('name', $plan?->name) }}" required
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: Starter Webchat"
                           oninput="generateSlug(this.value)">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Slug</label>
                    <input type="text" name="slug" id="plan-slug" value="{{ old('slug', $plan?->slug) }}"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral font-mono"
                           placeholder="auto-generat-din-nume">
                    <p class="text-xs text-muted mt-1">Lăsați gol pentru auto-generare din nume.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Tip *</label>
                    <select name="type" required class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">
                        <option value="">-- Selectează --</option>
                        <option value="webchat" {{ old('type', $plan?->type) === 'webchat' ? 'selected' : '' }}>Webchat</option>
                        <option value="voice" {{ old('type', $plan?->type) === 'voice' ? 'selected' : '' }}>Voice</option>
                        <option value="bundle" {{ old('type', $plan?->type) === 'bundle' ? 'selected' : '' }}>Bundle</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Ordine sortare</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}" min="0"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-muted mb-1">Descriere</label>
                <textarea name="description" rows="2"
                          class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                          placeholder="Scurtă descriere a pachetului">{{ old('description', $plan?->description) }}</textarea>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-4">Prețuri</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Preț lunar (lei) *</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="price_monthly" value="{{ old('price_monthly', $plan?->price_monthly ?? 0) }}" required min="0"
                               class="w-full text-sm border border-line rounded-lg pl-3 pr-14 py-2 focus:ring-coral focus:border-coral">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">lei/lună</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Preț anual (lei) *</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="price_yearly" value="{{ old('price_yearly', $plan?->price_yearly ?? 0) }}" required min="0"
                               class="w-full text-sm border border-line rounded-lg pl-3 pr-12 py-2 focus:ring-coral focus:border-coral">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">lei/an</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6 mt-4 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_popular" value="0">
                    <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan?->is_popular) ? 'checked' : '' }}
                           class="w-4 h-4 text-coral border-line rounded focus:ring-coral">
                    <span class="text-sm text-inkSoft">Marcat ca <strong>Popular</strong></span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-coral border-line rounded focus:ring-coral">
                    <span class="text-sm text-inkSoft">Activ</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_public" value="0">
                    <input type="checkbox" name="is_public" value="1" {{ old('is_public', $plan?->is_public ?? true) ? 'checked' : '' }}
                           class="w-4 h-4 text-coral border-line rounded focus:ring-coral">
                    <span class="text-sm text-inkSoft">Public (vizibil pe /preturi)</span>
                </label>
            </div>
        </div>

        {{-- Custom plan: assign to one tenant --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-1">Pachet custom (opțional)</h3>
            <p class="text-xs text-muted mb-4">Atribuie acest pachet unui singur tenant. Pachetele custom NU apar pe pagina publică /preturi și sunt vizibile doar în dashboard-ul tenantului asignat.</p>
            <select name="tenant_id"
                    class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">
                <option value="">— Pachet global (toți userii) —</option>
                @foreach($tenants ?? [] as $t)
                    <option value="{{ $t->id }}" {{ old('tenant_id', $plan?->tenant_id) == $t->id ? 'selected' : '' }}>
                        {{ $t->name }} (#{{ $t->id }})
                    </option>
                @endforeach
            </select>
            @if($plan?->tenant_id)
                <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                    ⚠ Pachet custom — <strong>is_public</strong> va fi forțat la <code>false</code> indiferent ce bifezi mai sus.
                </p>
            @endif
        </div>

        {{-- Limits --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-1">Limite</h3>
            <p class="text-xs text-muted mb-4">Setează -1 pentru nelimitat. Lasă gol pentru a nu include limita.</p>

            @php
                $currentLimits = old('limits', $plan?->limits ?? []);
                if (!is_array($currentLimits)) $currentLimits = [];
                $currentChannels = $currentLimits['channels'] ?? [];
                if (!is_array($currentChannels)) $currentChannels = [];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Boți</label>
                    <input type="number" name="limits[bots]" value="{{ $currentLimits['bots'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 3">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Mesaje / lună</label>
                    <input type="number" name="limits[messages_per_month]" value="{{ $currentLimits['messages_per_month'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 5000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Knowledge entries</label>
                    <input type="number" name="limits[knowledge_entries]" value="{{ $currentLimits['knowledge_entries'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 100">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Produse</label>
                    <input type="number" name="limits[products]" value="{{ $currentLimits['products'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 50">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Minute / lună</label>
                    <input type="number" name="limits[minutes_per_month]" value="{{ $currentLimits['minutes_per_month'] ?? '' }}" min="-1"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-muted mb-2">Canale disponibile</label>
                @php
                    $allChannels = ['webchat', 'whatsapp', 'facebook', 'instagram', 'voice', 'telegram'];
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach($allChannels as $channel)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="limits[channels][]" value="{{ $channel }}"
                                   {{ in_array($channel, $currentChannels) ? 'checked' : '' }}
                                   class="w-4 h-4 text-coral border-line rounded focus:ring-coral">
                            <span class="text-sm text-inkSoft">{{ ucfirst($channel) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Overage --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-1">Costuri overage</h3>
            <p class="text-xs text-muted mb-4">Costuri suplimentare când clientul depășește limitele pachetului.</p>

            @php
                $currentOverage = old('overage', $plan?->overage ?? []);
                if (!is_array($currentOverage)) $currentOverage = [];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Cost / mesaj (lei)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_message]" value="{{ $currentOverage['cost_per_message'] ?? '' }}" min="0"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 0.05">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Cost / cuvânt (lei)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_word]" value="{{ $currentOverage['cost_per_word'] ?? '' }}" min="0"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 0.001">
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Cost / minut (lei)</label>
                    <input type="number" step="0.0001" name="overage[cost_per_minute]" value="{{ $currentOverage['cost_per_minute'] ?? '' }}" min="0"
                           class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral"
                           placeholder="Ex: 0.50">
                </div>
            </div>
        </div>

        {{-- Topup bundles --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-1">Credite extra (top-up)</h3>
            <p class="text-xs text-muted mb-4">Pachete de credite suplimentare pe care clientul le poate cumpăra one-off, peste cuota inclusă în abonament. Ex: 1.000 mesaje extra = 5 RON. Mesajele se consumă până la zero, nu expiră lunar.</p>

            @php
                $currentTopups = old('topups', $plan?->topups ?? []);
                if (!is_array($currentTopups)) $currentTopups = [];
                // Always render at least 3 rows so admins can fill them in.
                while (count($currentTopups) < 3) {
                    $currentTopups[] = ['name' => '', 'unit' => 'messages', 'quantity' => '', 'price' => '', 'is_active' => true];
                }
            @endphp

            <div id="topup-rows" class="space-y-3">
                @foreach($currentTopups as $i => $topup)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-cream rounded-lg p-3">
                        <input type="text" name="topups[{{ $i }}][name]" value="{{ $topup['name'] ?? '' }}"
                               placeholder="Ex: 1.000 mesaje extra"
                               class="md:col-span-4 text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">

                        <select name="topups[{{ $i }}][unit]"
                                class="md:col-span-2 text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">
                            <option value="messages" {{ ($topup['unit'] ?? 'messages') === 'messages' ? 'selected' : '' }}>mesaje</option>
                            <option value="minutes" {{ ($topup['unit'] ?? 'messages') === 'minutes' ? 'selected' : '' }}>minute</option>
                            <option value="products" {{ ($topup['unit'] ?? 'messages') === 'products' ? 'selected' : '' }}>produse</option>
                        </select>

                        <input type="number" step="1" min="1" name="topups[{{ $i }}][quantity]" value="{{ $topup['quantity'] ?? '' }}"
                               placeholder="Cantitate"
                               class="md:col-span-2 text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">

                        <input type="number" step="0.01" min="0" name="topups[{{ $i }}][price]" value="{{ $topup['price'] ?? '' }}"
                               placeholder="Preț (lei)"
                               class="md:col-span-2 text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral">

                        <label class="md:col-span-2 inline-flex items-center gap-2 text-xs text-muted">
                            <input type="hidden" name="topups[{{ $i }}][is_active]" value="0">
                            <input type="checkbox" name="topups[{{ $i }}][is_active]" value="1"
                                   {{ ($topup['is_active'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-line text-coral focus:ring-coral">
                            Activ
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 text-xs text-muted">
                <strong>Notă:</strong> Lasă rândurile goale dacă nu vrei mai multe bundle-uri. ID-urile Stripe se generează automat la <code class="text-xs bg-cream px-1 rounded">php artisan stripe:sync-plans</code>.
            </div>

            @if($plan && !empty($plan->stripe_topup_prices))
                <div class="mt-4 text-xs text-muted bg-cream rounded-lg p-3 border border-line">
                    <div class="font-semibold mb-1">Stripe price IDs (mod activ: {{ \App\Models\Plan::activeStripeMode() }})</div>
                    @foreach(($plan->topups ?? []) as $i => $bundle)
                        @php $pid = $plan->stripeTopupPriceId((int)$i); @endphp
                        <div>#{{ $i }} {{ $bundle['name'] ?? '—' }} → <code class="bg-white px-1 rounded">{{ $pid ?: 'nesincronizat' }}</code></div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Features --}}
        <div class="bg-white rounded-xl border border-line p-6">
            <h3 class="text-sm font-bold text-ink uppercase tracking-wide mb-1">Funcționalități</h3>
            <p class="text-xs text-muted mb-4">Un feature per linie. Vor fi afișate ca listă pe pagina de prețuri.</p>

            @php
                $currentFeatures = old('features_text', '');
                if (empty($currentFeatures) && $plan && is_array($plan->features)) {
                    $currentFeatures = implode("\n", $plan->features);
                }
            @endphp

            <textarea name="features_text" rows="6"
                      class="w-full text-sm border border-line rounded-lg px-3 py-2 focus:ring-coral focus:border-coral font-mono"
                      placeholder="Chatbot personalizabil&#10;Integrare website&#10;Suport email&#10;Dashboard analytics">{{ $currentFeatures }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.plans.index') }}" class="text-sm text-muted hover:text-inkSoft">
                &larr; Înapoi la pachete
            </a>
            <button type="submit" class="px-6 py-2.5 bg-coral text-white text-sm font-medium rounded-lg hover:bg-coral transition-colors">
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
