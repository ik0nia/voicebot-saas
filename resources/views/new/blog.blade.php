@extends('layouts.new')

@section('title', 'Blog Sambla — ghiduri pentru agenți AI în afaceri românești')
@section('meta_description', 'Articole, ghiduri și studii de caz despre agenți AI conversaționali pentru afaceri din România. Conținut în pregătire.')
@section('canonical', url('/new/blog'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-14 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ blog</div>
        <h1 class="display h-display-xl mb-5">Scriem, <em class="italic accent-text">în curând.</em></h1>
        <p class="text-xl leading-relaxed max-w-2xl mx-auto" style="color: var(--muted);">
            Pregătim o bibliotecă de ghiduri practice despre cum folosești agenți AI pentru afacerea ta — concret, fără jargon.
        </p>
    </div>
</section>

<section class="pb-20">
    <div class="max-w-4xl mx-auto px-6">
        <div class="grid md:grid-cols-3 gap-4">
            @foreach([
                ['Cum pornești primul agent AI în 10 minute', 'Pas cu pas, cu exemple reale.', '🚀'],
                ['Ce documente să încarci pentru răspunsuri mai bune', 'Lista minimă viabilă pe tip de afacere.', '📁'],
                ['Măsurarea ROI-ului unui agent AI', 'Indicatori concreți — dincolo de „conversații".', '📊'],
                ['Anti-halucinație: ghid pentru non-tech', 'Cum să garantezi că agentul nu inventează.', '🛡️'],
                ['Agent AI vs call-center tradițional', 'Cost, calitate, timp de răspuns — comparat.', '⚖️'],
                ['Integrarea cu WooCommerce pe larg', 'Produse, stoc, categorii — în conversație.', '🛒'],
            ] as $post)
                <div class="rounded-2xl p-5 bg-paper border border-line opacity-60">
                    <div class="text-2xl mb-3">{{ $post[2] }}</div>
                    <div class="font-semibold text-sm mb-1 leading-snug">{{ $post[0] }}</div>
                    <p class="text-xs leading-relaxed" style="color: var(--muted);">{{ $post[1] }}</p>
                    <div class="mono text-[10px] uppercase tracking-wider mt-3" style="color: var(--muted);">În pregătire</div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 rounded-3xl p-7 bg-cream border border-line text-center">
            <p class="text-sm mb-3" style="color: var(--muted);">Vrei notificare când publicăm primele articole?</p>
            <a href="{{ route('new.contact') }}" class="btn btn-outline">Lasă-ne e-mail-ul</a>
        </div>
    </div>
</section>

@endsection
