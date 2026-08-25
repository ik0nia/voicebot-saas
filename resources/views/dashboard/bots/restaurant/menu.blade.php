@extends('layouts.dashboard')

@section('title', 'Meniu — ' . $bot->name)

@section('content')
<div class="max-w-5xl mx-auto py-6 px-4" x-data="{ newCategory: false, editing: null }">
    <div class="text-xs text-muted mb-1">
        <a href="{{ route('dashboard.bots.edit', $bot) }}" class="hover:text-coralh">← {{ $bot->name }}</a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-ink">Meniu</h1>
            <p class="text-sm text-muted mt-1">
                Preparatele pe care agentul le poate căuta și adăuga într-o comandă.
            </p>
        </div>
        <a href="{{ route('dashboard.bots.restaurant.item.create', $bot) }}"
           class="btn-coral inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold">
            + Adaugă preparat
        </a>
    </div>

    <div class="mt-6">
        @include('dashboard.bots.restaurant._nav', ['active' => 'menu'])
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @error('name')
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    {{-- Add category --}}
    <div class="mb-5">
        <button type="button" @click="newCategory = !newCategory"
                class="text-sm text-coralh hover:underline">+ Categorie nouă</button>

        <form x-show="newCategory" x-cloak method="POST"
              action="{{ route('dashboard.bots.restaurant.category.store', $bot) }}"
              class="mt-3 bg-white rounded-xl border border-line p-4 flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-2xs text-muted mb-1">Denumire</label>
                <input type="text" name="name" required placeholder="ex. Shaorma și kebab"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
            </div>
            <div class="w-24">
                <label class="block text-2xs text-muted mb-1">Ordine</label>
                <input type="number" name="sort_order" value="0" min="0"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
            </div>
            <button type="submit" class="btn-coral rounded-lg px-4 py-2 text-sm font-semibold">Adaugă</button>
        </form>
    </div>

    @if($categories->isEmpty() && $orphans->isEmpty())
        <div class="rounded-xl border border-line bg-white p-10 text-center">
            <div class="text-3xl mb-2">📖</div>
            <p class="text-sm text-muted">
                Meniul e gol. Adaugă o categorie, apoi preparatele din ea.
            </p>
        </div>
    @endif

    <div class="space-y-5">
        @foreach($categories as $category)
            <div class="bg-white rounded-xl border border-line overflow-hidden">
                <div class="px-4 py-3 border-b border-line flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink">{{ $category->name }}</span>
                            <span class="text-2xs text-muted">{{ $category->items->count() }} preparate</span>
                            @unless($category->is_active)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-stone-100 text-stone-600 border border-stone-300">
                                    inactivă
                                </span>
                            @endunless
                        </div>
                        @if($category->description)
                            <p class="text-xs text-muted mt-0.5">{{ $category->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('dashboard.bots.restaurant.item.create', ['bot' => $bot, 'category' => $category->id]) }}"
                           class="text-xs text-coralh hover:underline px-2">+ preparat</a>
                        <button type="button" @click="editing = editing === {{ $category->id }} ? null : {{ $category->id }}"
                                class="text-xs text-muted hover:text-ink p-1.5 rounded hover:bg-cream" title="Editează categoria">✎</button>
                        <form method="POST" action="{{ route('dashboard.bots.restaurant.category.destroy', [$bot, $category]) }}"
                              onsubmit="return confirm('Ștergi categoria „{{ addslashes($category->name) }}”? Preparatele rămân, dar fără categorie.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-muted hover:text-coralh p-1.5 rounded hover:bg-coralsoft" title="Șterge categoria">🗑</button>
                        </form>
                    </div>
                </div>

                {{-- Inline category editor --}}
                <form x-show="editing === {{ $category->id }}" x-cloak method="POST"
                      action="{{ route('dashboard.bots.restaurant.category.update', [$bot, $category]) }}"
                      class="px-4 py-3 bg-cream/40 border-b border-line flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-2xs text-muted mb-1">Denumire</label>
                        <input type="text" name="name" value="{{ $category->name }}" required
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-2xs text-muted mb-1">Descriere</label>
                        <input type="text" name="description" value="{{ $category->description }}"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                    </div>
                    <div class="w-20">
                        <label class="block text-2xs text-muted mb-1">Ordine</label>
                        <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-inkSoft pb-2">
                        <input type="checkbox" name="is_active" value="1" @checked($category->is_active)
                               class="rounded border-line text-coral">
                        activă
                    </label>
                    <button type="submit" class="btn-coral rounded-lg px-4 py-2 text-sm font-semibold">Salvează</button>
                </form>

                @include('dashboard.bots.restaurant._items', ['items' => $category->items, 'bot' => $bot])
            </div>
        @endforeach

        @if($orphans->isNotEmpty())
            <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-amber-200 bg-amber-50">
                    <span class="font-semibold text-amber-900">Fără categorie</span>
                    <span class="text-2xs text-amber-800 ml-2">
                        {{ $orphans->count() }} preparate — agentul le poate găsi, dar nu le poate grupa la citirea meniului.
                    </span>
                </div>
                @include('dashboard.bots.restaurant._items', ['items' => $orphans, 'bot' => $bot])
            </div>
        @endif
    </div>
</div>
@endsection
