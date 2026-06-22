@extends('layouts.dashboard')

@section('title', ($product ? 'Editează' : 'Adaugă') . ' produs — ' . $bot->name)

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="mb-6">
        <div class="text-xs text-muted mb-1">
            <a href="{{ route('dashboard.bots.products.index', $bot) }}" class="hover:text-coralh">← Toate produsele</a>
        </div>
        <h1 class="text-2xl font-bold text-ink">{{ $product ? 'Editează produs' : 'Adaugă produs manual' }}</h1>
        <p class="text-sm text-muted mt-1">
            @if($product)
                Modifici datele pentru <strong>{{ $product->name }}</strong>.
            @else
                Adaugă o intrare în catalog fără sincronizare WooCommerce. Util pentru servicii, abonamente sau magazine fără WordPress.
            @endif
        </p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $product ? route('dashboard.bots.products.update', [$bot, $product]) : route('dashboard.bots.products.store', $bot) }}"
          class="bg-white rounded-xl border border-line p-6 space-y-5">
        @csrf
        @if($product)@method('PATCH')@endif

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-1.5">Nume <span class="text-coral">*</span></label>
            <input type="text" name="name" required maxlength="255"
                   value="{{ old('name', $product?->name) }}"
                   placeholder="ex: Consultație stomatologică"
                   class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-1.5">Descriere scurtă</label>
            <textarea name="short_description" rows="3" maxlength="5000"
                      placeholder="Ce face acest produs/serviciu? Ce e inclus?"
                      class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">{{ old('short_description', $product?->short_description) }}</textarea>
            <p class="text-xs text-muted mt-1">Agentul folosește acest text pentru a explica produsul clienților.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Preț</label>
                <input type="number" name="price" step="0.01" min="0" max="9999999"
                       value="{{ old('price', $product?->price) }}"
                       placeholder="150.00"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Monedă</label>
                <select name="currency" class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                    @foreach(['RON' => 'RON (Lei)', 'EUR' => 'EUR', 'USD' => 'USD'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('currency', $product?->currency ?: 'RON') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Unitate</label>
                <input type="text" name="price_unit" maxlength="32"
                       value="{{ old('price_unit', $product?->price_unit) }}"
                       placeholder="buc / oră / kg"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Preț redus (opțional)</label>
                <input type="number" name="sale_price" step="0.01" min="0" max="9999999"
                       value="{{ old('sale_price', $product?->sale_price) }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                <p class="text-xs text-muted mt-1">Lasă gol dacă nu e ofertă.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Stoc</label>
                <select name="stock_status" class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                    @foreach(['instock' => 'În stoc / disponibil', 'outofstock' => 'Indisponibil', 'onbackorder' => 'Pe comandă'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('stock_status', $product?->stock_status ?: 'instock') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">SKU / cod</label>
                <input type="text" name="sku" maxlength="64"
                       value="{{ old('sku', $product?->sku) }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Categorii (CSV)</label>
                <input type="text" name="categories_csv" maxlength="500"
                       value="{{ old('categories_csv', is_array($product?->categories) ? implode(', ', $product->categories) : '') }}"
                       placeholder="stomatologie, profilaxie"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-1.5">URL imagine</label>
            <input type="url" name="image_url" maxlength="500"
                   value="{{ old('image_url', $product?->image_url) }}"
                   placeholder="https://exemplu.ro/imagine.jpg"
                   class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-1.5">URL pagină (link „mai multe detalii")</label>
            <input type="url" name="permalink" maxlength="500"
                   value="{{ old('permalink', $product?->permalink) }}"
                   placeholder="https://exemplu.ro/produs"
                   class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-line">
            <a href="{{ route('dashboard.bots.products.index', $bot) }}" class="text-sm text-muted hover:text-ink">← Renunță</a>
            <button type="submit" class="btn-coral rounded-lg px-5 py-2.5 text-sm font-semibold">
                {{ $product ? 'Salvează modificările' : 'Adaugă produs' }}
            </button>
        </div>
    </form>
</div>
@endsection
