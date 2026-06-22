@extends('layouts.dashboard')

@section('title', 'Baza de cunoștințe — ' . $bot->name)

@section('breadcrumb')
    <a href="/dashboard/boti" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <svg class="w-4 h-4 text-muted mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <a href="/dashboard/boti/{{ $bot->id }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
    <svg class="w-4 h-4 text-muted mx-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span class="text-inkSoft font-medium">Baza de cunoștințe</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Embedding stats widget — vizualizare rapidă stare RAG --}}
    <div class="mb-6 bg-white rounded-xl border border-line p-5"
         x-data="kbStats({{ $bot->id }})" x-init="load()">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-ink">📈 Stare embedding</h3>
            <button @click="load()" class="text-xs text-muted hover:text-ink">⟳</button>
        </div>
        <div x-show="loading" class="text-xs text-muted">Se încarcă…</div>
        <template x-if="!loading && stats">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
                <div><div class="text-xs text-muted">Total</div><div class="font-semibold text-ink" x-text="stats.total"></div></div>
                <div><div class="text-xs text-muted">Ready</div><div class="font-semibold text-emerald-700" x-text="stats.ready"></div></div>
                <div><div class="text-xs text-muted">Pending</div><div class="font-semibold text-amber-700" x-text="stats.pending"></div></div>
                <div><div class="text-xs text-muted">Failed</div><div class="font-semibold text-coralh" x-text="stats.failed"></div></div>
                <div><div class="text-xs text-muted">Fără embedding</div><div class="font-semibold text-inkSoft" x-text="stats.no_embedding"></div></div>
            </div>
        </template>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
            <a href="{{ route('dashboard.bots.knowledge.exportJson', $bot) }}"
               class="rounded-full border border-line px-3 py-1 hover:bg-cream">📤 Export JSON</a>
            <a href="{{ route('dashboard.bots.knowledge.topChunks', $bot) }}" target="_blank"
               class="rounded-full border border-line px-3 py-1 hover:bg-cream">📊 Top chunks</a>
            <button type="button" @click="$dispatch('open-rag-inspector')"
                    class="rounded-full border border-ink bg-ink text-cream px-3 py-1 hover:bg-inkSoft transition">🔬 Testează RAG</button>
            <form method="POST" action="{{ route('dashboard.bots.knowledge.reembedAll', $bot) }}"
                  onsubmit="return confirm('Marchezi toate chunks ca pending pentru re-embedding?');">
                @csrf
                <button type="submit" class="rounded-full border border-coral/30 bg-coralsoft text-coralh px-3 py-1 hover:bg-coral hover:text-white transition">🔄 Re-embed all</button>
            </form>
        </div>
    </div>

    {{-- RAG inspector modal: dă un query, vezi ce chunks ar returna RAG-ul
         ACUM cu score-urile lor (similarity + fts + final RRF). Esențial
         pentru debugging „de ce botul răspunde X în loc de Y". --}}
    <div x-data="ragInspector({{ $bot->id }})"
         @open-rag-inspector.window="open()"
         x-show="visible" x-cloak
         @click.self="visible = false"
         class="fixed inset-0 z-50 bg-ink/50 flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full my-8" @click.stop>
            <div class="px-5 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-ink">🔬 Testează RAG live</h3>
                    <p class="text-xs text-muted mt-0.5">Vezi ce chunks ar returna căutarea pentru o întrebare anume.</p>
                </div>
                <button @click="visible = false" class="text-muted hover:text-ink text-2xl leading-none">×</button>
            </div>
            <div class="p-5 space-y-4">
                <form @submit.prevent="runQuery()">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-inkSoft mb-1">Întrebare client</label>
                            <input type="text" x-model="query" required minlength="2" maxlength="500"
                                   placeholder="ex: cât costă o operație de carii?"
                                   class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-inkSoft mb-1">Limit</label>
                            <select x-model.number="limit"
                                    class="w-20 rounded-lg border border-line bg-white px-2 py-2.5 text-sm">
                                <option :value="3">3</option>
                                <option :value="6">6</option>
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                            </select>
                        </div>
                        <button type="submit" :disabled="loading || !query.trim()"
                                class="rounded-lg bg-coral text-white px-4 py-2.5 text-sm font-semibold disabled:opacity-50">
                            <span x-show="!loading">Caută</span>
                            <span x-show="loading">…</span>
                        </button>
                    </div>
                </form>

                <template x-if="error">
                    <div class="rounded-lg border border-coral/30 bg-coralsoft p-3 text-xs text-coralh" x-text="error"></div>
                </template>

                <template x-if="results !== null && !loading">
                    <div>
                        <div class="text-xs text-muted mb-2">
                            <span x-text="results.count"></span> chunks returnate · sortate descrescător după scor final
                        </div>
                        <template x-if="results.count === 0">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                Niciun chunk găsit. Verifică dacă ai documente/site indexate sau scade threshold-ul de similarity din Tab Avansat → Calitate căutare.
                            </div>
                        </template>
                        <div class="space-y-2.5">
                            <template x-for="(c, idx) in results.chunks" :key="c.id">
                                <div class="rounded-lg border border-line bg-white p-3">
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-coralsoft text-coralh text-xs font-bold shrink-0" x-text="idx + 1"></span>
                                            <span class="text-xs font-semibold text-ink truncate" x-text="c.title || ('Chunk #' + c.id)"></span>
                                        </div>
                                        <div class="flex gap-1 shrink-0 text-2xs">
                                            <template x-if="c.similarity !== null">
                                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono" :title="'Cosine similarity (vector match)'">sim <span x-text="c.similarity"></span></span>
                                            </template>
                                            <template x-if="c.fts_score !== null">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-mono" :title="'Full-text search rank'">fts <span x-text="c.fts_score"></span></span>
                                            </template>
                                            <template x-if="c.final_score !== null || c.rrf_score !== null">
                                                <span class="px-1.5 py-0.5 rounded bg-ink text-cream font-mono" :title="'Final score (RRF fusion sau weighted)'">
                                                    <span x-text="c.final_score !== null ? c.final_score : c.rrf_score"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <p class="text-xs text-inkSoft leading-relaxed" x-text="c.snippet"></p>
                                    <div class="mt-1.5 text-2xs text-muted">
                                        <span x-text="c.source_type || 'manual'"></span>
                                        <template x-if="c.chunk_index !== null">
                                            <span> · chunk <span x-text="c.chunk_index"></span></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
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
            <h1 class="text-2xl font-bold text-ink">Baza de cunoștințe</h1>
            <p class="mt-1 text-sm text-muted">{{ $bot->name }} &mdash; documente pentru îmbunătățirea răspunsurilor</p>
        </div>
        <button onclick="toggleAddForm()" id="btn-add-doc" class="inline-flex items-center gap-2 px-4 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Adaugă document
        </button>
    </div>

    {{-- Add document form (hidden by default) --}}
    <div id="add-form" class="hidden mb-8">
        <div class="bg-white rounded-xl border border-line shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h2 class="text-lg font-semibold text-ink">Document nou</h2>
                <button onclick="toggleAddForm()" class="p-1.5 rounded-lg text-muted hover:text-muted hover:bg-cream transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-line">
                <button onclick="switchTab('text')" id="tab-text" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-coral text-coralh">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Text
                </button>
                <button onclick="switchTab('url')" id="tab-url" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-muted hover:text-inkSoft">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    URL
                </button>
                <button onclick="switchTab('pdf')" id="tab-pdf" class="tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-muted hover:text-inkSoft">
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
                    <label for="text-title" class="block text-sm font-medium text-inkSoft mb-1.5">Titlu</label>
                    <input type="text" id="text-title" name="title" required
                           class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm text-ink placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition"
                           placeholder="ex: Informații despre companie">
                </div>
                <div>
                    <label for="text-content" class="block text-sm font-medium text-inkSoft mb-1.5">Conținut</label>
                    <textarea id="text-content" name="content" rows="8" required
                              class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm text-ink placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition resize-y"
                              placeholder="Introdu textul care va fi folosit ca bază de cunoștințe..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
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
                    <label for="url-title" class="block text-sm font-medium text-inkSoft mb-1.5">Titlu</label>
                    <input type="text" id="url-title" name="title" required
                           class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm text-ink placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition"
                           placeholder="ex: Pagina de prețuri">
                </div>
                <div>
                    <label for="url-input" class="block text-sm font-medium text-inkSoft mb-1.5">Adresă URL</label>
                    <input type="url" id="url-input" name="url" required
                           class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm text-ink placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition"
                           placeholder="https://exemplu.ro/pagina">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
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
                    <label for="pdf-title" class="block text-sm font-medium text-inkSoft mb-1.5">Titlu</label>
                    <input type="text" id="pdf-title" name="title" required
                           class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm text-ink placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition"
                           placeholder="ex: Manual de utilizare">
                </div>
                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5">Fișier PDF</label>
                    <div id="drop-zone" class="relative border-2 border-dashed border-line rounded-lg p-8 text-center hover:border-red-400 hover:bg-coralsoft/50 transition-colors cursor-pointer"
                         onclick="document.getElementById('pdf-file').click()">
                        <input type="file" id="pdf-file" name="file" accept=".pdf" required class="hidden" onchange="updateFileName(this)">
                        <svg class="w-10 h-10 text-muted mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p id="drop-text" class="text-sm text-muted">
                            <span class="font-semibold text-coralh">Click pentru a alege</span> sau trage fișierul aici
                        </p>
                        <p id="drop-hint" class="text-xs text-muted mt-1">PDF, max. 10 MB</p>
                        <p id="drop-filename" class="text-sm font-medium text-coralh mt-2 hidden"></p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
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
        <div class="bg-white rounded-xl border border-line shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-line mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="text-lg font-semibold text-ink mb-2">Baza de cunoștințe este goală</h3>
            <p class="text-sm text-muted max-w-md mx-auto">Adaugă documente pentru a îmbunătăți răspunsurile agentului AI. Poți adăuga text, URL-uri sau fișiere PDF.</p>
            <button onclick="toggleAddForm()" class="mt-6 inline-flex items-center gap-2 px-4 py-2.5 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Adaugă primul document
            </button>
        </div>
    @else
        <div class="space-y-3">
            @foreach($documents as $doc)
                <div class="bg-white rounded-xl border border-line shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center gap-4 min-w-0">
                            {{-- Type icon --}}
                            <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center
                                @if($doc->type === 'pdf') bg-coralsoft text-coral
                                @elseif($doc->type === 'url') bg-coralsoft text-coralh
                                @else bg-coralsoft text-coralh @endif">
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
                                <h3 class="text-sm font-semibold text-ink truncate">{{ $doc->title }}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    {{-- Type badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($doc->type === 'pdf') bg-coralsoft text-coralh
                                        @elseif($doc->type === 'url') bg-coralsoft text-coralh
                                        @else bg-coralsoft text-coralh @endif">
                                        {{ strtoupper($doc->type) }}
                                    </span>

                                    {{-- Status badge --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($doc->status === 'ready') bg-green-100 text-green-700
                                        @elseif($doc->status === 'processing') bg-coralsoft text-coralh
                                        @elseif($doc->status === 'pending') bg-yellow-100 text-yellow-700
                                        @else bg-coralsoft text-coralh @endif">
                                        @if($doc->status === 'ready') Gata
                                        @elseif($doc->status === 'processing') Se procesează
                                        @elseif($doc->status === 'pending') În așteptare
                                        @else Eșuat @endif
                                    </span>

                                    {{-- Chunks count --}}
                                    <span class="text-xs text-muted">{{ $doc->chunks_count }} {{ $doc->chunks_count == 1 ? 'fragment' : 'fragmente' }}</span>

                                    {{-- Date --}}
                                    <span class="text-xs text-muted">{{ \Carbon\Carbon::parse($doc->created_at)->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Delete button --}}
                        <form action="/dashboard/boti/{{ $bot->id }}/knowledge/{{ urlencode($doc->title) }}" method="POST" onsubmit="return confirm('Sigur dorești să ștergi acest document și toate fragmentele asociate?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="shrink-0 p-2 rounded-lg text-muted hover:text-coral hover:bg-coralsoft transition-colors" title="Șterge documentul">
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
    function kbStats(botId) {
        return {
            loading: true,
            stats: null,
            async load() {
                this.loading = true;
                try {
                    const r = await fetch(`/dashboard/agenti/${botId}/knowledge/embedding-stats`);
                    if (r.ok) this.stats = await r.json();
                } catch (e) { console.warn('kbStats load failed', e); }
                finally { this.loading = false; }
            },
        };
    }

    // RAG inspector — modal cu query input + chunks returnate live.
    function ragInspector(botId) {
        return {
            visible: false,
            query: '',
            limit: 6,
            loading: false,
            results: null,
            error: '',
            open() {
                this.visible = true;
                this.error = '';
            },
            async runQuery() {
                const q = (this.query || '').trim();
                if (q.length < 2) return;
                this.loading = true;
                this.error = '';
                this.results = null;
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const r = await fetch(`/dashboard/agenti/${botId}/knowledge/inspect-rag`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                        body: JSON.stringify({ query: q, limit: this.limit }),
                    });
                    if (!r.ok) {
                        this.error = 'Eroare HTTP ' + r.status;
                    } else {
                        this.results = await r.json();
                    }
                } catch (e) {
                    this.error = 'Server indisponibil.';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
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
            t.classList.remove('border-coral', 'text-coralh');
            t.classList.add('border-transparent', 'text-muted');
        });

        // Show selected form and activate tab
        document.getElementById('form-' + tab).classList.remove('hidden');
        var activeTab = document.getElementById('tab-' + tab);
        activeTab.classList.remove('border-transparent', 'text-muted');
        activeTab.classList.add('border-coral', 'text-coralh');
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
                dropZone.classList.add('border-red-400', 'bg-coralsoft');
            });
        });

        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-red-400', 'bg-coralsoft');
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
