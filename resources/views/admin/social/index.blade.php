@extends('layouts.admin')

@section('title', 'Social Media')
@section('breadcrumb', 'Social Media Management')

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Social Media</h1>
            <p class="text-sm text-slate-500 mt-1">Gestioneaza postari, conturi si programari</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.social.style') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Style Training
            </a>
            <a href="{{ route('admin.social.accounts') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Conturi
            </a>
            <a href="{{ route('admin.social.schedule') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Programare
            </a>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Total Postari</h3>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $stats['total_posts'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Publicate</h3>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ $stats['published'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Programate</h3>
            <p class="text-2xl font-bold text-blue-600 mt-2">{{ $stats['scheduled'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Esuate</h3>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ $stats['failed'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Azi</h3>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $stats['today'] }}</p>
        </div>
    </div>

    {{-- Quick generate form --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Genereaza Postare Noua</h2>
        <form method="POST" action="{{ route('admin.social.generate') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <select name="platform" class="rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 w-full sm:w-40">
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="blog">Blog</option>
            </select>
            <input type="text" name="topic" placeholder="Subiectul postarii..." required
                   class="flex-1 rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
            <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Genereaza
            </button>
        </form>
        @error('topic')
            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Posts table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Postari</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Platforma</th>
                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Continut</th>
                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Programat</th>
                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Publicat</th>
                        <th class="px-6 py-3 text-right font-semibold text-slate-600">Actiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($posts as $post)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                @if($post->platform === 'facebook')
                                    <span class="inline-flex items-center gap-1 text-blue-600 font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        FB
                                    </span>
                                @elseif($post->platform === 'instagram')
                                    <span class="inline-flex items-center gap-1 text-pink-600 font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                        IG
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-medium">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                        Blog
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-slate-700 max-w-xs truncate">{{ \Illuminate\Support\Str::limit($post->content, 50) }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'scheduled' => 'bg-blue-100 text-blue-700',
                                        'published' => 'bg-green-100 text-green-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$post->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($post->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-slate-500">{{ $post->scheduled_at ? $post->scheduled_at->format('d.m.Y H:i') : '-' }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $post->published_at ? $post->published_at->format('d.m.Y H:i') : '-' }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.social.edit', $post) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100" title="Editare">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if($post->status === 'draft')
                                        <form method="POST" action="{{ route('admin.social.publish', $post) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-green-400 hover:text-green-700 hover:bg-green-50" title="Publica acum">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.social.destroy', $post) }}" class="inline" onsubmit="return confirm('Stergi postarea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:text-red-700 hover:bg-red-50" title="Sterge">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">Nicio postare inca. Genereaza prima postare!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
