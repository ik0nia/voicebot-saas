@extends('layouts.admin')

@section('title', 'Editare Postare')
@section('breadcrumb')
    <a href="{{ route('admin.social.index') }}" class="text-slate-400 hover:text-slate-600">Social Media</a>
    <span class="mx-1.5 text-slate-300">/</span>
    Editare Postare
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Post header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.social.index') }}" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editare Postare</h1>
                <div class="flex items-center gap-2 mt-1">
                    @php
                        $platformLabel = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'blog' => 'Blog'][$post->platform] ?? $post->platform;
                        $statusColors = [
                            'draft' => 'bg-slate-100 text-slate-700',
                            'scheduled' => 'bg-blue-100 text-blue-700',
                            'published' => 'bg-green-100 text-green-700',
                            'failed' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="text-sm text-slate-500">{{ $platformLabel }}</span>
                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$post->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($post->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.social.update', $post) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Content --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <label for="content" class="block text-sm font-semibold text-slate-700 mb-2">Continut</label>
            <textarea name="content" id="content" rows="12"
                      class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                      required>{{ old('content', $post->content) }}</textarea>
            @error('content')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Hashtags --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <label for="hashtags" class="block text-sm font-semibold text-slate-700 mb-2">Hashtag-uri</label>
            <input type="text" name="hashtags" id="hashtags"
                   value="{{ old('hashtags', is_array($post->hashtags) ? implode(', ', $post->hashtags) : $post->hashtags) }}"
                   placeholder="separate prin virgula: #sambla, #chatbot, #ai"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
            <p class="text-xs text-slate-400 mt-1">Separa cu virgula</p>
        </div>

        {{-- Image prompt --}}
        @if($post->image_prompt)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Prompt Imagine (generat de AI)</h3>
                <p class="text-sm text-slate-600 bg-slate-50 rounded-lg p-3">{{ $post->image_prompt }}</p>
            </div>
        @endif

        {{-- Schedule --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <label for="scheduled_at" class="block text-sm font-semibold text-slate-700 mb-2">Programare publicare</label>
            <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                   value="{{ old('scheduled_at', $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                   class="w-full sm:w-auto rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
        </div>

        {{-- Metadata --}}
        @if($post->metadata)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Metadata</h3>
                <div class="text-xs text-slate-500 bg-slate-50 rounded-lg p-3 font-mono">
                    @foreach((array)$post->metadata as $key => $val)
                        <div><span class="text-slate-700 font-medium">{{ $key }}:</span> {{ is_array($val) ? json_encode($val) : $val }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" name="action" value="draft"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Salveaza Draft
            </button>
            <button type="submit" name="action" value="schedule"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Programeaza
            </button>
        </div>
    </form>

    {{-- Publish now (separate form) --}}
    @if($post->status === 'draft' || $post->status === 'scheduled')
        <form method="POST" action="{{ route('admin.social.publish', $post) }}" class="pt-2 border-t border-slate-200">
            @csrf
            <button type="submit" onclick="return confirm('Publici acum?')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Publica Acum
            </button>
        </form>
    @endif
</div>
@endsection
