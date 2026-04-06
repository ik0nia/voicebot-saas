@extends('layouts.admin')

@section('title', 'Social Media')
@section('breadcrumb', 'Social Media Management')

@section('content')
@php
    $statusColors = [
        'draft'      => 'bg-slate-100 text-slate-700',
        'scheduled'  => 'bg-blue-100 text-blue-700',
        'publishing' => 'bg-amber-100 text-amber-700',
        'published'  => 'bg-green-100 text-green-700',
        'failed'     => 'bg-red-100 text-red-700',
    ];
    $platformIcon = ['facebook' => 'FB', 'instagram' => 'IG', 'blog' => 'Blog'];
    $platformBg = ['facebook' => 'bg-blue-600', 'instagram' => 'bg-pink-600', 'blog' => 'bg-slate-600'];

    // Group posts by scheduled/published day for a Gmail/Buffer-style inbox.
    $grouped = $posts->getCollection()->groupBy(function ($p) {
        $dt = $p->scheduled_at ?? $p->published_at ?? $p->created_at;
        if (!$dt) return '—';
        if ($dt->isToday()) return 'Astăzi';
        if ($dt->isYesterday()) return 'Ieri';
        if ($dt->isTomorrow()) return 'Mâine';
        if ($dt->isCurrentWeek()) return $dt->translatedFormat('l');
        return $dt->translatedFormat('d M Y');
    });
@endphp

<div class="relative" id="socialRoot" data-csrf="{{ csrf_token() }}">

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Social Media</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded">j</kbd>
                <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded">k</kbd>
                navigare ·
                <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded">e</kbd> editează ·
                <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded">a</kbd> publică ·
                <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 border border-slate-200 rounded">?</kbd> ajutor
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.social.style') }}" class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Style</a>
            <a href="{{ route('admin.social.accounts') }}" class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Conturi</a>
            <a href="{{ route('admin.social.schedule') }}" class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Programare</a>
        </div>
    </div>

    {{-- Stat chips row --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-4">
        @foreach([
            ['label' => 'Total', 'value' => $stats['total_posts'], 'color' => 'text-slate-900', 'status' => null],
            ['label' => 'De review', 'value' => $stats['draft'], 'color' => $stats['draft'] < 100 ? 'text-amber-600' : 'text-slate-700', 'status' => 'draft'],
            ['label' => 'Programate', 'value' => $stats['scheduled'], 'color' => 'text-blue-600', 'status' => 'scheduled'],
            ['label' => 'Publicate', 'value' => $stats['published'], 'color' => 'text-green-600', 'status' => 'published'],
            ['label' => 'Eșuate', 'value' => $stats['failed'], 'color' => 'text-red-600', 'status' => 'failed'],
        ] as $chip)
            <a href="{{ route('admin.social.index', $chip['status'] ? ['status' => $chip['status']] : []) }}"
               class="bg-white rounded-lg border border-slate-200 px-3 py-2 hover:border-slate-400 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase">{{ $chip['label'] }}</span>
                <span class="text-lg font-bold {{ $chip['color'] }}">{{ $chip['value'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Toolbar: search + filters + generate --}}
    <form method="GET" action="{{ route('admin.social.index') }}" class="bg-white rounded-xl border border-slate-200 p-3 mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Caută în text, prompt..."
               class="flex-1 min-w-[180px] rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
        <select name="platform" class="rounded-lg border-slate-300 text-sm">
            <option value="">Toate platformele</option>
            @foreach(['facebook','instagram','blog'] as $p)
                <option value="{{ $p }}" @selected(request('platform')===$p)>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border-slate-300 text-sm">
            <option value="">Orice status</option>
            @foreach(['draft','scheduled','publishing','published','failed'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-slate-300 text-sm" title="De la">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-slate-300 text-sm" title="Până la">
        <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-slate-800 rounded-lg hover:bg-slate-900">Filtrează</button>
        @if(request()->hasAny(['q','platform','status','from','to']))
            <a href="{{ route('admin.social.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Resetează</a>
        @endif
    </form>

    {{-- Quick generate --}}
    <details class="bg-white rounded-xl border border-slate-200 p-4 mb-4" {{ request('new') ? 'open' : '' }}>
        <summary class="cursor-pointer text-sm font-semibold text-slate-900">➕ Generează postare nouă</summary>
        <form method="POST" action="{{ route('admin.social.generate') }}" class="mt-3 flex flex-col sm:flex-row gap-2">
            @csrf
            <select name="platform" class="rounded-lg border-slate-300 text-sm w-full sm:w-36">
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="blog">Blog</option>
            </select>
            <input type="text" name="topic" placeholder="Subiect..." required class="flex-1 rounded-lg border-slate-300 text-sm">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Generează</button>
        </form>
    </details>

    {{-- Main split-pane layout --}}
    <div class="flex gap-4 items-start">
        {{-- LEFT: grouped list --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden flex-1 min-w-0">
            <div class="px-4 py-2.5 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10">
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1.5 text-xs text-slate-500">
                        <input type="checkbox" id="selectAll" class="rounded border-slate-300">
                        Selectează tot
                    </label>
                    <span class="text-xs text-slate-500">{{ $posts->total() }} postări</span>
                </div>
                <div id="bulkBar" class="hidden items-center gap-2 flex-wrap">
                    <span id="bulkCount" class="text-xs font-semibold text-slate-700">0 selectate</span>
                    <button type="button" onclick="bulkAction('publish')" class="px-2 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700">Publică</button>
                    <div class="flex items-center gap-1">
                        <input type="datetime-local" id="bulkRescheduleAt" class="rounded border-slate-300 text-xs px-2 py-1">
                        <button type="button" onclick="bulkAction('reschedule')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Mută</button>
                    </div>
                    <button type="button" onclick="bulkAction('delete')" class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700">Șterge</button>
                </div>
            </div>

            <ul class="divide-y divide-slate-100" id="postList">
                @forelse($grouped as $groupLabel => $groupPosts)
                    <li class="bg-slate-50 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $groupLabel }}</li>
                    @foreach($groupPosts as $post)
                        <li class="post-row group" data-post-id="{{ $post->id }}">
                            <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 cursor-pointer relative" onclick="openPost({{ $post->id }}, event)">
                                <input type="checkbox" class="post-check rounded border-slate-300 shrink-0" value="{{ $post->id }}" onclick="event.stopPropagation(); updateBulk();">

                                <div class="shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 relative">
                                    @if($post->image_url)
                                        <img src="{{ $post->image_url }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-300 text-[10px]">no img</div>
                                    @endif
                                    <span class="absolute bottom-0 left-0 px-1 py-0.5 text-[9px] font-bold text-white {{ $platformBg[$post->platform] ?? 'bg-slate-600' }} rounded-tr">{{ $platformIcon[$post->platform] ?? $post->platform }}</span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded {{ $statusColors[$post->status] ?? 'bg-slate-100' }}">{{ strtoupper($post->status) }}</span>
                                        @if($post->post_type === 'story')
                                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-700">STORY</span>
                                        @endif
                                        @if(($post->regen_count ?? 0) > 0)
                                            <span class="inline-flex px-1.5 py-0.5 text-[10px] text-amber-700" title="Regenerări">↻{{ $post->regen_count }}</span>
                                        @endif
                                        <span class="text-[11px] text-slate-400 ml-auto">
                                            @if($post->status === 'published' && $post->published_at)
                                                {{ $post->published_at->format('H:i') }}
                                            @elseif($post->scheduled_at)
                                                {{ $post->scheduled_at->format('H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-700 line-clamp-1">{{ \Illuminate\Support\Str::limit($post->content, 120) }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-400">Niciun post. Generează primul din butonul de sus.</li>
                @endforelse
            </ul>

            @if($posts->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">{{ $posts->onEachSide(1)->links() }}</div>
            @endif
        </div>

        {{-- RIGHT: side panel (desktop sticky flex column; mobile fixed bottom sheet) --}}
        <aside id="postPanel"
               class="hidden lg:flex lg:flex-col lg:sticky lg:top-4 lg:w-[560px] lg:shrink-0
                      fixed inset-x-0 bottom-0 lg:inset-auto z-40
                      bg-white lg:rounded-xl lg:border lg:border-slate-200 lg:shadow-xl
                      max-h-[92vh] lg:max-h-[calc(100vh-7rem)] overflow-hidden">
            {{-- Empty state --}}
            <div id="panelEmpty" class="flex-1 flex items-center justify-center p-10 text-center text-sm text-slate-400">
                <div>
                    <div class="text-3xl mb-2">👈</div>
                    Selectează o postare din stânga.<br>
                    <span class="text-xs mt-2 block">sau folosește <kbd class="px-1 py-0.5 text-[10px] bg-slate-100 border rounded">j</kbd>/<kbd class="px-1 py-0.5 text-[10px] bg-slate-100 border rounded">k</kbd></span>
                </div>
            </div>

            {{-- Loaded state --}}
            <div id="panelContent" class="hidden flex-1 overflow-y-auto">
                {{-- Sticky header --}}
                <div class="sticky top-0 bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-2 z-10">
                    <button type="button" data-close-panel class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg" aria-label="Închide">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <span id="panel-platform" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700"></span>
                    <span id="panel-status" class="px-2 py-0.5 text-xs font-semibold rounded-full"></span>
                    <span id="panel-saved" class="text-[11px] text-slate-400 ml-auto">—</span>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="navPost(-1)" class="p-1 text-slate-400 hover:text-slate-700" title="Anterior (k)" aria-label="Anterior">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" onclick="navPost(1)" class="p-1 text-slate-400 hover:text-slate-700" title="Următor (j)" aria-label="Următor">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <div class="p-4 space-y-4">
                    {{-- Image: neutral canvas, centered, respects native aspect ratio --}}
                    <div id="panel-image-wrap" class="relative hidden">
                        <div class="flex items-center justify-center bg-slate-50 rounded-lg border border-slate-200 overflow-hidden" style="min-height: 180px;">
                            <img id="panel-image" src="" alt="" class="block max-w-full max-h-[55vh] h-auto w-auto object-contain">
                        </div>
                        <div id="panel-image-loading" class="hidden absolute inset-0 flex items-center justify-center bg-white/85 rounded-lg text-sm text-slate-600">
                            <svg class="animate-spin w-5 h-5 mr-2 text-slate-500" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                            Se generează...
                        </div>
                    </div>

                    {{-- Error message --}}
                    <div id="panel-error-wrap" class="hidden">
                        <p class="text-xs font-semibold text-red-700 uppercase">Eroare publicare</p>
                        <p id="panel-error" class="mt-1 text-xs text-red-700 bg-red-50 border border-red-200 p-2 rounded"></p>
                    </div>

                    {{-- Content editor --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-500 uppercase">Text postare</label>
                            <button id="panel-undo-text" type="button" onclick="undoTextRegen()" class="hidden text-[11px] text-amber-600 hover:text-amber-800">↶ revino la text-ul anterior</button>
                        </div>
                        <div id="panel-text-diff" class="hidden mt-1 text-xs text-slate-500 bg-amber-50 border border-amber-200 rounded p-2 leading-relaxed"></div>
                        <textarea id="panel-content" rows="8"
                                  class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                                  placeholder="Textul postării..."></textarea>
                    </div>

                    {{-- Schedule --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase">Programat pentru</label>
                            <input type="datetime-local" id="panel-scheduled" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500 uppercase">Tip</label>
                            <select id="panel-post-type" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                                <option value="post">Post</option>
                                <option value="story">Story</option>
                                <option value="reel">Reel</option>
                            </select>
                        </div>
                    </div>

                    {{-- Image prompt readonly --}}
                    <details id="panel-prompt-wrap">
                        <summary class="text-xs font-semibold text-slate-500 uppercase cursor-pointer">Prompt imagine</summary>
                        <p id="panel-image-prompt" class="mt-1 text-xs text-slate-600 bg-slate-50 border border-slate-200 p-2 rounded font-mono"></p>
                    </details>

                    {{-- External link --}}
                    <div id="panel-external-wrap" class="hidden">
                        <a id="panel-external" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800">
                            Vezi pe platformă →
                        </a>
                    </div>

                    {{-- Variants --}}
                    <div id="panel-variants-wrap" class="hidden">
                        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Versiuni anterioare</p>
                        <div id="panel-variants" class="flex flex-wrap gap-1"></div>
                    </div>

                    {{-- Past rejections --}}
                    <div id="panel-rejections-wrap" class="hidden">
                        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Refuzuri anterioare</p>
                        <ul id="panel-rejections" class="text-xs text-slate-600 space-y-0.5"></ul>
                    </div>
                </div>

                {{-- Sticky footer actions --}}
                <div class="sticky bottom-0 bg-white border-t border-slate-200 px-4 py-3 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="regenImage()" id="btn-regen-img" class="px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 disabled:opacity-50">🖼️ Imagine nouă</button>
                        <button type="button" onclick="regenText()" id="btn-regen-txt" class="px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 disabled:opacity-50">✏️ Text nou</button>
                        <button type="button" onclick="duplicatePost()" class="px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50">📋 Duplică</button>
                        <button type="button" onclick="showRejectForm()" class="px-3 py-2 text-xs font-medium text-red-700 bg-white border border-red-300 rounded hover:bg-red-50">❌ Refuză</button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="deletePost()" class="px-2 py-2.5 text-xs font-semibold text-white bg-slate-700 rounded hover:bg-slate-800">🗑️ Șterge</button>
                        <button type="button" onclick="publishNow()" id="btn-publish" class="px-2 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50">Publică acum</button>
                        <button type="button" onclick="approvePost()" id="btn-approve" class="px-2 py-2.5 text-sm font-bold text-white bg-green-600 rounded hover:bg-green-700">✓ Aprobă →</button>
                    </div>
                    <p id="panel-next-slot" class="text-[11px] text-slate-500 text-center">—</p>

                    {{-- Reject form (inline) --}}
                    <div id="reject-form" class="hidden bg-red-50 border border-red-200 rounded p-3 space-y-2">
                        <div class="flex flex-wrap gap-1">
                            @foreach(['text' => 'Text', 'tone' => 'Ton', 'length' => 'Lungime', 'image' => 'Imagine', 'topic' => 'Subiect', 'other' => 'Altceva'] as $val => $label)
                                <button type="button" data-cat="{{ $val }}" onclick="setRejectCategory('{{ $val }}', this)" class="reject-chip px-2 py-1 text-[11px] font-medium border border-red-300 text-red-700 rounded-full hover:bg-red-100">{{ $label }}</button>
                            @endforeach
                        </div>
                        <textarea id="reject-feedback" rows="2" placeholder="Ce nu merge? (opțional)" class="w-full text-xs rounded border-red-300 focus:border-red-500 focus:ring-red-500"></textarea>
                        <div class="flex gap-2">
                            <button type="button" onclick="confirmReject()" class="flex-1 px-3 py-1.5 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">Confirmă</button>
                            <button type="button" onclick="document.getElementById('reject-form').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-600">Anulează</button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg flex items-center gap-3">
    <span id="toast-msg"></span>
    <button id="toast-undo" type="button" class="hidden text-amber-300 font-semibold hover:text-amber-200">Anulează</button>
</div>

{{-- Shortcuts help overlay --}}
<div id="helpOverlay" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-3">Scurtături tastatură</h2>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">j</kbd> / <kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">↓</kbd></dt><dd class="text-slate-600">Post următor</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">k</kbd> / <kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">↑</kbd></dt><dd class="text-slate-600">Post anterior</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">e</kbd></dt><dd class="text-slate-600">Editează textul</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">a</kbd></dt><dd class="text-slate-600">Aprobă (→ swipe dreapta pe mobil)</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">r</kbd></dt><dd class="text-slate-600">Refuză</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">⌫</kbd></dt><dd class="text-slate-600">Șterge (cu confirmare)</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">Esc</kbd></dt><dd class="text-slate-600">Închide panel</dd>
            <dt><kbd class="px-1.5 py-0.5 font-mono bg-slate-100 border rounded">?</kbd></dt><dd class="text-slate-600">Acest ajutor</dd>
        </dl>
        <button type="button" onclick="document.getElementById('helpOverlay').classList.add('hidden')" class="mt-4 w-full px-3 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg">Închide</button>
    </div>
</div>

@push('scripts')
<script>
// ==== DEBUG: visible JS error banner (so the user can screenshot issues) ====
window.addEventListener('error', (e) => {
    const b = document.getElementById('jsErrorBanner');
    if (!b) return;
    b.classList.remove('hidden');
    b.textContent = 'JS error: ' + (e.message || e.error) + ' @ ' + (e.filename || '') + ':' + (e.lineno || '');
});
</script>
<div id="jsErrorBanner" class="hidden fixed top-0 inset-x-0 z-[100] bg-red-600 text-white text-xs px-3 py-2 font-mono"></div>
<script>
(() => {
    const csrf = document.getElementById('socialRoot').dataset.csrf;
    const postIds = Array.from(document.querySelectorAll('.post-row')).map(r => +r.dataset.postId);
    let currentId = null;
    let currentFetchAbort = null;
    let currentData = null;
    let autosaveTimer = null;
    let rejectCategory = 'other';
    let lastTextBeforeRegen = null;

    const $ = (id) => document.getElementById(id);
    const platformLabels = { facebook: 'Facebook', instagram: 'Instagram', blog: 'Blog' };
    const statusColors = {
        draft: 'bg-slate-100 text-slate-700',
        scheduled: 'bg-blue-100 text-blue-700',
        publishing: 'bg-amber-100 text-amber-700',
        published: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
    };

    // ---------- Toast ----------
    let toastTimer = null;
    function toast(msg, { undo = null, duration = 3500 } = {}) {
        const t = $('toast'), m = $('toast-msg'), u = $('toast-undo');
        m.textContent = msg;
        if (undo) {
            u.classList.remove('hidden');
            u.onclick = () => { undo(); hideToast(); };
        } else {
            u.classList.add('hidden');
            u.onclick = null;
        }
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(hideToast, duration);
    }
    function hideToast() { $('toast').classList.add('hidden'); }

    // ---------- URL sync ----------
    function syncUrl(id) {
        const url = new URL(location.href);
        if (id) url.searchParams.set('post', id); else url.searchParams.delete('post');
        history.replaceState(null, '', url);
    }

    // ---------- Open / close panel ----------
    async function openPost(id, ev) {
        if (ev && ev.target.closest('input,button,a')) return;
        currentId = id;
        syncUrl(id);

        // Highlight row
        document.querySelectorAll('.post-row').forEach(r => r.classList.toggle('bg-blue-50', +r.dataset.postId === id));

        // Show panel on mobile (desktop is always visible)
        $('postPanel').classList.remove('hidden');
        $('panelEmpty').classList.add('hidden');
        $('panelContent').classList.remove('hidden');

        // Instantly clear stale state so we never flash the previous post's image.
        $('panel-image').removeAttribute('src');
        $('panel-image-wrap').classList.add('hidden');
        $('panel-content').value = '';
        $('panel-text-diff').classList.add('hidden');
        $('panel-undo-text').classList.add('hidden');
        lastTextBeforeRegen = null;
        $('panel-saved').textContent = 'Se încarcă...';
        $('panel-error-wrap').classList.add('hidden');
        $('panel-external-wrap').classList.add('hidden');
        $('panel-variants-wrap').classList.add('hidden');
        $('panel-rejections-wrap').classList.add('hidden');
        $('reject-form').classList.add('hidden');
        $('reject-feedback').value = '';
        rejectCategory = 'other';
        document.querySelectorAll('.reject-chip').forEach(b => b.classList.remove('bg-red-600','text-white'));

        // Abort any in-flight fetch from a previous post.
        if (currentFetchAbort) currentFetchAbort.abort();
        currentFetchAbort = new AbortController();
        const reqId = id;

        try {
            const res = await fetch(`/admin/social/post/${id}`, { signal: currentFetchAbort.signal, headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (reqId !== currentId) return; // user moved on, drop stale response
            render(data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            $('panel-saved').textContent = 'Eroare la încărcare';
        }
    }

    function closePanel() {
        currentId = null;
        currentData = null;
        syncUrl(null);
        // On mobile, fully hide the panel; on desktop, show the empty state.
        if (window.innerWidth < 1024) {
            $('postPanel').classList.add('hidden');
        } else {
            $('panelEmpty').classList.remove('hidden');
            $('panelContent').classList.add('hidden');
        }
        document.querySelectorAll('.post-row').forEach(r => r.classList.remove('bg-blue-50'));
    }

    function render(d) {
        currentData = d;

        $('panel-platform').textContent = platformLabels[d.platform] ?? d.platform;
        const st = $('panel-status');
        st.textContent = (d.status || '').toUpperCase();
        st.className = 'px-2 py-0.5 text-xs font-semibold rounded-full ' + (statusColors[d.status] || 'bg-slate-100');
        $('panel-saved').textContent = 'Salvat';

        $('panel-content').value = d.content ?? '';
        $('panel-content').readOnly = !d.is_editable;
        $('panel-scheduled').value = d.scheduled_at ?? '';
        $('panel-scheduled').disabled = !d.is_editable;
        $('panel-post-type').value = d.post_type ?? 'post';
        $('panel-post-type').disabled = !d.is_editable;

        // Image
        const img = $('panel-image'), wrap = $('panel-image-wrap');
        if (d.image_url) {
            img.src = d.image_url + (d.image_url.includes('?') ? '&' : '?') + '_=' + Date.now();
            wrap.classList.remove('hidden');
        } else {
            wrap.classList.add('hidden');
        }
        $('panel-image-loading').classList.add('hidden');

        // Error
        if (d.error_message) {
            $('panel-error-wrap').classList.remove('hidden');
            $('panel-error').textContent = d.error_message;
        }

        // Prompt
        $('panel-image-prompt').textContent = d.image_prompt || '—';

        // External
        if (d.external_url) {
            $('panel-external-wrap').classList.remove('hidden');
            $('panel-external').href = d.external_url;
        }

        // Variants
        const vwrap = $('panel-variants-wrap'), vlist = $('panel-variants');
        if (d.variants && d.variants.length) {
            vwrap.classList.remove('hidden');
            vlist.innerHTML = d.variants.map(v => `
                <button type="button" onclick="useVariant(${v.id})" class="flex items-center gap-1 px-2 py-1 text-[11px] bg-slate-100 hover:bg-slate-200 rounded border border-slate-200" title="${(v.content||'').replace(/"/g,'&quot;').slice(0,80)}">
                    ${v.kind === 'image' ? '🖼️' : '✏️'} ${v.created_at}
                </button>
            `).join('');
        } else {
            vwrap.classList.add('hidden');
        }

        // Rejections
        const rwrap = $('panel-rejections-wrap'), rlist = $('panel-rejections');
        if (d.rejections && d.rejections.length) {
            rwrap.classList.remove('hidden');
            rlist.innerHTML = d.rejections.map(r => `<li>• <b>${r.reason_category ?? 'other'}</b>: ${r.feedback ?? ''} <span class="text-slate-400">(${r.created_at})</span></li>`).join('');
        } else {
            rwrap.classList.add('hidden');
        }

        // Publish button only if editable
        $('btn-publish').disabled = !d.is_editable;
    }

    // ---------- Autosave (content, scheduled_at, post_type) ----------
    function scheduleAutosave() {
        if (!currentData?.is_editable) return;
        $('panel-saved').textContent = 'Se salvează...';
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(flushAutosave, 700);
    }

    async function flushAutosave() {
        if (!currentId || !currentData) return;
        clearTimeout(autosaveTimer);
        const payload = {
            content: $('panel-content').value,
            scheduled_at: $('panel-scheduled').value || null,
            post_type: $('panel-post-type').value,
            updated_at: currentData.updated_at,
        };
        try {
            const res = await fetch(`/admin/social/post/${currentId}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (res.status === 409) {
                const data = await res.json();
                $('panel-saved').textContent = 'Conflict — reîncarc';
                toast('Postarea a fost modificată în altă parte, reîncarc.');
                openPost(currentId);
                return;
            }
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            currentData.updated_at = data.updated_at;
            $('panel-saved').textContent = 'Salvat';
        } catch (e) {
            $('panel-saved').textContent = 'Eroare salvare';
        }
    }

    $('panel-content').addEventListener('input', scheduleAutosave);
    $('panel-scheduled').addEventListener('change', scheduleAutosave);
    $('panel-post-type').addEventListener('change', scheduleAutosave);

    // ---------- Actions ----------
    async function regenImage() {
        if (!currentId) return;
        $('btn-regen-img').disabled = true;
        $('panel-image-loading').classList.remove('hidden');
        try {
            const res = await fetch(`/admin/social/post/${currentId}/regenerate-image`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Eroare');
            $('panel-image').src = data.image_url + '?_=' + Date.now();
            $('panel-image-wrap').classList.remove('hidden');
            toast('Imagine nouă');
            openPost(currentId); // reload to fetch fresh variants
        } catch (e) {
            toast(e.message);
        } finally {
            $('btn-regen-img').disabled = false;
            $('panel-image-loading').classList.add('hidden');
        }
    }

    async function regenText() {
        if (!currentId) return;
        $('btn-regen-txt').disabled = true;
        const prev = $('panel-content').value;
        lastTextBeforeRegen = prev;
        $('panel-content').value = 'Se generează text nou...';
        $('panel-content').disabled = true;
        try {
            const res = await fetch(`/admin/social/post/${currentId}/regenerate-text`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Eroare');
            $('panel-content').value = data.content;
            renderTextDiff(prev, data.content);
            $('panel-undo-text').classList.remove('hidden');
            toast('Text nou');
        } catch (e) {
            $('panel-content').value = prev;
            toast(e.message);
        } finally {
            $('btn-regen-txt').disabled = false;
            $('panel-content').disabled = false;
        }
    }

    // Word-level diff: highlights words in NEW that didn't appear in OLD.
    // Purely client-side, no dependencies. Good enough for short social posts.
    function renderTextDiff(oldText, newText) {
        const oldWords = new Set((oldText || '').toLowerCase().split(/\s+/).filter(Boolean));
        const escape = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const highlighted = (newText || '').split(/(\s+)/).map(token => {
            if (/^\s+$/.test(token)) return token;
            const key = token.toLowerCase().replace(/[.,!?;:()"]/g, '');
            if (!key) return escape(token);
            return oldWords.has(key)
                ? escape(token)
                : `<mark class="bg-amber-200 rounded px-0.5">${escape(token)}</mark>`;
        }).join('');
        const el = $('panel-text-diff');
        el.innerHTML = '<b class="text-amber-700">Modificări:</b> ' + highlighted;
        el.classList.remove('hidden');
    }

    async function undoTextRegen() {
        if (lastTextBeforeRegen === null || !currentId) return;
        const restored = lastTextBeforeRegen;
        $('panel-content').value = restored;
        $('panel-text-diff').classList.add('hidden');
        $('panel-undo-text').classList.add('hidden');
        lastTextBeforeRegen = null;
        scheduleAutosave();
    }
    window.undoTextRegen = undoTextRegen;

    async function duplicatePost() {
        if (!currentId) return;
        const res = await fetch(`/admin/social/post/${currentId}/duplicate`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!res.ok) { toast('Eroare la duplicare'); return; }
        toast('Duplicat — reîncarc');
        setTimeout(() => location.href = `?post=${data.id}`, 500);
    }

    async function approvePost() {
        if (!currentId) return;
        await flushAutosave();
        const id = currentId;
        const btn = $('btn-approve');
        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = '...';
        try {
            const res = await fetch(`/admin/social/post/${id}/approve`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Eroare');
            toast('Aprobat → ' + (data.scheduled_human || data.scheduled_at));
            // Hide row, advance to next
            const row = document.querySelector(`.post-row[data-post-id="${id}"]`);
            if (row) row.style.display = 'none';
            const idx = postIds.indexOf(id);
            const next = postIds[idx + 1] || postIds[idx - 1];
            if (next) openPost(next); else closePanel();
        } catch (e) {
            toast(e.message);
        } finally {
            btn.disabled = false;
            btn.textContent = oldText;
        }
    }
    window.approvePost = approvePost;

    async function publishNow() {
        if (!currentId) return;
        await flushAutosave();
        $('btn-publish').disabled = true;
        try {
            const res = await fetch(`/admin/social/post/${currentId}/publish`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('Eroare publicare');
            toast('Publicat → în coadă');
            // Advance to next post for fast queue clearing
            setTimeout(() => navPost(1), 500);
        } catch (e) {
            toast(e.message);
            $('btn-publish').disabled = false;
        }
    }

    async function deletePost() {
        if (!currentId) return;
        const id = currentId;
        try {
            const res = await fetch(`/admin/social/post/${id}`, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (!res.ok && !res.redirected) throw new Error('Eroare');
            const row = document.querySelector(`.post-row[data-post-id="${id}"]`);
            if (row) row.style.display = 'none';
            const idx = postIds.indexOf(id);
            const next = postIds[idx + 1] || postIds[idx - 1];
            closePanel();
            if (next) openPost(next);
            toast('Șters', {
                undo: async () => {
                    const r = await fetch(`/admin/social/post/${id}/restore`, {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (r.ok) { if (row) row.style.display = ''; toast('Restaurat'); }
                },
                duration: 6000,
            });
        } catch (e) { toast(e.message); }
    }

    function showRejectForm() { $('reject-form').classList.remove('hidden'); }
    function setRejectCategory(cat, btn) {
        rejectCategory = cat;
        document.querySelectorAll('.reject-chip').forEach(b => b.classList.remove('bg-red-600','text-white'));
        btn.classList.add('bg-red-600','text-white');
    }
    async function confirmReject() {
        if (!currentId) return;
        const id = currentId;
        try {
            const res = await fetch(`/admin/social/post/${id}/reject`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ reason_category: rejectCategory, feedback: $('reject-feedback').value }),
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Eroare');
            const row = document.querySelector(`.post-row[data-post-id="${id}"]`);
            if (row) row.style.display = 'none';
            const idx = postIds.indexOf(id);
            const next = postIds[idx + 1] || postIds[idx - 1];
            closePanel();
            if (next) openPost(next);
            toast('Refuzat — Sambla învață din asta', {
                undo: async () => {
                    const r = await fetch(`/admin/social/post/${id}/restore`, {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (r.ok) { if (row) row.style.display = ''; toast('Restaurat'); }
                },
                duration: 6000,
            });
        } catch (e) { toast(e.message); }
    }

    async function useVariant(variantId) {
        if (!currentId) return;
        const res = await fetch(`/admin/social/post/${currentId}/variant/${variantId}/use`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        if (res.ok) { toast('Versiune restaurată'); openPost(currentId); }
    }

    // ---------- Navigation ----------
    function navPost(delta) {
        if (!postIds.length) return;
        const i = currentId ? postIds.indexOf(currentId) : -1;
        const next = postIds[Math.max(0, Math.min(postIds.length - 1, i + delta))];
        if (next && next !== currentId) {
            flushAutosave().finally(() => openPost(next));
        }
    }

    // ---------- Bulk ----------
    function updateBulk() {
        const checks = document.querySelectorAll('.post-check:checked');
        const bar = $('bulkBar');
        if (checks.length) {
            bar.classList.remove('hidden');
            bar.classList.add('flex');
        } else {
            bar.classList.add('hidden');
            bar.classList.remove('flex');
        }
        $('bulkCount').textContent = `${checks.length} selectate`;
    }
    $('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.post-check').forEach(c => c.checked = e.target.checked);
        updateBulk();
    });
    async function bulkAction(action) {
        const ids = Array.from(document.querySelectorAll('.post-check:checked')).map(c => +c.value);
        if (!ids.length) return;
        const payload = { action, ids };
        if (action === 'reschedule') {
            const when = $('bulkRescheduleAt').value;
            if (!when) { toast('Alege data și ora pentru mutare'); return; }
            payload.scheduled_at = when;
        }
        if (action === 'delete' && !confirm(`Ștergi ${ids.length} postări?`)) return;
        const res = await fetch('/admin/social/bulk', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok) {
            toast(`${action}: ${data.count} postări`);
            setTimeout(() => location.reload(), 600);
        } else toast('Eroare bulk');
    }

    // ---------- Keyboard shortcuts ----------
    document.addEventListener('keydown', (e) => {
        // Don't intercept while typing in inputs (except global ones)
        const tag = (e.target.tagName || '').toLowerCase();
        const inField = tag === 'textarea' || tag === 'input' || tag === 'select';

        if (e.key === '?' && !inField) { e.preventDefault(); $('helpOverlay').classList.toggle('hidden'); return; }
        if (e.key === 'Escape') {
            if (!$('helpOverlay').classList.contains('hidden')) { $('helpOverlay').classList.add('hidden'); return; }
            if (inField) { e.target.blur(); return; }
            closePanel(); return;
        }
        if (inField && !(e.metaKey || e.ctrlKey)) return;
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter' && inField) {
            e.preventDefault(); flushAutosave(); return;
        }
        if (e.key === 'j' || e.key === 'ArrowDown') { e.preventDefault(); navPost(1); }
        else if (e.key === 'k' || e.key === 'ArrowUp') { e.preventDefault(); navPost(-1); }
        else if (e.key === 'e' && currentId) { e.preventDefault(); $('panel-content').focus(); }
        else if (e.key === 'a' && currentId) { e.preventDefault(); approvePost(); }
        else if (e.key === 'r' && currentId) { e.preventDefault(); showRejectForm(); }
    });

    // ---------- Swipe on list rows (same semantics as swipe on panel) ----------
    document.querySelectorAll('.post-row').forEach(row => {
        let startX = null, startY = null;
        row.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX; startY = e.touches[0].clientY;
        }, { passive: true });
        row.addEventListener('touchend', (e) => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            const dy = e.changedTouches[0].clientY - startY;
            startX = startY = null;
            if (Math.abs(dx) < 60 || Math.abs(dy) > 50) return;
            const id = +row.dataset.postId;
            if (dx > 60) { openPost(id); setTimeout(() => approvePost(), 250); }
            else if (dx < -60) { openPost(id); setTimeout(() => showRejectForm(), 250); }
        }, { passive: true });
    });

    // ---------- Click delegation (handles both row clicks AND close button) ----------
    document.addEventListener('click', (e) => {
        // Panel close button (works regardless of inline onclick)
        if (e.target.closest('[data-close-panel]')) {
            e.preventDefault();
            closePanel();
            return;
        }
        // Row click → open panel
        const row = e.target.closest('.post-row');
        if (row && !e.target.closest('input,button,a')) {
            const id = +row.dataset.postId;
            if (id) openPost(id, e);
        }
    });

    // ---------- Panel-level swipe gestures (when open: left/right navigate, down closes) ----------
    (function() {
        const panel = $('postPanel');
        let sx = null, sy = null;
        panel.addEventListener('touchstart', (e) => {
            if (e.touches.length !== 1) return;
            sx = e.touches[0].clientX; sy = e.touches[0].clientY;
        }, { passive: true });
        panel.addEventListener('touchend', (e) => {
            if (sx === null) return;
            const dx = e.changedTouches[0].clientX - sx;
            const dy = e.changedTouches[0].clientY - sy;
            sx = sy = null;
            // Don't interpret as swipe if it was a small movement (tap) or vertical scroll
            if (Math.abs(dx) < 60 && Math.abs(dy) < 80) return;
            if (Math.abs(dx) > Math.abs(dy)) {
                // Horizontal swipe
                if (dx > 60 && currentId) { approvePost(); }
                else if (dx < -60 && currentId) { showRejectForm(); }
            } else if (dy > 80) {
                closePanel();
            }
        }, { passive: true });
    })();

    // ---------- Boot ----------
    // Expose the small handful we need from HTML attributes.
    window.openPost = openPost;
    window.closePanel = closePanel;
    window.regenImage = regenImage;
    window.regenText = regenText;
    window.duplicatePost = duplicatePost;
    window.publishNow = publishNow;
    window.deletePost = deletePost;
    window.showRejectForm = showRejectForm;
    window.setRejectCategory = setRejectCategory;
    window.confirmReject = confirmReject;
    window.useVariant = useVariant;
    window.navPost = navPost;
    window.updateBulk = updateBulk;
    window.bulkAction = bulkAction;

    // Deep-link: open post from ?post=
    const initial = new URLSearchParams(location.search).get('post');
    if (initial && postIds.includes(+initial)) {
        openPost(+initial);
    }
})();
</script>
@endpush
@endsection
