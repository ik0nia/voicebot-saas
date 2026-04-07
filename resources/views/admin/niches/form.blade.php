@extends('layouts.admin')

@section('title', $isEdit ? 'Editează nișă' : 'Nișă nouă')
@section('breadcrumb')
    <a href="{{ route('admin.niches.index') }}" class="text-slate-500 hover:text-slate-700">Nișe</a>
    <span class="text-slate-400 mx-1">/</span>
    <span class="text-slate-900 font-medium">{{ $isEdit ? $niche->name : 'Nișă nouă' }}</span>
@endsection

@section('content')
@php
    $themes = ['red','emerald','blue','amber','rose','purple','indigo','teal','cyan','orange'];
    $tones  = ['formal' => 'Formal', 'professional' => 'Profesional', 'friendly' => 'Prietenos', 'playful' => 'Jucăuș'];
    $action = $isEdit ? route('admin.niches.update', $niche) : route('admin.niches.store');
    $old = fn($k, $d = '') => old($k, data_get($niche, $k, $d));
    $benefits     = old('benefits',     $niche->benefits     ?? []) ?: [];
    $demoMessages = old('demo_messages',$niche->demo_messages?? []) ?: [];
    $faq          = old('faq',          $niche->faq          ?? []) ?: [];
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $isEdit ? 'Editează: '.$niche->name : 'Nișă nouă' }}</h1>
            <p class="text-sm text-slate-500">Toate câmpurile marcate sunt obligatorii.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($isEdit)
                <a href="{{ route('public.niche', $niche->slug) }}" target="_blank"
                   class="px-3 py-2 text-sm rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Preview</a>
            @endif
            <a href="{{ route('admin.niches.index') }}"
               class="px-3 py-2 text-sm rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Anulează</a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-800 mb-2">Te rog corectează următoarele erori:</p>
            <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-6" id="nicheForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Basic info --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Informații de bază</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nume *</label>
                    <input type="text" name="name" id="niche-name" value="{{ $old('name') }}" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Slug *</label>
                    <div class="flex gap-2">
                        <input type="text" name="slug" id="niche-slug" value="{{ $old('slug') }}" required
                               class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <button type="button" id="slug-from-name" class="px-3 py-2 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Auto</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Vertical label *</label>
                    <input type="text" name="vertical_label" value="{{ $old('vertical_label') }}" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Temă *</label>
                        <select name="color_theme" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            @foreach($themes as $t)
                                <option value="{{ $t }}" @selected($old('color_theme','blue') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ton *</label>
                        <select name="tone" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            @foreach($tones as $tv => $tl)
                                <option value="{{ $tv }}" @selected($old('tone','professional') === $tv)>{{ $tl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ordine</label>
                    <input type="number" name="sort_order" value="{{ $old('sort_order', 0) }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ $old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                        Activ (vizibil public)
                    </label>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">SEO</h2>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Meta title</label>
                <input type="text" name="meta_title" maxlength="200" value="{{ old('meta_title', $niche->getAttributes()['meta_title'] ?? '') }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm char-counter" data-max="60">
                <p class="text-xs text-slate-400 mt-1"><span class="char-count">0</span> caractere (recomandat ≤ 60)</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Meta description</label>
                <textarea name="meta_description" rows="2" maxlength="300"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm char-counter" data-max="160">{{ $old('meta_description') }}</textarea>
                <p class="text-xs text-slate-400 mt-1"><span class="char-count">0</span> caractere (recomandat ≤ 160)</p>
            </div>
        </div>

        {{-- Hero --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Hero</h2>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Eyebrow</label>
                <input type="text" name="hero_eyebrow" value="{{ $old('hero_eyebrow') }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Titlu *</label>
                <input type="text" name="hero_title" value="{{ $old('hero_title') }}" required
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitlu *</label>
                <textarea name="hero_subtitle" rows="3" required
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">{{ $old('hero_subtitle') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Image path</label>
                <input type="text" name="image_path" value="{{ $old('image_path') }}" placeholder="/images/niches/medic.jpg"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
            </div>
        </div>

        {{-- Problem --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Problema</h2>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Titlu *</label>
                <input type="text" name="problem_title" value="{{ $old('problem_title') }}" required
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Text *</label>
                <textarea name="problem_text" rows="5" required
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">{{ $old('problem_text') }}</textarea>
            </div>
        </div>

        {{-- Solution --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Soluția</h2>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Titlu *</label>
                <input type="text" name="solution_title" value="{{ $old('solution_title') }}" required
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Text *</label>
                <textarea name="solution_text" rows="5" required
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">{{ $old('solution_text') }}</textarea>
            </div>
        </div>

        {{-- Benefits repeater --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($benefits)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Beneficii</h2>
                <button type="button" @click="rows.push({title:'', description:''})"
                        class="text-xs font-semibold text-red-600 hover:text-red-700">+ Adaugă beneficiu</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start border border-slate-100 rounded-lg p-3">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Titlu</label>
                        <input type="text" :name="`benefits[${i}][title]`" x-model="row.title"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Descriere</label>
                        <input type="text" :name="`benefits[${i}][description]`" x-model="row.description"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-red-600 hover:text-red-700">Șterge</button>
                    </div>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-slate-400">Niciun beneficiu adăugat încă.</p>
        </div>

        {{-- Demo messages repeater --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($demoMessages)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Demo conversație</h2>
                <button type="button" @click="rows.push({role:'bot', text:''})"
                        class="text-xs font-semibold text-red-600 hover:text-red-700">+ Adaugă mesaj</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start border border-slate-100 rounded-lg p-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rol</label>
                        <select :name="`demo_messages[${i}][role]`" x-model="row.role"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            <option value="bot">Bot</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="md:col-span-9">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Text</label>
                        <input type="text" :name="`demo_messages[${i}][text]`" x-model="row.text"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-red-600 hover:text-red-700">Șterge</button>
                    </div>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-slate-400">Niciun mesaj adăugat încă.</p>
        </div>

        {{-- CTAs + social proof --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">CTA-uri & Social proof</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">CTA primar — text</label>
                    <input type="text" name="cta_primary_text" value="{{ $old('cta_primary_text') }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">CTA primar — href</label>
                    <input type="text" name="cta_primary_href" value="{{ $old('cta_primary_href') }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">CTA secundar — text</label>
                    <input type="text" name="cta_secondary_text" value="{{ $old('cta_secondary_text') }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">CTA secundar — href</label>
                    <input type="text" name="cta_secondary_href" value="{{ $old('cta_secondary_href') }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Social proof text</label>
                <textarea name="social_proof_text" rows="2"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">{{ $old('social_proof_text') }}</textarea>
            </div>
        </div>

        {{-- Icon SVG --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Icon SVG</h2>
            <textarea name="icon_svg" rows="5" placeholder="<svg ...></svg>"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono">{{ $old('icon_svg') }}</textarea>
        </div>

        {{-- FAQ repeater --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($faq)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">FAQ</h2>
                <button type="button" @click="rows.push({question:'', answer:''})"
                        class="text-xs font-semibold text-red-600 hover:text-red-700">+ Adaugă întrebare</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="space-y-2 border border-slate-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700">Întrebarea #<span x-text="i+1"></span></label>
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-red-600 hover:text-red-700">Șterge</button>
                    </div>
                    <input type="text" :name="`faq[${i}][question]`" x-model="row.question" placeholder="Întrebare"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    <textarea :name="`faq[${i}][answer]`" x-model="row.answer" rows="3" placeholder="Răspuns"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></textarea>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-slate-400">Nicio întrebare adăugată încă.</p>
        </div>

        {{-- Save --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.niches.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Înapoi la listă</a>
            <button type="submit"
                    class="px-5 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">
                {{ $isEdit ? 'Salvează modificările' : 'Creează nișa' }}
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    // Auto-slug button
    const nameEl = document.getElementById('niche-name');
    const slugEl = document.getElementById('niche-slug');
    const slugBtn = document.getElementById('slug-from-name');
    function slugify(s) {
        return (s || '').toString().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    if (slugBtn) slugBtn.addEventListener('click', () => { slugEl.value = slugify(nameEl.value); });

    // Char counters
    document.querySelectorAll('.char-counter').forEach(el => {
        const p = el.parentElement.querySelector('.char-count');
        const update = () => { if (p) p.textContent = el.value.length; };
        el.addEventListener('input', update);
        update();
    });
})();
</script>
@endsection
