@extends('layouts.admin')

@section('title', 'Postare #' . $post->id)
@section('breadcrumb', 'Social · Postare #' . $post->id)

@section('content')
@php
    $statusColors = [
        'draft'      => 'bg-cream text-inkSoft',
        'scheduled'  => 'bg-blue-100 text-blue-700',
        'publishing' => 'bg-amber-100 text-amber-700',
        'published'  => 'bg-green-100 text-green-700',
        'failed'     => 'bg-coralsoft text-coralh',
        'rejected'   => 'bg-rose-100 text-rose-700',
    ];
    $platformBg = ['facebook' => 'bg-blue-600', 'instagram' => 'bg-pink-600', 'blog' => 'bg-slate-600'];
    $isEditable = in_array($post->status, ['draft', 'scheduled', 'failed']);
@endphp

<div class="max-w-6xl mx-auto p-4 lg:p-6" id="postRoot" data-csrf="{{ csrf_token() }}" data-post-id="{{ $post->id }}">

    {{-- Top bar --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.social.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Înapoi la listă
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-2 py-1 text-[10px] font-bold rounded-full text-white {{ $platformBg[$post->platform] ?? 'bg-slate-600' }}">{{ strtoupper($post->platform) }}</span>
            <span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $statusColors[$post->status] ?? 'bg-cream' }}">{{ strtoupper($post->status) }}</span>
            @if($post->post_type !== 'post')
                <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700">{{ strtoupper($post->post_type) }}</span>
            @endif
            @if(($post->regen_count ?? 0) > 0)
                <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700" title="Regenerări">↻ {{ $post->regen_count }}</span>
            @endif
            @if($post->scheduled_at)
                <span class="text-xs text-muted">📅 {{ $post->scheduled_at->translatedFormat('l d M, H:i') }}</span>
            @elseif($post->published_at)
                <span class="text-xs text-muted">✓ Publicat {{ $post->published_at->translatedFormat('d M, H:i') }}</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm border border-emerald-200">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-rose-50 text-rose-800 text-sm border border-rose-200">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($post->error_message)
        <div class="mb-4 px-4 py-3 rounded-lg bg-coralsoft text-coralh text-sm border border-coral/30">
            <strong>Eroare publicare:</strong> {{ $post->error_message }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] gap-6">

        {{-- LEFT: image + content --}}
        <div class="space-y-6">
            {{-- Image card --}}
            <div class="bg-white rounded-xl border border-line overflow-hidden">
                <div class="flex items-center justify-center bg-cream" style="min-height: 320px;">
                    @if($post->image_url)
                        <img id="postImage" src="{{ $post->image_url }}" alt="" class="block max-w-full max-h-[70vh] h-auto w-auto object-contain">
                    @else
                        <div class="py-20 text-center text-muted text-sm">
                            <div class="text-4xl mb-2">🖼️</div>
                            Fără imagine
                            <div class="text-[11px] mt-1 text-muted">Worker-ul de backfill o va genera automat</div>
                        </div>
                    @endif
                </div>
                @if($isEditable)
                    <div class="border-t border-line p-4 space-y-2">
                        <label class="block text-[11px] font-semibold text-muted uppercase">Prompt imagine</label>
                        <textarea id="imagePrompt" rows="3"
                                  class="w-full rounded-lg border-line text-xs font-mono focus:border-coral focus:ring-coral"
                                  placeholder="Ce să conțină imaginea...">{{ $post->image_prompt }}</textarea>
                        <button type="button" id="btnRegenImage"
                                class="w-full px-3 py-2 text-xs font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 disabled:opacity-50">
                            🖼️ Generează imagine nouă cu acest prompt
                        </button>
                    </div>
                @elseif($post->image_prompt)
                    <div class="border-t border-line p-4">
                        <label class="block text-[11px] font-semibold text-muted uppercase mb-1">Prompt imagine</label>
                        <p class="text-xs font-mono text-muted whitespace-pre-wrap">{{ $post->image_prompt }}</p>
                    </div>
                @endif
            </div>

            {{-- Content card --}}
            <form id="contentForm" method="POST" action="{{ route('admin.social.update', $post) }}" class="bg-white rounded-xl border border-line p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-semibold text-muted uppercase mb-1">Conținut</label>
                    <textarea name="content" id="postContent" rows="10" {{ $isEditable ? '' : 'readonly' }}
                        class="w-full px-3 py-2 text-sm rounded-lg border border-line focus:border-coral focus:ring-coral {{ $isEditable ? '' : 'bg-cream text-muted' }}">{{ old('content', $post->content) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-muted uppercase mb-1">Hashtag-uri (opțional)</label>
                        <input type="text" name="hashtags" {{ $isEditable ? '' : 'readonly' }}
                            value="{{ old('hashtags', is_array($post->hashtags) ? implode(', ', $post->hashtags) : '') }}"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-line {{ $isEditable ? '' : 'bg-cream' }}">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-muted uppercase mb-1">Programat la</label>
                        <input type="datetime-local" name="scheduled_at" {{ $isEditable ? '' : 'readonly' }}
                            value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-line {{ $isEditable ? '' : 'bg-cream' }}">
                    </div>
                </div>

                @if($isEditable)
                    <div class="flex flex-wrap gap-2 pt-3 border-t border-line">
                        <button type="submit" name="action" value="save"
                            class="px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                            💾 Salvează draft
                        </button>
                        <button type="submit" name="action" value="schedule"
                            class="px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            📅 Salvează & programează
                        </button>
                    </div>
                @else
                    <div class="text-xs text-muted italic pt-3 border-t border-line">Acest post nu mai poate fi editat (status: {{ $post->status }}).</div>
                @endif
            </form>

            {{-- Regenerate text card --}}
            @if($isEditable)
                <div class="bg-white rounded-xl border border-line p-5">
                    <label class="block text-[11px] font-semibold text-muted uppercase mb-2">Regenerează text cu instrucțiuni</label>
                    <textarea id="textInstructions" rows="2"
                        class="w-full px-3 py-2 text-xs rounded-lg border border-line focus:border-coral focus:ring-coral"
                        placeholder="Ex: mai scurt, ton mai entuziast, menționează prețul..."></textarea>
                    <button type="button" id="btnRegenText"
                        class="mt-2 w-full px-3 py-2 text-xs font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 disabled:opacity-50">
                        ✏️ Regenerează textul
                    </button>
                </div>
            @endif
        </div>

        {{-- RIGHT: actions + meta --}}
        <aside class="space-y-4 lg:sticky lg:top-4 self-start">
            @if($isEditable)
                <div class="bg-white rounded-xl border border-line p-4 space-y-2">
                    <button type="button" id="btnApprove"
                        class="w-full px-4 py-3 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">
                        ✓ Aprobă & programează
                    </button>
                    <button type="button" id="btnPublish"
                        class="w-full px-4 py-2.5 text-sm font-semibold text-inkSoft bg-white border border-line rounded-lg hover:bg-cream">
                        🚀 Publică acum
                    </button>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" id="btnDuplicate"
                            class="px-3 py-2 text-xs font-medium text-inkSoft bg-white border border-line rounded-lg hover:bg-cream">📋 Duplică</button>
                        <button type="button" id="btnReject"
                            class="px-3 py-2 text-xs font-medium text-coralh bg-white border border-red-300 rounded-lg hover:bg-coralsoft">❌ Refuză</button>
                    </div>
                    <button type="button" id="btnDelete"
                        class="w-full px-3 py-2 text-xs font-semibold text-white bg-slate-700 rounded-lg hover:bg-slate-800">
                        🗑️ Șterge
                    </button>
                </div>

                {{-- Reject form --}}
                <div id="rejectForm" class="hidden bg-coralsoft border border-coral/30 rounded-xl p-4 space-y-2">
                    <p class="text-xs font-semibold text-coralh">De ce refuzi?</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach(['text' => 'Text', 'tone' => 'Ton', 'length' => 'Lungime', 'image' => 'Imagine', 'topic' => 'Subiect', 'other' => 'Altceva'] as $val => $label)
                            <button type="button" data-cat="{{ $val }}" class="reject-chip px-2 py-1 text-[11px] font-medium border border-red-300 text-coralh rounded-full hover:bg-coralsoft">{{ $label }}</button>
                        @endforeach
                    </div>
                    <textarea id="rejectFeedback" rows="2" placeholder="Ce nu merge? (opțional)"
                        class="w-full text-xs rounded border-red-300 focus:border-coral focus:ring-coral"></textarea>
                    <div class="flex gap-2">
                        <button type="button" id="btnRejectConfirm" class="flex-1 px-3 py-1.5 text-xs font-semibold text-white bg-coral rounded hover:bg-coral">Confirmă refuz</button>
                        <button type="button" id="btnRejectCancel" class="px-3 py-1.5 text-xs text-muted">Anulează</button>
                    </div>
                </div>
            @endif

            @if($post->external_url)
                <a href="{{ $post->external_url }}" target="_blank" rel="noopener"
                   class="block bg-white rounded-xl border border-line p-4 text-sm text-blue-600 hover:bg-cream">
                    🔗 Vezi pe platformă →
                </a>
            @endif

            {{-- Group siblings --}}
            @if(!empty($fanout) && count($fanout) > 1)
                <div class="bg-white rounded-xl border border-line p-4">
                    <p class="text-[11px] font-semibold uppercase text-muted mb-2">Grup ({{ count($fanout) }} variante)</p>
                    <ul class="space-y-1.5">
                        @foreach($fanout as $sib)
                            <li class="flex items-center justify-between gap-2 text-xs">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $platformBg[$sib->platform] ?? 'bg-slate-400' }}"></span>
                                    <span class="text-inkSoft">{{ $sib->platform }}</span>
                                    <span class="text-muted">· {{ $sib->post_type }}</span>
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded {{ $statusColors[$sib->status] ?? 'bg-cream' }}">{{ strtoupper($sib->status) }}</span>
                                </span>
                                @if($sib->id === $post->id)
                                    <span class="text-muted">curent</span>
                                @else
                                    <a href="{{ route('admin.social.edit', $sib->id) }}" class="text-blue-600 hover:underline">deschide</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Variants --}}
            @if($post->variants && $post->variants->count())
                <div class="bg-white rounded-xl border border-line p-4">
                    <p class="text-[11px] font-semibold uppercase text-muted mb-2">Versiuni anterioare</p>
                    <div class="space-y-1.5">
                        @foreach($post->variants as $v)
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="text-muted">{{ $v->kind === 'image' ? '🖼️' : '✏️' }} {{ $v->created_at?->diffForHumans() }}</span>
                                <button type="button" class="btn-use-variant text-blue-600 hover:underline" data-variant-id="{{ $v->id }}">restaurează</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Rejections history --}}
            @if($post->rejections && $post->rejections->count())
                <div class="bg-white rounded-xl border border-line p-4">
                    <p class="text-[11px] font-semibold uppercase text-muted mb-2">Refuzuri anterioare</p>
                    <ul class="space-y-1 text-xs text-muted">
                        @foreach($post->rejections as $r)
                            <li>• <b>{{ $r->reason_category ?? 'other' }}</b> {{ $r->feedback ? '— ' . $r->feedback : '' }} <span class="text-muted">({{ $r->created_at?->diffForHumans() }})</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg"></div>

@push('scripts')
<script>
(() => {
    const root = document.getElementById('postRoot');
    const csrf = root.dataset.csrf;
    const postId = root.dataset.postId;
    const indexUrl = @json(route('admin.social.index'));
    let rejectCategory = 'other';

    const $ = (id) => document.getElementById(id);

    function toast(msg, ms = 2500) {
        const t = $('toast');
        t.textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => t.classList.add('hidden'), ms);
    }

    async function api(url, opts = {}) {
        const headers = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
        if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(opts.body);
        }
        const res = await fetch(url, { ...opts, headers: { ...headers, ...(opts.headers || {}) } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
        return data;
    }

    // ---- Regen image ----
    $('btnRegenImage')?.addEventListener('click', async () => {
        const btn = $('btnRegenImage');
        const prompt = $('imagePrompt').value.trim();
        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = 'Se generează...';
        try {
            const d = await api(`/admin/social/post/${postId}/regenerate-image`, {
                method: 'POST', body: { prompt }
            });
            if (d.image_url) {
                let img = $('postImage');
                if (!img) { location.reload(); return; }
                img.src = d.image_url + (d.image_url.includes('?') ? '&' : '?') + '_=' + Date.now();
                toast('Imagine generată');
            }
        } catch (e) { toast('Eroare: ' + e.message, 4000); }
        finally { btn.disabled = false; btn.textContent = oldText; }
    });

    // ---- Regen text ----
    $('btnRegenText')?.addEventListener('click', async () => {
        const btn = $('btnRegenText');
        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = 'Se generează...';
        try {
            const d = await api(`/admin/social/post/${postId}/regenerate-text`, {
                method: 'POST', body: { instructions: $('textInstructions').value }
            });
            if (d.content) { $('postContent').value = d.content; toast('Text regenerat'); }
        } catch (e) { toast('Eroare: ' + e.message, 4000); }
        finally { btn.disabled = false; btn.textContent = oldText; }
    });

    // ---- Approve ----
    $('btnApprove')?.addEventListener('click', async () => {
        const btn = $('btnApprove');
        btn.disabled = true;
        try {
            const d = await api(`/admin/social/post/${postId}/approve`, { method: 'POST' });
            toast('Programat: ' + (d.scheduled_human || d.scheduled_at));
            setTimeout(() => location.href = indexUrl, 900);
        } catch (e) { toast('Eroare: ' + e.message, 4000); btn.disabled = false; }
    });

    // ---- Publish now ----
    $('btnPublish')?.addEventListener('click', async () => {
        if (!confirm('Publici acum această postare?')) return;
        const btn = $('btnPublish');
        btn.disabled = true;
        try {
            await api(`/admin/social/post/${postId}/publish`, { method: 'POST' });
            toast('Trimis la publicare');
            setTimeout(() => location.reload(), 900);
        } catch (e) { toast('Eroare: ' + e.message, 4000); btn.disabled = false; }
    });

    // ---- Duplicate ----
    $('btnDuplicate')?.addEventListener('click', async () => {
        try {
            const d = await api(`/admin/social/post/${postId}/duplicate`, { method: 'POST' });
            if (d.id) location.href = `/admin/social/post/${d.id}/edit`;
        } catch (e) { toast('Eroare: ' + e.message, 4000); }
    });

    // ---- Delete ----
    $('btnDelete')?.addEventListener('click', async () => {
        if (!confirm('Ștergi această postare?')) return;
        try {
            await api(`/admin/social/post/${postId}`, { method: 'DELETE' });
            toast('Șters');
            setTimeout(() => location.href = indexUrl, 700);
        } catch (e) { toast('Eroare: ' + e.message, 4000); }
    });

    // ---- Reject ----
    $('btnReject')?.addEventListener('click', () => $('rejectForm').classList.remove('hidden'));
    $('btnRejectCancel')?.addEventListener('click', () => $('rejectForm').classList.add('hidden'));
    document.querySelectorAll('.reject-chip').forEach(c => {
        c.addEventListener('click', () => {
            rejectCategory = c.dataset.cat;
            document.querySelectorAll('.reject-chip').forEach(x => x.classList.remove('bg-coral','text-white'));
            c.classList.add('bg-coral','text-white');
        });
    });
    $('btnRejectConfirm')?.addEventListener('click', async () => {
        try {
            await api(`/admin/social/post/${postId}/reject`, {
                method: 'POST',
                body: { reason_category: rejectCategory, feedback: $('rejectFeedback').value }
            });
            toast('Refuzat');
            setTimeout(() => location.href = indexUrl, 700);
        } catch (e) { toast('Eroare: ' + e.message, 4000); }
    });

    // ---- Use variant ----
    document.querySelectorAll('.btn-use-variant').forEach(b => {
        b.addEventListener('click', async () => {
            try {
                await api(`/admin/social/post/${postId}/variant/${b.dataset.variantId}/use`, { method: 'POST' });
                toast('Versiune restaurată');
                setTimeout(() => location.reload(), 700);
            } catch (e) { toast('Eroare: ' + e.message, 4000); }
        });
    });
})();
</script>
@endpush
@endsection
