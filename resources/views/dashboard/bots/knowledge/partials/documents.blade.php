<div class="p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Toate documentele</h3>
            <p class="text-sm text-slate-500">Lista completă a bazei de cunoștințe cu toate sursele.</p>
        </div>
        {{-- Source filter --}}
        <div class="flex flex-wrap items-center gap-2">
            <button onclick="filterDocs('all', this)" class="doc-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-red-800 text-white transition-colors">Toate</button>
            <button onclick="filterDocs('manual', this)" class="doc-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Manual</button>
            <button onclick="filterDocs('agent', this)" class="doc-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Agent AI</button>
            <button onclick="filterDocs('scan', this)" class="doc-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Scanner</button>
            <button onclick="filterDocs('connector', this)" class="doc-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Conector</button>
        </div>
    </div>

    @if($documents->isEmpty())
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Baza de cunoștințe este goală</h3>
            <p class="text-sm text-slate-500 mb-6">Folosește agenții AI, upload sau scannerul pentru a adăuga conținut.</p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <button onclick="switchBuilderTab('agents')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Generează cu AI
                </button>
                <button onclick="switchBuilderTab('upload')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload fișiere
                </button>
                <button onclick="switchBuilderTab('scanner')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-semibold rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Scanează site
                </button>
            </div>
        </div>
    @else
        <div class="space-y-2" id="documents-list">
            @foreach($documents as $doc)
                <div class="doc-item flex items-center justify-between bg-white rounded-lg border border-slate-200 px-5 py-3.5 hover:shadow-sm transition-shadow" data-source="{{ $doc->source_type ?? 'manual' }}">
                    <div class="flex items-center gap-4 min-w-0">
                        {{-- Type icon --}}
                        <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center
                            @if($doc->type === 'pdf') bg-red-50 text-red-600
                            @elseif($doc->type === 'url') bg-blue-50 text-blue-600
                            @elseif($doc->type === 'docx') bg-indigo-50 text-indigo-600
                            @elseif($doc->type === 'csv') bg-green-50 text-green-600
                            @else bg-slate-50 text-slate-600 @endif">
                            @if($doc->type === 'pdf')
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            @elseif($doc->type === 'url')
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <h4 class="text-sm font-semibold text-slate-900 truncate">{{ $doc->title }}</h4>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">{{ strtoupper($doc->type) }}</span>

                                @if($doc->source_type === 'agent')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Agent AI</span>
                                @elseif($doc->source_type === 'scan')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Scanner</span>
                                @elseif($doc->source_type === 'connector')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Conector</span>
                                @elseif($doc->source_type === 'upload')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Upload</span>
                                @endif

                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                    @if($doc->status === 'ready') bg-green-100 text-green-700
                                    @elseif($doc->status === 'processing') bg-yellow-100 text-yellow-700
                                    @elseif($doc->status === 'pending') bg-slate-100 text-slate-600
                                    @else bg-red-100 text-red-700 @endif">
                                    @if($doc->status === 'ready') Gata
                                    @elseif($doc->status === 'processing') Procesare
                                    @elseif($doc->status === 'pending') Așteptare
                                    @else Eșuat @endif
                                </span>

                                <span class="text-xs text-slate-400">{{ $doc->chunks_count }} {{ $doc->chunks_count == 1 ? 'fragment' : 'fragmente' }}</span>
                                <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($doc->created_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="/dashboard/boti/{{ $bot->id }}/knowledge/{{ urlencode($doc->title) }}" method="POST" onsubmit="return confirm('Sigur dorești să ștergi acest document?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="shrink-0 p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    function filterDocs(source, btn) {
        document.querySelectorAll('.doc-filter-btn').forEach(function(b) {
            b.classList.remove('bg-red-800', 'text-white');
            b.classList.add('bg-slate-100', 'text-slate-600');
        });
        if (btn) {
            btn.classList.remove('bg-slate-100', 'text-slate-600');
            btn.classList.add('bg-red-800', 'text-white');
        }

        document.querySelectorAll('.doc-item').forEach(function(item) {
            if (source === 'all') {
                item.classList.remove('hidden');
            } else {
                var s = item.getAttribute('data-source') || 'manual';
                // 'manual' filter matches null/upload/manual source_type
                if (source === 'manual') {
                    item.classList.toggle('hidden', s !== 'manual' && s !== 'upload' && s !== '');
                } else {
                    item.classList.toggle('hidden', s !== source);
                }
            }
        });
    }
</script>
@endpush
