@extends('layouts.admin')

@section('title', 'Style Training')
@section('breadcrumb')
    <a href="{{ route('admin.social.index') }}" class="text-slate-400 hover:text-slate-600">Social Media</a>
    <span class="mx-1.5 text-slate-300">/</span>
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
            <a href="{{ route('admin.social.index') }}" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Style Training</h1>
                <p class="text-sm text-slate-500 mt-1">Adauga exemple pentru a antrena stilul de scriere al AI-ului</p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">In asteptare</h3>
            <p class="text-2xl font-bold text-amber-600 mt-2">{{ $unreviewed->total() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Aprobate</h3>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ $approved }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Respinse</h3>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ $rejected }}</p>
        </div>
    </div>

    {{-- Add new example --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Adauga Exemplu Nou</h2>
        <form method="POST" action="{{ route('admin.social.style.add') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="platform" class="block text-sm font-medium text-slate-700 mb-1">Platforma</label>
                    <select name="platform" id="platform" class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="blog">Blog</option>
                    </select>
                </div>
                <div>
                    <label for="source" class="block text-sm font-medium text-slate-700 mb-1">Sursa (URL optional)</label>
                    <input type="text" name="source" id="source" placeholder="https://..." class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                </div>
            </div>
            <div>
                <label for="content" class="block text-sm font-medium text-slate-700 mb-1">Continut exemplu</label>
                <textarea name="content" id="content" rows="5" required placeholder="Lipeste aici un exemplu de postare in stilul dorit..."
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adauga Exemplu
            </button>
        </form>
    </div>

    {{-- Unreviewed examples --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Exemple in asteptare</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($unreviewed as $example)
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                @php
                                    $pColors = ['facebook' => 'bg-blue-100 text-blue-700', 'instagram' => 'bg-pink-100 text-pink-700', 'blog' => 'bg-slate-100 text-slate-700'];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $pColors[$example->platform] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($example->platform) }}
                                </span>
                                @if($example->example_source)
                                    <a href="{{ $example->example_source }}" target="_blank" class="text-xs text-slate-400 hover:text-slate-600 truncate">{{ $example->example_source }}</a>
                                @endif
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($example->example_content, 300) }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.social.style.review', $example) }}" class="flex flex-col gap-2 shrink-0">
                            @csrf
                            <input type="text" name="notes" placeholder="Note..." class="w-40 rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500">
                            <div class="flex gap-1">
                                <button type="submit" name="approved" value="true"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                                    Aproba
                                </button>
                                <button type="submit" name="approved" value="false"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100">
                                    Respinge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-slate-500 text-sm">Niciun exemplu in asteptare.</div>
            @endforelse
        </div>
        @if($unreviewed->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $unreviewed->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
