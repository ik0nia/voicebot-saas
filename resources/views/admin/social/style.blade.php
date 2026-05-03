@extends('layouts.admin')

@section('title', 'Style Training')
@section('breadcrumb')
    <a href="{{ route('admin.social.index') }}" class="text-muted hover:text-muted">Social Media</a>
    <span class="mx-1.5 text-line">/</span>
    Style Training
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.social.index') }}" class="p-2 rounded-lg text-muted hover:text-inkSoft hover:bg-cream">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-ink">Style Training</h1>
                <p class="text-sm text-muted mt-1">Adauga exemple pentru a antrena stilul de scriere al AI-ului</p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-sm font-semibold text-muted uppercase tracking-wider">In asteptare</h3>
            <p class="text-2xl font-bold text-amber-600 mt-2">{{ $unreviewed->total() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-sm font-semibold text-muted uppercase tracking-wider">Aprobate</h3>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ $approved }}</p>
        </div>
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-sm font-semibold text-muted uppercase tracking-wider">Respinse</h3>
            <p class="text-2xl font-bold text-coral mt-2">{{ $rejected }}</p>
        </div>
    </div>

    {{-- Add new example --}}
    <div class="bg-white rounded-xl border border-line p-6">
        <h2 class="text-lg font-semibold text-ink mb-4">Adauga Exemplu Nou</h2>
        <form method="POST" action="{{ route('admin.social.style.add') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="platform" class="block text-sm font-medium text-inkSoft mb-1">Platforma</label>
                    <select name="platform" id="platform" class="w-full rounded-lg border-line text-sm focus:border-coral focus:ring-coral">
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="blog">Blog</option>
                    </select>
                </div>
                <div>
                    <label for="source" class="block text-sm font-medium text-inkSoft mb-1">Sursa (URL optional)</label>
                    <input type="text" name="source" id="source" placeholder="https://..." class="w-full rounded-lg border-line text-sm focus:border-coral focus:ring-coral">
                </div>
            </div>
            <div>
                <label for="content" class="block text-sm font-medium text-inkSoft mb-1">Continut exemplu</label>
                <textarea name="content" id="content" rows="5" required placeholder="Lipeste aici un exemplu de postare in stilul dorit..."
                          class="w-full rounded-lg border-line text-sm focus:border-coral focus:ring-coral"></textarea>
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-coral rounded-lg hover:bg-coralh">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adauga Exemplu
            </button>
        </form>
    </div>

    {{-- Unreviewed examples --}}
    <div class="bg-white rounded-xl border border-line overflow-hidden">
        <div class="px-6 py-4 border-b border-line">
            <h2 class="text-lg font-semibold text-ink">Exemple in asteptare</h2>
        </div>
        <div class="divide-y divide-line">
            @forelse($unreviewed as $example)
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                @php
                                    $pColors = ['facebook' => 'bg-blue-100 text-blue-700', 'instagram' => 'bg-pink-100 text-pink-700', 'blog' => 'bg-cream text-inkSoft'];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $pColors[$example->platform] ?? 'bg-cream text-inkSoft' }}">
                                    {{ ucfirst($example->platform) }}
                                </span>
                                @if($example->example_source)
                                    <a href="{{ $example->example_source }}" target="_blank" class="text-xs text-muted hover:text-muted truncate">{{ $example->example_source }}</a>
                                @endif
                            </div>
                            <p class="text-sm text-inkSoft whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($example->example_content, 300) }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.social.style.review', $example) }}" class="flex flex-col gap-2 shrink-0">
                            @csrf
                            <input type="text" name="notes" placeholder="Note..." class="w-40 rounded-lg border-line text-xs focus:border-coral focus:ring-coral">
                            <div class="flex gap-1">
                                <button type="submit" name="approved" value="true"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                                    Aproba
                                </button>
                                <button type="submit" name="approved" value="false"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium text-coralh bg-coralsoft border border-coral/30 rounded-lg hover:bg-coralsoft">
                                    Respinge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-muted text-sm">Niciun exemplu in asteptare.</div>
            @endforelse
        </div>
        @if($unreviewed->hasPages())
            <div class="px-6 py-4 border-t border-line">
                {{ $unreviewed->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
