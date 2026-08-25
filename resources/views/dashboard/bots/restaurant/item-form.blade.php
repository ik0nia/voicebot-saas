@extends('layouts.dashboard')

@section('title', ($item ? 'Editează ' . $item->name : 'Preparat nou') . ' — ' . $bot->name)

@php
    $action = $item
        ? route('dashboard.bots.restaurant.item.update', [$bot, $item])
        : route('dashboard.bots.restaurant.item.store', $bot);

    // Price is edited in RON, stored in cents. Rendered with a comma because
    // that is what a Romanian operator types; the controller accepts both.
    $priceValue = old('price', $item ? number_format($item->price_cents / 100, 2, ',', '') : '');
@endphp

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="text-xs text-muted mb-1">
        <a href="{{ route('dashboard.bots.restaurant.menu', $bot) }}" class="hover:text-coralh">← Meniu</a>
    </div>
    <h1 class="text-2xl font-bold text-ink mb-6">{{ $item ? 'Editează preparat' : 'Preparat nou' }}</h1>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($item) @method('PATCH') @endif

        <div class="bg-white rounded-xl border border-line p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink mb-1">Denumire *</label>
                <input type="text" name="name" required value="{{ old('name', $item->name ?? '') }}"
                       placeholder="ex. Doner Kebab"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Categorie</label>
                    <select name="menu_category_id" class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                        <option value="">— fără categorie —</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected(old('menu_category_id', $item->menu_category_id ?? $presetCategoryId) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Preț *</label>
                    <div class="relative">
                        <input type="text" name="price" required value="{{ $priceValue }}"
                               placeholder="24,99" inputmode="decimal"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-14 text-sm">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">RON</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Descriere</label>
                <textarea name="description" rows="2"
                          placeholder="Cum îl descrie agentul la telefon."
                          class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">{{ old('description', $item->description ?? '') }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Gramaj / porție</label>
                    <input type="text" name="portion" value="{{ old('portion', $item->portion ?? '') }}"
                           placeholder="410 g"
                           class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Timp preparare</label>
                    <div class="relative">
                        <input type="number" name="prep_time_minutes" min="0" max="600"
                               value="{{ old('prep_time_minutes', $item->prep_time_minutes ?? '') }}"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-12 text-sm">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">min</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink mb-1">Ordine</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $item->sort_order ?? 0) }}"
                           class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-line p-5 space-y-4">
            <div class="text-xs font-semibold text-muted uppercase">Ce știe agentul despre preparat</div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Ingrediente</label>
                <input type="text" name="ingredients_csv"
                       value="{{ old('ingredients_csv', $item && $item->ingredients ? implode(', ', $item->ingredients) : '') }}"
                       placeholder="carne de pui, varză, roșii, castraveți murați, sos de usturoi"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <p class="text-2xs text-muted mt-1">
                    Separate prin virgulă. Lasă gol dacă nu ești sigur — agentul va spune că nu are lista,
                    în loc să ghicească. Tot de aici caută când clientul cere „ceva cu ciuperci”.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Alergeni</label>
                <input type="text" name="allergens_csv"
                       value="{{ old('allergens_csv', $item && $item->allergens ? implode(', ', $item->allergens) : '') }}"
                       placeholder="gluten, lactoză, muștar"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <p class="text-2xs text-muted mt-1">
                    Separat de ingrediente pentru că răspunde la altă întrebare — asta e lista de siguranță.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Cum îi mai spun clienții</label>
                <input type="text" name="aliases_csv"
                       value="{{ old('aliases_csv', $item && $item->aliases ? implode(', ', $item->aliases) : '') }}"
                       placeholder="shaorma pui, șaorma de pui"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <p class="text-2xs text-muted mt-1">
                    Ajută căutarea când clientul folosește alt cuvânt decât cel din meniu.
                </p>
            </div>

            <div class="flex flex-wrap gap-4 pt-1">
                @foreach([
                    'is_available'   => 'Disponibil',
                    'is_vegetarian'  => 'Vegetarian',
                    'is_vegan'       => 'Vegan',
                    'is_gluten_free' => 'Fără gluten',
                    'is_spicy'       => 'Picant',
                ] as $field => $label)
                    <label class="inline-flex items-center gap-2 text-sm text-inkSoft">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               @checked(old($field, $item ? $item->{$field} : ($field === 'is_available')))
                               class="rounded border-line text-coral">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard.bots.restaurant.menu', $bot) }}" class="text-sm text-muted hover:text-ink">← Renunță</a>
            <button type="submit" class="btn-coral rounded-lg px-5 py-2.5 text-sm font-semibold">
                {{ $item ? 'Salvează' : 'Adaugă în meniu' }}
            </button>
        </div>
    </form>
</div>
@endsection
