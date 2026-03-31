@props([
    'title' => 'Transformă fiecare conversație într-o oportunitate',
    'subtitle' => 'Configurezi în 10 minute. Primele rezultate din prima zi.',
    'primaryText' => 'Începe gratuit acum',
    'secondaryText' => 'Programează un demo',
    'primaryHref' => '/register',
    'secondaryHref' => '/contact',
])

<section class="relative overflow-hidden" style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 25%, #dc2626 50%, #b91c1c 75%, #7f1d1d 100%);">
    {{-- Motif tradițional --}}
    @php $ctaId = 'cta-motif-' . Str::random(4); @endphp
    <div class="absolute inset-0 opacity-[0.1]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="{{ $ctaId }}" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M30 10 L40 20 L30 30 L20 20 Z" fill="white"/><rect x="28" y="2" width="4" height="6" fill="white"/><rect x="28" y="32" width="4" height="6" fill="white"/><rect x="10" y="18" width="6" height="4" fill="white"/><rect x="44" y="18" width="6" height="4" fill="white"/></pattern></defs><rect width="100%" height="100%" fill="url(#{{ $ctaId }})"/></svg>
    </div>
    <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-[300px] h-[300px] bg-white/[0.05] rounded-full blur-[80px]"></div>
    <div class="absolute top-1/2 right-1/4 -translate-y-1/2 w-[250px] h-[250px] bg-red-400/10 rounded-full blur-[80px]"></div>

    <div class="container-custom py-24 lg:py-32 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm mb-8 ring-1 ring-white/20">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
            </div>

            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-5 tracking-tight leading-tight">{!! $title !!}</h2>
            <p class="text-lg text-white/70 mb-12 max-w-lg mx-auto leading-relaxed">{{ $subtitle }}</p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
                <a href="{{ $primaryHref }}" class="group inline-flex items-center gap-2.5 px-10 py-4 rounded-full bg-white text-red-700 font-bold hover:bg-red-50 transition-all duration-300 shadow-xl shadow-black/10 text-base hover:scale-105">
                    {{ $primaryText }}
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="{{ $secondaryHref }}" class="inline-flex items-center gap-2 px-10 py-4 rounded-full border-2 border-white/25 text-white font-semibold hover:bg-white/10 transition-all duration-300 text-base">
                    <svg class="w-4 h-4 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    {{ $secondaryText }}
                </a>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-white/50">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Setup în 10 minute
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    GDPR compliant
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    Folosit în 8+ industrii
                </span>
            </div>
        </div>
    </div>
</section>
