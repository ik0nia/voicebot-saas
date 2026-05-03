@extends('layouts.admin')

@section('title', $isEdit ? 'Editează nișă' : 'Nișă nouă')
@section('breadcrumb')
    <a href="{{ route('admin.niches.index') }}" class="text-muted hover:text-inkSoft">Nișe</a>
    <span class="text-muted mx-1">/</span>
    <span class="text-ink font-medium">{{ $isEdit ? $niche->name : 'Nișă nouă' }}</span>
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
            <h1 class="text-xl font-bold text-ink">{{ $isEdit ? 'Editează: '.$niche->name : 'Nișă nouă' }}</h1>
            <p class="text-sm text-muted">Toate câmpurile marcate sunt obligatorii.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($isEdit)
                <a href="{{ route('new.niche', $niche->slug) }}" target="_blank"
                   class="px-3 py-2 text-sm rounded-lg border border-line text-inkSoft hover:bg-cream">Preview</a>
            @endif
            <a href="{{ route('admin.niches.index') }}"
               class="px-3 py-2 text-sm rounded-lg border border-line text-inkSoft hover:bg-cream">Anulează</a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-coral/30 bg-coralsoft p-4">
            <p class="text-sm font-semibold text-coralh mb-2">Te rog corectează următoarele erori:</p>
            <ul class="list-disc list-inside text-sm text-coralh space-y-0.5">
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
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Informații de bază</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">Nume *</label>
                    <input type="text" name="name" id="niche-name" value="{{ $old('name') }}" required
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm focus:ring-2 focus:ring-coral focus:border-coral">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">Slug *</label>
                    <div class="flex gap-2">
                        <input type="text" name="slug" id="niche-slug" value="{{ $old('slug') }}" required
                               class="flex-1 px-3 py-2 border border-line rounded-lg text-sm font-mono focus:ring-2 focus:ring-coral focus:border-coral">
                        <button type="button" id="slug-from-name" class="px-3 py-2 text-xs rounded-lg border border-line text-muted hover:bg-cream">Auto</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">Vertical label *</label>
                    <input type="text" name="vertical_label" value="{{ $old('vertical_label') }}" required
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Temă *</label>
                        <select name="color_theme" class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                            @foreach($themes as $t)
                                <option value="{{ $t }}" @selected($old('color_theme','blue') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Ton *</label>
                        <select name="tone" class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                            @foreach($tones as $tv => $tl)
                                <option value="{{ $tv }}" @selected($old('tone','professional') === $tv)>{{ $tl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">Ordine</label>
                    <input type="number" name="sort_order" value="{{ $old('sort_order', 0) }}"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-inkSoft">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ $old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-line text-coral focus:ring-coral">
                        Activ (vizibil public)
                    </label>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">SEO</h2>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Meta title</label>
                <input type="text" name="meta_title" maxlength="200" value="{{ old('meta_title', $niche->getAttributes()['meta_title'] ?? '') }}"
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm char-counter" data-max="60">
                <p class="text-xs text-muted mt-1"><span class="char-count">0</span> caractere (recomandat ≤ 60)</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Meta description</label>
                <textarea name="meta_description" rows="2" maxlength="300"
                          class="w-full px-3 py-2 border border-line rounded-lg text-sm char-counter" data-max="160">{{ $old('meta_description') }}</textarea>
                <p class="text-xs text-muted mt-1"><span class="char-count">0</span> caractere (recomandat ≤ 160)</p>
            </div>
        </div>

        {{-- Hero --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Hero</h2>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Eyebrow</label>
                <input type="text" name="hero_eyebrow" value="{{ $old('hero_eyebrow') }}"
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Titlu *</label>
                <input type="text" name="hero_title" value="{{ $old('hero_title') }}" required
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Subtitlu *</label>
                <textarea name="hero_subtitle" rows="3" required
                          class="w-full px-3 py-2 border border-line rounded-lg text-sm">{{ $old('hero_subtitle') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Image path</label>
                <input type="text" name="image_path" value="{{ $old('image_path') }}" placeholder="/images/niches/medic.jpg"
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm font-mono">
            </div>
        </div>

        {{-- Problem --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Problema</h2>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Titlu *</label>
                <input type="text" name="problem_title" value="{{ $old('problem_title') }}" required
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Text *</label>
                <textarea name="problem_text" rows="5" required
                          class="w-full px-3 py-2 border border-line rounded-lg text-sm">{{ $old('problem_text') }}</textarea>
            </div>
        </div>

        {{-- Solution --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Soluția</h2>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Titlu *</label>
                <input type="text" name="solution_title" value="{{ $old('solution_title') }}" required
                       class="w-full px-3 py-2 border border-line rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Text *</label>
                <textarea name="solution_text" rows="5" required
                          class="w-full px-3 py-2 border border-line rounded-lg text-sm">{{ $old('solution_text') }}</textarea>
            </div>
        </div>

        {{-- Benefits repeater --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($benefits)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Beneficii</h2>
                <button type="button" @click="rows.push({title:'', description:''})"
                        class="text-xs font-semibold text-coral hover:text-coralh">+ Adaugă beneficiu</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start border border-line rounded-lg p-3">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Titlu</label>
                        <input type="text" :name="`benefits[${i}][title]`" x-model="row.title"
                               class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-7">
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Descriere</label>
                        <input type="text" :name="`benefits[${i}][description]`" x-model="row.description"
                               class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-coral hover:text-coralh">Șterge</button>
                    </div>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-muted">Niciun beneficiu adăugat încă.</p>
        </div>

        {{-- Demo messages repeater --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($demoMessages)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Demo conversație</h2>
                <button type="button" @click="rows.push({role:'bot', text:''})"
                        class="text-xs font-semibold text-coral hover:text-coralh">+ Adaugă mesaj</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start border border-line rounded-lg p-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Rol</label>
                        <select :name="`demo_messages[${i}][role]`" x-model="row.role"
                                class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                            <option value="bot">Bot</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="md:col-span-9">
                        <label class="block text-xs font-semibold text-inkSoft mb-1">Text</label>
                        <input type="text" :name="`demo_messages[${i}][text]`" x-model="row.text"
                               class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                    </div>
                    <div class="md:col-span-1 flex md:justify-end md:pt-6">
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-coral hover:text-coralh">Șterge</button>
                    </div>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-muted">Niciun mesaj adăugat încă.</p>
        </div>

        {{-- CTAs + social proof --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">CTA-uri & Social proof</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">CTA primar — text</label>
                    <input type="text" name="cta_primary_text" value="{{ $old('cta_primary_text') }}"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">CTA primar — href</label>
                    <input type="text" name="cta_primary_href" value="{{ $old('cta_primary_href') }}"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">CTA secundar — text</label>
                    <input type="text" name="cta_secondary_text" value="{{ $old('cta_secondary_text') }}"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-inkSoft mb-1">CTA secundar — href</label>
                    <input type="text" name="cta_secondary_href" value="{{ $old('cta_secondary_href') }}"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-inkSoft mb-1">Social proof text</label>
                <textarea name="social_proof_text" rows="2"
                          class="w-full px-3 py-2 border border-line rounded-lg text-sm">{{ $old('social_proof_text') }}</textarea>
            </div>
        </div>

        {{-- Icon SVG --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-muted">Icon SVG</h2>
            <textarea name="icon_svg" rows="5" placeholder="<svg ...></svg>"
                      class="w-full px-3 py-2 border border-line rounded-lg text-xs font-mono">{{ $old('icon_svg') }}</textarea>
        </div>

        {{-- FAQ repeater --}}
        <div class="bg-white rounded-xl border border-line shadow-sm p-6 space-y-4"
             x-data='{ rows: @json(array_values($faq)) }'>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-muted">FAQ</h2>
                <button type="button" @click="rows.push({question:'', answer:''})"
                        class="text-xs font-semibold text-coral hover:text-coralh">+ Adaugă întrebare</button>
            </div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="space-y-2 border border-line rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-inkSoft">Întrebarea #<span x-text="i+1"></span></label>
                        <button type="button" @click="rows.splice(i,1)" class="text-xs text-coral hover:text-coralh">Șterge</button>
                    </div>
                    <input type="text" :name="`faq[${i}][question]`" x-model="row.question" placeholder="Întrebare"
                           class="w-full px-3 py-2 border border-line rounded-lg text-sm">
                    <textarea :name="`faq[${i}][answer]`" x-model="row.answer" rows="3" placeholder="Răspuns"
                              class="w-full px-3 py-2 border border-line rounded-lg text-sm"></textarea>
                </div>
            </template>
            <p x-show="rows.length === 0" class="text-xs text-muted">Nicio întrebare adăugată încă.</p>
        </div>

        {{-- Save --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.niches.index') }}" class="text-sm text-muted hover:text-inkSoft">← Înapoi la listă</a>
            <button type="submit"
                    class="px-5 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coral transition-colors">
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
