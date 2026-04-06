@extends('layouts.admin')

@section('title', 'Social Media')
@section('breadcrumb', 'Social Media Management')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Social Media</h1>
            <p class="text-sm text-slate-500 mt-1">Apasă pe orice postare pentru a o vedea, regenera sau respinge</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.social.style') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Style</a>
            <a href="{{ route('admin.social.accounts') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Conturi</a>
            <a href="{{ route('admin.social.schedule') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Programare</a>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <a href="{{ route('admin.social.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-400">
            <h3 class="text-xs font-semibold text-slate-500 uppercase">Total</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_posts'] }}</p>
        </a>
        <a href="{{ route('admin.social.index', ['status' => 'published']) }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-green-400">
            <h3 class="text-xs font-semibold text-slate-500 uppercase">Publicate</h3>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['published'] }}</p>
        </a>
        <a href="{{ route('admin.social.index', ['status' => 'scheduled']) }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-blue-400">
            <h3 class="text-xs font-semibold text-slate-500 uppercase">Programate</h3>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['scheduled'] }}</p>
        </a>
        <a href="{{ route('admin.social.index', ['status' => 'failed']) }}" class="bg-white rounded-xl border border-slate-200 p-4 hover:border-red-400">
            <h3 class="text-xs font-semibold text-slate-500 uppercase">Eșuate</h3>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['failed'] }}</p>
        </a>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase">Azi</h3>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['today'] }}</p>
        </div>
    </div>

    {{-- Quick generate --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
        <h2 class="text-base font-semibold text-slate-900 mb-3">Generează postare nouă</h2>
        <form method="POST" action="{{ route('admin.social.generate') }}" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <select name="platform" class="rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 w-full sm:w-36">
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="blog">Blog</option>
            </select>
            <input type="text" name="topic" placeholder="Subiect..." required class="flex-1 rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Generează</button>
        </form>
    </div>

    {{-- Posts grid (mobile cards + desktop table feel) --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 sm:px-6 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Postări</h2>
            <span class="text-xs text-slate-500">{{ $posts->total() }} total</span>
        </div>

        <ul class="divide-y divide-slate-100">
            @forelse($posts as $post)
                @php
                    $statusColors = [
                        'draft' => 'bg-slate-100 text-slate-700',
                        'scheduled' => 'bg-blue-100 text-blue-700',
                        'publishing' => 'bg-amber-100 text-amber-700',
                        'published' => 'bg-green-100 text-green-700',
                        'failed' => 'bg-red-100 text-red-700',
                    ];
                    $platformIcon = ['facebook' => 'FB', 'instagram' => 'IG', 'blog' => 'Blog'][$post->platform] ?? $post->platform;
                    $platformBg = ['facebook' => 'bg-blue-600', 'instagram' => 'bg-pink-600', 'blog' => 'bg-slate-600'][$post->platform] ?? 'bg-slate-600';
                @endphp
                <li>
                    <button type="button" onclick="openSocialPost({{ $post->id }})" class="w-full flex items-center gap-3 px-4 sm:px-6 py-3 hover:bg-slate-50 text-left">
                        {{-- Thumbnail --}}
                        <div class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 relative">
                            @if($post->image_url)
                                <img src="{{ $post->image_url }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300 text-xs">no image</div>
                            @endif
                            <span class="absolute bottom-0.5 left-0.5 px-1 py-0.5 text-[9px] font-bold text-white {{ $platformBg }} rounded">{{ $platformIcon }}</span>
                        </div>
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $statusColors[$post->status] ?? 'bg-slate-100' }}">{{ strtoupper($post->status) }}</span>
                                @if($post->post_type === 'story')
                                    <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-purple-100 text-purple-700">STORY</span>
                                @endif
                                <span class="text-[11px] text-slate-500">
                                    @if($post->status === 'published' && $post->published_at)
                                        Publicat {{ $post->published_at->format('d.m H:i') }}
                                    @elseif($post->scheduled_at)
                                        {{ $post->scheduled_at->isPast() ? 'Programat' : 'Va apărea' }} {{ $post->scheduled_at->format('d.m H:i') }}
                                    @endif
                                </span>
                            </div>
                            <p class="text-sm text-slate-700 line-clamp-2">{{ \Illuminate\Support\Str::limit($post->content, 140) }}</p>
                        </div>
                        <svg class="hidden sm:block w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-slate-500">Nicio postare. Generează prima!</li>
            @endforelse
        </ul>

        @if($posts->hasPages())
            <div class="px-4 sm:px-6 py-3 border-t border-slate-200">{{ $posts->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal --}}
<div id="postModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" onclick="closeSocialPost()"></div>
    <div class="relative min-h-screen flex items-start sm:items-center justify-center p-0 sm:p-4">
        <div class="relative bg-white w-full sm:max-w-3xl sm:rounded-2xl shadow-2xl min-h-screen sm:min-h-0 sm:my-8">
            {{-- Header --}}
            <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-4 sm:px-6 py-3 flex items-center justify-between sm:rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <span id="modal-platform" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700"></span>
                    <span id="modal-status" class="px-2 py-0.5 text-xs font-semibold rounded-full"></span>
                    <span id="modal-time" class="text-xs text-slate-500"></span>
                </div>
                <button type="button" onclick="closeSocialPost()" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-5">
                {{-- Image --}}
                <div id="modal-image-wrap" class="relative bg-slate-100 rounded-xl overflow-hidden">
                    <img id="modal-image" src="" alt="" class="w-full max-h-[60vh] object-contain bg-slate-900">
                    <div id="modal-image-loading" class="hidden absolute inset-0 flex items-center justify-center bg-white/80">
                        <div class="text-sm text-slate-600">Se generează imagine nouă…</div>
                    </div>
                </div>

                {{-- Text content --}}
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Text</label>
                    <p id="modal-content" class="mt-2 text-slate-800 whitespace-pre-wrap text-sm leading-relaxed"></p>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Hashtag-uri</label>
                    <p id="modal-hashtags" class="mt-2 text-sm text-blue-600"></p>
                </div>

                <div id="modal-error-wrap" class="hidden">
                    <label class="text-xs font-semibold text-red-500 uppercase">Eroare</label>
                    <p id="modal-error" class="mt-2 text-sm text-red-700 bg-red-50 p-3 rounded-lg"></p>
                </div>

                {{-- External link for published --}}
                <div id="modal-external-wrap" class="hidden">
                    <a id="modal-external" href="#" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                        Vezi pe platformă →
                    </a>
                </div>

                {{-- Action buttons (only when editable) --}}
                <div id="modal-actions" class="hidden border-t border-slate-200 pt-4 space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" onclick="regenImage()" class="px-3 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            🖼️ Imagine nouă
                        </button>
                        <button type="button" onclick="regenText()" class="px-3 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            ✏️ Text nou
                        </button>
                        <button type="button" onclick="showRejectForm()" class="px-3 py-2.5 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">
                            ❌ Refuză & învață
                        </button>
                        <button type="button" onclick="deletePost()" class="px-3 py-2.5 text-sm font-semibold text-white bg-red-600 border border-red-600 rounded-lg hover:bg-red-700">
                            🗑️ Șterge
                        </button>
                    </div>

                    {{-- Reject form --}}
                    <div id="reject-form" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 space-y-3">
                        <p class="text-sm font-semibold text-red-900">Ce nu îți place? (Sambla va învăța din asta)</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['text' => 'Text slab', 'tone' => 'Ton greșit', 'length' => 'Prea lung/scurt', 'image' => 'Imagine urâtă', 'topic' => 'Subiect prost', 'hashtags' => 'Hashtag-uri', 'other' => 'Altceva'] as $val => $label)
                                <button type="button" onclick="setRejectCategory('{{ $val }}', this)" class="reject-chip px-3 py-1.5 text-xs font-medium border border-red-300 text-red-700 rounded-full hover:bg-red-100">{{ $label }}</button>
                            @endforeach
                        </div>
                        <textarea id="reject-feedback" rows="2" placeholder="Detalii (opțional)..." class="w-full text-sm rounded-lg border-red-300 focus:border-red-500 focus:ring-red-500"></textarea>
                        <div class="flex gap-2">
                            <button type="button" onclick="confirmReject()" class="flex-1 px-3 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">Confirmă & șterge</button>
                            <button type="button" onclick="document.getElementById('reject-form').classList.add('hidden')" class="px-3 py-2 text-sm text-slate-600 hover:text-slate-900">Anulează</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPostId = null;
let rejectCategory = 'other';
const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function openSocialPost(id) {
    currentPostId = id;
    document.getElementById('postModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('reject-form').classList.add('hidden');
    document.getElementById('modal-error-wrap').classList.add('hidden');
    document.getElementById('modal-content').textContent = 'Se încarcă...';

    try {
        const res = await fetch(`/admin/social/post/${id}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        renderPost(data);
    } catch (e) {
        document.getElementById('modal-content').textContent = 'Eroare la încărcare: ' + e.message;
    }
}

function renderPost(data) {
    const platformLabels = { facebook: 'Facebook', instagram: 'Instagram', blog: 'Blog' };
    const statusColors = {
        draft: 'bg-slate-100 text-slate-700',
        scheduled: 'bg-blue-100 text-blue-700',
        publishing: 'bg-amber-100 text-amber-700',
        published: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
    };

    document.getElementById('modal-platform').textContent = platformLabels[data.platform] || data.platform;
    const st = document.getElementById('modal-status');
    st.textContent = (data.status || '').toUpperCase();
    st.className = 'px-2 py-0.5 text-xs font-semibold rounded-full ' + (statusColors[data.status] || 'bg-slate-100');
    document.getElementById('modal-time').textContent = data.published_at ? ('Publicat ' + data.published_at) : (data.scheduled_at ? ('Programat ' + data.scheduled_at) : '');
    document.getElementById('modal-content').textContent = data.content || '(fără text)';
    document.getElementById('modal-hashtags').textContent = (data.hashtags || []).map(h => h.startsWith('#') ? h : '#' + h).join(' ');

    const img = document.getElementById('modal-image');
    const wrap = document.getElementById('modal-image-wrap');
    if (data.image_url) {
        img.src = data.image_url;
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
    }

    if (data.error_message) {
        document.getElementById('modal-error-wrap').classList.remove('hidden');
        document.getElementById('modal-error').textContent = data.error_message;
    } else {
        document.getElementById('modal-error-wrap').classList.add('hidden');
    }

    if (data.external_url) {
        document.getElementById('modal-external-wrap').classList.remove('hidden');
        document.getElementById('modal-external').href = data.external_url;
    } else {
        document.getElementById('modal-external-wrap').classList.add('hidden');
    }

    document.getElementById('modal-actions').classList.toggle('hidden', !data.is_editable);
}

function closeSocialPost() {
    document.getElementById('postModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentPostId = null;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSocialPost(); });

async function regenImage() {
    if (!currentPostId) return;
    document.getElementById('modal-image-loading').classList.remove('hidden');
    try {
        const res = await fetch(`/admin/social/post/${currentPostId}/regenerate-image`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.image_url) {
            document.getElementById('modal-image').src = data.image_url + '?t=' + Date.now();
        } else {
            alert(data.error || 'Eroare');
        }
    } catch (e) { alert('Eroare: ' + e.message); }
    document.getElementById('modal-image-loading').classList.add('hidden');
}

async function regenText() {
    if (!currentPostId) return;
    const el = document.getElementById('modal-content');
    const old = el.textContent;
    el.textContent = 'Se generează text nou...';
    try {
        const res = await fetch(`/admin/social/post/${currentPostId}/regenerate-text`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.content) {
            el.textContent = data.content;
            document.getElementById('modal-hashtags').textContent = (data.hashtags || []).map(h => h.startsWith('#') ? h : '#' + h).join(' ');
        } else {
            el.textContent = old;
            alert(data.error || 'Eroare');
        }
    } catch (e) { el.textContent = old; alert('Eroare: ' + e.message); }
}

function showRejectForm() {
    document.getElementById('reject-form').classList.remove('hidden');
}
function setRejectCategory(cat, btn) {
    rejectCategory = cat;
    document.querySelectorAll('.reject-chip').forEach(b => b.classList.remove('bg-red-600', 'text-white'));
    btn.classList.add('bg-red-600', 'text-white');
}
async function confirmReject() {
    if (!currentPostId) return;
    const fb = document.getElementById('reject-feedback').value;
    try {
        const res = await fetch(`/admin/social/post/${currentPostId}/reject`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ reason_category: rejectCategory, feedback: fb }),
        });
        const data = await res.json();
        if (data.ok) {
            closeSocialPost();
            location.reload();
        } else {
            alert(data.error || 'Eroare');
        }
    } catch (e) { alert('Eroare: ' + e.message); }
}
async function deletePost() {
    if (!currentPostId) return;
    if (!confirm('Sigur vrei să ștergi postarea? Acțiunea nu poate fi anulată.')) return;
    try {
        const res = await fetch(`/admin/social/post/${currentPostId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        if (res.ok || res.redirected) {
            closeSocialPost();
            location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.error || 'Eroare la ștergere');
        }
    } catch (e) { alert('Eroare: ' + e.message); }
}
</script>
@endpush
@endsection
