@extends('layouts.dashboard')

@section('title', 'Baza de cunoștințe — ' . $bot->name)

@section('breadcrumb')
    <a href="/dashboard/boti" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <svg class="w-4 h-4 text-slate-400 mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <a href="/dashboard/boti/{{ $bot->id }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <svg class="w-4 h-4 text-slate-400 mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Baza de cunoștințe</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Baza de cunoștințe</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $bot->name }} &mdash; documente pentru îmbunătățirea răspunsurilor</p>
        </div>
        <button onclick="toggleAddForm()" id="btn-add-doc" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Adaugă document
        </button>
    </div>

    {{-- Add document form (hidden by default) --}}
    <div id="add-form" class="hidden mb-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Document nou</h2>
                <button onclick="toggleAddForm()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-slate-200">
                <button onclick="switchTab('text')" id="tab-text" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-red-800 text-red-800">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Text
                </button>
                <button onclick="switchTab('url')" id="tab-url" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    URL
                </button>
                <button onclick="switchTab('pdf')" id="tab-pdf" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PDF
                </button>
            </div>

            {{-- Text form --}}
            <form id="form-text" action="/dashboard/boti/{{ $bot->id }}/knowledge" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="type" value="text">
                <div>
                    <label for="text-title" class="block text-sm font-medium text-slate-700 mb-1.5">Titlu</label>
                    <input type="text" id="text-title" name="title" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition"
                           placeholder="ex: Informații despre companie">
                </div>
                <div>
                    <label for="text-content" class="block text-sm font-medium text-slate-700 mb-1.5">Conținut</label>
                    <textarea id="text-content" name="content" rows="8" required
                              class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y"
                              placeholder="Introdu textul care va fi folosit ca bază de cunoștințe..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adaugă document
                    </button>
                </div>
            </form>

            {{-- URL form --}}
            <form id="form-url" action="/dashboard/boti/{{ $bot->id }}/knowledge" method="POST" class="p-6 space-y-4 hidden">
                @csrf
                <input type="hidden" name="type" value="url">
                <div>
                    <label for="url-title" class="block text-sm font-medium text-slate-700 mb-1.5">Titlu</label>
                    <input type="text" id="url-title" name="title" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition"
                           placeholder="ex: Pagina de prețuri">
                </div>
                <div>
                    <label for="url-input" class="block text-sm font-medium text-slate-700 mb-1.5">Adresă URL</label>
                    <input type="url" id="url-input" name="url" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition"
                           placeholder="https://exemplu.ro/pagina">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adaugă document
                    </button>
                </div>
            </form>

            {{-- PDF form --}}
            <form id="form-pdf" action="/dashboard/boti/{{ $bot->id }}/knowledge" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 hidden">
                @csrf
                <input type="hidden" name="type" value="pdf">
                <div>
                    <label for="pdf-title" class="block text-sm font-medium text-slate-700 mb-1.5">Titlu</label>
                    <input type="text" id="pdf-title" name="title" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition"
                           placeholder="ex: Manual de utilizare">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Fișier PDF</label>
                    <div id="drop-zone" class="relative border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-red-400 hover:bg-red-50/50 transition-colors cursor-pointer"
                         onclick="document.getElementById('pdf-file').click()">
                        <input type="file" id="pdf-file" name="file" accept=".pdf" required class="hidden" onchange="updateFileName(this)">
                        <svg class="w-10 h-10 text-slate-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p id="drop-text" class="text-sm text-slate-600">
                            <span class="font-semibold text-red-800">Click pentru a alege</span> sau trage fișierul aici
                        </p>
                        <p id="drop-hint" class="text-xs text-slate-400 mt-1">PDF, max. 10 MB</p>
                        <p id="drop-filename" class="text-sm font-medium text-red-800 mt-2 hidden"></p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adaugă document
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Documents list --}}
    @if($documents->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Baza de cunoștințe este goală</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">Adaugă documente pentru a îmbunătăți răspunsurile botului. Poți adăuga text, URL-uri sau fișiere PDF.</p>
            <button onclick="toggleAddForm()" class="mt-6 inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Adaugă primul document
            </button>
        </div>
    @else
        <div class="space-y-3">
            @foreach($documents as $doc)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center gap-4 min-w-0">
                            {{-- Type icon --}}
                            <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center
                                @if($doc->type === 'pdf') bg-red-50 text-red-600
                                @elseif($doc->type === 'url') bg-red-50 text-red-800
                                @else bg-red-50 text-red-800 @endif">
                                @if($doc->type === 'pdf')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                @elseif($doc->type === 'url')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @endif
                            </div>

                            {{-- Title + meta --}}
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 truncate">{{ $doc->title }}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    {{-- Type badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($doc->type === 'pdf') bg-red-100 text-red-700
                                        @elseif($doc->type === 'url') bg-red-100 text-red-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ strtoupper($doc->type) }}
                                    </span>

                                    {{-- Status badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($doc->status === 'ready') bg-green-100 text-green-700
                                        @elseif($doc->status === 'processing') bg-red-100 text-red-800
                                        @elseif($doc->status === 'pending') bg-yellow-100 text-yellow-700
                                        @else bg-red-100 text-red-700 @endif">
                                        @if($doc->status === 'ready') Gata
                                        @elseif($doc->status === 'processing') Se procesează
                                        @elseif($doc->status === 'pending') În așteptare
                                        @else Eșuat @endif
                                    </span>

                                    {{-- Chunks count --}}
                                    <span class="text-xs text-slate-400">{{ $doc->chunks_count }} {{ $doc->chunks_count == 1 ? 'fragment' : 'fragmente' }}</span>

                                    {{-- Date --}}
                                    <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($doc->created_at)->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Delete button --}}
                        <form action="/dashboard/boti/{{ $bot->id }}/knowledge/{{ urlencode($doc->title) }}" method="POST" onsubmit="return confirm('Sigur dorești să ștergi acest document și toate fragmentele asociate?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="shrink-0 p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Șterge documentul">
                                <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleAddForm() {
        var form = document.getElementById('add-form');
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function switchTab(tab) {
        // Hide all forms
        document.getElementById('form-text').classList.add('hidden');
        document.getElementById('form-url').classList.add('hidden');
        document.getElementById('form-pdf').classList.add('hidden');

        // Deactivate all tabs
        var tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(function(t) {
            t.classList.remove('border-red-800', 'text-red-800');
            t.classList.add('border-transparent', 'text-slate-500');
        });

        // Show selected form and activate tab
        document.getElementById('form-' + tab).classList.remove('hidden');
        var activeTab = document.getElementById('tab-' + tab);
        activeTab.classList.remove('border-transparent', 'text-slate-500');
        activeTab.classList.add('border-red-800', 'text-red-800');
    }

    function updateFileName(input) {
        var filenameEl = document.getElementById('drop-filename');
        var textEl = document.getElementById('drop-text');
        var hintEl = document.getElementById('drop-hint');
        if (input.files && input.files[0]) {
            filenameEl.textContent = input.files[0].name;
            filenameEl.classList.remove('hidden');
            textEl.classList.add('hidden');
            hintEl.classList.add('hidden');
        } else {
            filenameEl.classList.add('hidden');
            textEl.classList.remove('hidden');
            hintEl.classList.remove('hidden');
        }
    }

    // Drag and drop support
    (function() {
        var dropZone = document.getElementById('drop-zone');
        if (!dropZone) return;

        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('border-red-400', 'bg-red-50');
            });
        });

        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-red-400', 'bg-red-50');
            });
        });

        dropZone.addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                var fileInput = document.getElementById('pdf-file');
                fileInput.files = files;
                updateFileName(fileInput);
            }
        });
    })();
</script>
@endpush
