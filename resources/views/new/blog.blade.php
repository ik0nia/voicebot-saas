@extends('layouts.new')

@section('title', 'Blog Sambla — ghiduri pentru agenți AI în afaceri românești')
@section('meta_description', 'Articole și ghiduri pentru afaceri care vor să folosească agenți AI practic, nu la modă. Încă nu avem — te anunțăm când primul apare.')
@section('canonical', url('/new/blog'))

@section('content')

<section class="hero-glow">
    <div class="max-w-3xl mx-auto px-6 pt-20 pb-16 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ blog</div>
        <h1 class="display h-display-xl mb-6">Încă nu avem. <em class="italic accent-text">Dar o să avem.</em></h1>
        <p class="text-lg leading-relaxed mb-10" style="color: var(--muted);">
            Preferăm să nu umplem blogul cu articole subțiri generate cu AI doar pentru SEO. Când o să avem ceva concret de spus — despre cum folosești agenți AI în afacerea ta, din experiența clienților noștri — îl punem aici.
        </p>

        <div class="rounded-3xl p-8 bg-paper border border-line max-w-xl mx-auto">
            <div class="mono text-[11px] uppercase tracking-wider mb-3" style="color: var(--muted);">◇ între timp</div>
            <p class="text-base leading-relaxed mb-5">
                Dacă vrei un caz concret pentru industria ta, scrie-ne direct. Răspundem în aceeași zi lucrătoare, cu exemple reale.
            </p>
            <a href="{{ route('new.contact') }}" class="btn btn-primary">
                Scrie-ne o întrebare
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</section>

@endsection
