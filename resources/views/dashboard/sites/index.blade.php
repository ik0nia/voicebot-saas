@extends('layouts.dashboard')

@section('title', 'Site-uri')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Site-uri</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Site-urile tale</h1>
            <p class="mt-1 text-sm text-slate-500">Gestionează site-urile pe care vrei să folosești chatbot-ul.</p>
        </div>
        @if($canAddSite ?? true)
            <a href="{{ route('dashboard.sites.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Adaugă site
            </a>
        @endif
    </div>

    {{-- Sites grid or empty state --}}
    @if($sites->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($sites as $site)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-5">
                        {{-- Top row: domain + status --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('dashboard.sites.show', $site) }}" class="text-lg font-semibold text-slate-900 hover:text-red-800 transition-colors truncate block">
                                    {{ $site->domain }}
                                </a>
                                @if($site->name)
                                    <p class="text-sm text-slate-500 truncate mt-0.5">{{ $site->name }}</p>
                                @endif
                            </div>
                            @php
                                $statusConfig = [
                                    'verified' => ['label' => 'Verificat', 'dot' => 'bg-green-500', 'text' => 'text-green-600'],
                                    'unverified' => ['label' => 'Neverificat', 'dot' => 'bg-amber-500', 'text' => 'text-amber-600'],
                                    'suspended' => ['label' => 'Suspendat', 'dot' => 'bg-red-500', 'text' => 'text-red-600'],
                                ];
                                $sc = $statusConfig[$site->status] ?? $statusConfig['unverified'];
                            @endphp
                            <span class="shrink-0 ml-3 flex items-center gap-1.5 text-xs font-medium {{ $sc['text'] }}">
                                <span class="w-2 h-2 rounded-full {{ $sc['dot'] }}"></span>
                                {{ $sc['label'] }}
                            </span>
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 text-sm text-slate-500 mb-4">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                                </svg>
                                {{ $site->bots_count ?? $site->bots->count() }} {{ ($site->bots_count ?? $site->bots->count()) == 1 ? 'bot' : 'boți' }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $site->created_at->format('d.m.Y') }}
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-4 border-t border-slate-100">
                            <a href="{{ route('dashboard.sites.show', $site) }}" title="Vezi detalii"
                               class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Vezi detalii
                            </a>

                            <div class="flex-1"></div>

                            <form method="POST" action="{{ route('dashboard.sites.destroy', $site) }}" class="shrink-0"
                                  onsubmit="return confirm('Ești sigur că vrei să ștergi site-ul {{ $site->domain }}? Această acțiune este ireversibilă.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Șterge"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-white text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if(method_exists($sites, 'hasPages') && $sites->hasPages())
            <div class="mt-6">
                {{ $sites->withQueryString()->links() }}
            </div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-16 px-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Nu ai niciun site încă</h3>
            <p class="text-sm text-slate-500 mb-6 text-center max-w-sm">Adaugă primul tău site pentru a putea folosi chatbot-ul.</p>
            @if($canAddSite ?? true)
                <a href="{{ route('dashboard.sites.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Adaugă site
                </a>
            @endif
        </div>
    @endif
@endsection
