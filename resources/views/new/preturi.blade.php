@extends('layouts.new')

@section('title', 'Prețuri Sambla — planuri pentru agenți AI, în lei')
@section('meta_description', 'Planuri lunare și anuale pentru agenți AI — chat pe site, telefon, multi-canal. Prețuri transparente în lei românești, fără surprize. 7 zile gratuit.')
@section('canonical', url('/new/preturi'))

@section('content')

<section class="hero-glow">
    <div class="max-w-5xl mx-auto px-6 pt-16 pb-10 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ prețuri</div>
        <h1 class="display h-display-xl mb-5">Prețuri <em class="italic accent-text">clare.</em> În lei. Fără surprize.</h1>
        <p class="text-xl max-w-2xl mx-auto" style="color: var(--muted);">7 zile gratuit, fără card. Anulezi oricând. 30% reducere pentru ONG-uri și școli.</p>
    </div>
</section>

<section class="py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="mb-12">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3 text-center" style="color: var(--muted);">— Agent AI pe site & multi-canal —</div>
            <h2 class="display h-display-m text-center mb-8">Planuri pentru chat, mesagerie & CRM</h2>
            <div class="grid md:grid-cols-3 gap-4 max-w-6xl mx-auto">
                @forelse($webchatPlans as $plan)
                    @php
                        $isHighlighted = strtolower($plan->name ?? '') === 'professional' || $plan->is_highlighted ?? false;
                    @endphp
                    <div class="fade-up rounded-3xl p-7 {{ $isHighlighted ? 'bg-ink' : 'bg-paper border border-line' }} relative">
                        @if($isHighlighted)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold" style="color:#fff;">Recomandat</div>
                        @endif
                        <div class="mono text-xs uppercase tracking-wider mb-3" style="color: {{ $isHighlighted ? 'var(--sun)' : 'var(--muted)' }};">{{ $plan->name }}</div>
                        <div class="flex items-baseline gap-1 mb-3">
                            <span class="display text-5xl font-medium" style="color: {{ $isHighlighted ? 'var(--cream)' : 'var(--ink)' }};">{{ (int)($plan->price ?? 0) }}</span>
                            <span style="color: {{ $isHighlighted ? '#A8A29E' : 'var(--muted)' }};">lei / lună</span>
                        </div>
                        @if(!empty($plan->description))
                            <p class="text-sm mb-5 pb-5 border-b" style="color: {{ $isHighlighted ? '#D7D3CA' : 'var(--muted)' }}; border-color: {{ $isHighlighted ? 'rgba(255,255,255,.1)' : 'var(--line)' }};">{{ $plan->description }}</p>
                        @endif
                        @if(!empty($plan->features) && is_array($plan->features))
                            <ul class="space-y-2.5 text-sm mb-8">
                                @foreach($plan->features as $feat)
                                    <li class="flex gap-2 items-start">
                                        <span style="color: {{ $isHighlighted ? 'var(--sun)' : 'var(--accent)' }};">✓</span>
                                        <span style="color: {{ $isHighlighted ? '#D7D3CA' : 'inherit' }};">{{ is_array($feat) ? ($feat['text'] ?? '') : $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ url('/register') }}" class="btn {{ $isHighlighted ? 'btn-primary' : 'btn-outline' }} w-full justify-center" @if($isHighlighted) style="background: var(--sun); color: var(--ink); width:100%;" @else style="width:100%;" @endif>
                            Alege {{ $plan->name }}
                        </a>
                    </div>
                @empty
                    @include('new.partials.default-pricing')
                @endforelse
            </div>
        </div>

        @if($voicePlans->count() > 0)
            <div class="mt-16">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3 text-center" style="color: var(--muted);">— Agent AI vocal —</div>
                <h2 class="display h-display-m text-center mb-8">Planuri pentru telefon</h2>
                <div class="grid md:grid-cols-3 gap-4 max-w-6xl mx-auto">
                    @foreach($voicePlans as $plan)
                        <div class="fade-up rounded-3xl p-7 bg-cream border border-line">
                            <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">{{ $plan->name }}</div>
                            <div class="flex items-baseline gap-1 mb-3">
                                <span class="display text-5xl font-medium">{{ (int)($plan->price ?? 0) }}</span>
                                <span style="color: var(--muted);">lei / lună</span>
                            </div>
                            @if(!empty($plan->description))
                                <p class="text-sm mb-5" style="color: var(--muted);">{{ $plan->description }}</p>
                            @endif
                            <a href="{{ url('/register') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Alege {{ $plan->name }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

<section class="py-16 bg-paper">
    <div class="max-w-3xl mx-auto px-6">
        <h2 class="display h-display-m text-center mb-10">Întrebări despre prețuri</h2>
        <div class="space-y-3">
            @foreach([
                ['Plătesc ceva în primele 7 zile?', 'Nu. 7 zile complete de testare fără card. La final decizi dacă vrei să continui.'],
                ['Ce se întâmplă dacă depășesc limita de conversații?', 'Te anunțăm înainte să o atingi. Fie cumperi un top-up la unitate, fie treci pe un plan superior. Serviciul nu se oprește brusc.'],
                ['Pot schimba planul oricând?', 'Da. Upgrade e instant, downgrade la finalul perioadei curente. Fără taxe de schimbare.'],
                ['Emiteti factură fiscală cu TVA?', 'Da, pentru firme românești — facturi automate prin Stripe, cu TVA 19%. Facturile ajung în inbox-ul tău imediat după plată.'],
                ['Aveți planuri custom pentru volum mare?', 'Da. Dacă ai nevoie de mai mulți agenți, suport dedicat, SLA sau integrări particulare — scrie-ne și facem un plan pe măsură.'],
            ] as $f)
                <details class="rounded-2xl bg-cream border border-line px-5 py-4">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-semibold pr-6">{{ $f[0] }}</span>
                        <svg class="chev w-4 h-4 shrink-0 transition" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 leading-relaxed text-sm" style="color: var(--muted);">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="py-20 text-center">
    <div class="max-w-3xl mx-auto px-6">
        <h2 class="display h-display-l mb-5">Gata să încerci?</h2>
        <a href="{{ url('/register') }}" class="btn btn-primary">Începe gratuit 7 zile</a>
    </div>
</section>

@endsection
