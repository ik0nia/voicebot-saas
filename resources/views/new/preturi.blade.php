@extends('layouts.new')

@section('title', 'Prețuri Sambla — planuri pentru agenți AI, în lei')
@section('meta_description', 'Planuri pentru agenți AI — chat pe site, telefon, multi-canal. Prețuri transparente în lei românești, fără surprize. 7 zile gratuit.')
@section('canonical', url('/preturi'))
@section('og_image', route('new.og', ['title' => 'Prețuri clare. În lei. Fără surprize.', 'sub' => '7 zile gratuit, fără card. Factură cu TVA.', 'eyebrow' => 'sambla.ro · preturi']))

@section('jsonld')
@php
    $allPlans = $webchatPlans->concat($voicePlans)->filter(fn ($p) => (float)($p->price_monthly ?? 0) > 0);
@endphp
@if($allPlans->count())
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Sambla — agenți AI pentru afaceri",
  "description": "Platformă de agenți AI cu planuri lunare în lei — chat, vocal și multi-canal.",
  "brand": { "@id": "https://sambla.ro/#organization" },
  "offers": [
    @foreach($allPlans as $plan)
    @php $priceStr = number_format((float) $plan->price_monthly, 2, '.', ''); @endphp
    {
      "@type": "Offer",
      "name": @json($plan->name),
      "price": @json($priceStr),
      "priceCurrency": "RON",
      "availability": "https://schema.org/InStock",
      "url": "https://sambla.ro/preturi",
      "priceSpecification": {
        "@type": "UnitPriceSpecification",
        "price": @json($priceStr),
        "priceCurrency": "RON",
        "billingIncrement": 1,
        "unitText": "MONTH"
      }
    }{{ !$loop->last ? ',' : '' }}
    @endforeach
  ]
}
</script>
@endif
@endsection

@php
    /* Formatare RON în stil românesc: 1.299,50 lei (nu 1,299.50) */
    $fmtRo = fn ($val, int $dec = 0) => number_format((float) $val, $dec, ',', '.');

    /* Un plan e „Enterprise / Custom" dacă:
       - are price_monthly = 0, SAU
       - slug / name conține „enterprise" sau „custom"
       Evităm afișarea bizară „0 lei/lună" pentru planuri ce trebuie
       discutate individual. */
    $isCustomPlan = function ($plan) {
        $priceM = (float) ($plan->price_monthly ?? 0);
        if ($priceM <= 0) return true;
        $text = strtolower(($plan->slug ?? '') . ' ' . ($plan->name ?? ''));
        return str_contains($text, 'enterprise') || str_contains($text, 'custom');
    };

@endphp

@section('content')

{{-- HERO compact + trust chips interactive --}}
<section class="hero-glow">
    <div class="max-w-5xl mx-auto px-6 pt-16 pb-8 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ prețuri</div>
        <h1 class="display h-display-xl mb-4">Prețuri <em class="italic accent-text">clare.</em><br class="hidden sm:block"> În lei. Fără surprize.</h1>
        <p class="text-lg max-w-xl mx-auto mb-8" style="color: var(--muted);">7 zile gratuit, fără card. Anulezi oricând.</p>

        {{-- 4 trust chips — highlights vizuale între titlu și carduri --}}
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-paper border border-line text-sm font-medium hover:border-[var(--accent)] hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Fără setup fee
            </span>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-paper border border-line text-sm font-medium hover:border-[var(--accent)] hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Fără contract
            </span>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-paper border border-line text-sm font-medium hover:border-[var(--accent)] hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4 accent-text" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Factură cu TVA
            </span>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold text-white" style="background: var(--accent);">
                🎓 −30% ONG &amp; școli
            </span>
        </div>
    </div>
</section>

{{-- WEBCHAT PLANS — direct la carduri, un singur rând de context --}}
<section class="pt-10 pb-16">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mono text-[11px] uppercase tracking-[0.2em]" style="color: var(--muted);">
                <span class="w-8 h-px" style="background: var(--line);"></span>
                <span>Agent AI · chat, mesagerie &amp; CRM</span>
                <span class="w-8 h-px" style="background: var(--line);"></span>
            </div>
        </div>


        {{-- Plans grid --}}
        <div class="grid md:grid-cols-3 gap-5 max-w-6xl mx-auto">
            @forelse($webchatPlans as $plan)
                @php
                    $isHighlighted = $plan->is_popular ?? false;
                    $features = is_array($plan->features) ? $plan->features : [];
                    $overage = is_array($plan->overage) ? $plan->overage : [];
                    $cpm = isset($overage['cost_per_message']) ? (float) $overage['cost_per_message'] : null;
                    $cpb = isset($overage['cost_per_extra_bot']) ? (float) $overage['cost_per_extra_bot'] : null;
                    $priceM = (float) ($plan->price_monthly ?? 0);
                    $isEnterprise = $isCustomPlan($plan);
                @endphp
                <div class="fade-up rounded-3xl p-7 relative bg-paper {{ $isHighlighted ? '' : 'border border-line' }}"
                     @if($isHighlighted)
                        style="border: 2px solid var(--accent); box-shadow: 0 20px 50px -10px color-mix(in srgb, var(--accent) 25%, transparent);"
                     @endif>
                    @if($isHighlighted)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full text-white whitespace-nowrap" style="background: var(--accent); box-shadow: 0 4px 12px color-mix(in srgb, var(--accent) 35%, transparent);">★ Recomandat</div>
                    @endif

                    <div class="mono text-xs uppercase tracking-wider mb-3" style="color: {{ $isHighlighted ? 'var(--accent)' : 'var(--muted)' }};">{{ $plan->name }}</div>

                    @if($plan->description)
                        <p class="text-sm mb-5" style="color: var(--muted);">{{ $plan->description }}</p>
                    @endif

                    <div class="mb-5 pb-5 border-b border-line">
                        @if($isEnterprise)
                            <div class="flex items-baseline">
                                <span class="display text-4xl font-medium text-ink">La cerere</span>
                            </div>
                        @else
                            <div class="flex items-baseline gap-1">
                                <span class="display text-5xl font-medium text-ink">{{ $fmtRo($priceM, fmod($priceM, 1) > 0 ? 2 : 0) }}</span>
                                <span class="text-base font-semibold" style="color: var(--muted);">lei</span>
                                <span class="text-sm" style="color: var(--muted);">/lună +TVA</span>
                            </div>
                        @endif
                    </div>

                    @if(!empty($features) && is_array($features))
                        <ul class="space-y-2.5 text-sm mb-6">
                            @foreach($features as $feat)
                                <li class="flex gap-2 items-start">
                                    <span class="shrink-0 mt-0.5 accent-text">✓</span>
                                    <span>{{ is_array($feat) ? ($feat['text'] ?? '') : $feat }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if(($cpm && $cpm > 0) || ($cpb && $cpb > 0))
                        <div class="text-xs mb-6 pt-4 border-t border-line space-y-1" style="color: var(--muted);">
                            @if($cpm && $cpm > 0)
                                <p>Mesaj suplimentar: <span class="font-semibold text-ink">{{ $fmtRo($cpm, 2) }} lei</span></p>
                            @endif
                            @if($cpb && $cpb > 0)
                                <p>Agent suplimentar: <span class="font-semibold text-ink">{{ $fmtRo($cpb, fmod($cpb, 1) > 0 ? 2 : 0) }} lei/lună</span></p>
                            @endif
                        </div>
                    @endif

                    @if($isEnterprise)
                        <a href="{{ route('new.contact') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Contactează-ne</a>
                    @else
                        <a href="{{ url('/register') }}"
                           data-analytics-plan="{{ $plan->slug ?? strtolower($plan->name) }}"
                           data-analytics-plan-name="{{ $plan->name }}"
                           data-analytics-price="{{ $priceM }}"
                           class="btn w-full justify-center {{ $isHighlighted ? 'btn-primary' : 'btn-outline' }}"
                           style="width:100%;">
                            Alege {{ $plan->name }}
                        </a>
                    @endif
                </div>
            @empty
                @include('new.partials.default-pricing')
            @endforelse
        </div>
    </div>
</section>

{{-- VOICE PLANS --}}
@if($voicePlans->count() > 0)
    <section class="py-16 bg-paper border-y border-line">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-10">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">— Agent AI vocal —</div>
                <h2 class="display h-display-m mb-4">Planuri pentru <em class="italic accent-text">telefon</em></h2>
                <p class="text-lg max-w-xl mx-auto" style="color: var(--muted);">Agent vocal în limba română — răspunde apelurilor, programează, escaladează. Minutele se consumă doar când cineva vorbește.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-5 max-w-6xl mx-auto">
                @foreach($voicePlans as $plan)
                    @php
                        $features = is_array($plan->features) ? $plan->features : [];
                        $overage = is_array($plan->overage) ? $plan->overage : [];
                        $cpm = isset($overage['cost_per_minute']) ? (float) $overage['cost_per_minute'] : null;
                        $limits = is_array($plan->limits) ? $plan->limits : [];
                        $minutes = $limits['minutes'] ?? $limits['voice_minutes'] ?? null;
                        $priceM = (float) ($plan->price_monthly ?? 0);
                        $isEnterprise = $isCustomPlan($plan);
                    @endphp
                    <div class="fade-up rounded-3xl p-7 bg-cream border border-line">
                        <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">{{ $plan->name }}</div>
                        @if($plan->description)
                            <p class="text-sm mb-5" style="color: var(--muted);">{{ $plan->description }}</p>
                        @endif
                        <div class="mb-5 pb-5 border-b border-line">
                            @if($isEnterprise)
                                <div class="display text-4xl font-medium">La cerere</div>
                            @else
                                <div class="flex items-baseline gap-1">
                                    <span class="display text-5xl font-medium">{{ $fmtRo($priceM, fmod($priceM, 1) > 0 ? 2 : 0) }}</span>
                                    <span class="text-base font-semibold" style="color: var(--muted);">lei</span>
                                    <span class="text-sm" style="color: var(--muted);">/lună +TVA</span>
                                </div>
                            @endif
                        </div>
                        @if(is_numeric($minutes))
                            <p class="text-sm font-semibold accent-text mb-4">
                                {{ (int) $minutes === -1 ? 'Minute nelimitate' : $fmtRo($minutes, 0) . ' minute incluse' }}
                            </p>
                        @endif
                        @if(!empty($features) && is_array($features))
                            <ul class="space-y-2.5 text-sm mb-6">
                                @foreach($features as $feat)
                                    <li class="flex gap-2 items-start">
                                        <span class="shrink-0 mt-0.5 accent-text">✓</span>
                                        <span>{{ is_array($feat) ? ($feat['text'] ?? '') : $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($cpm)
                            <p class="text-xs mb-6 pt-4 border-t border-line" style="color: var(--muted);">
                                Minut suplimentar: <span class="font-semibold text-ink">{{ $fmtRo($cpm, 2) }} lei</span>
                            </p>
                        @endif
                        @if($isEnterprise)
                            <a href="{{ route('new.contact') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Contactează-ne</a>
                        @else
                            <a href="{{ url('/register') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Alege {{ $plan->name }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- CREDIT SUPLIMENTAR --}}
<section class="py-16">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ credit suplimentar</div>
            <h2 class="display h-display-m mb-4">Depășești limita? <em class="italic accent-text">Nu se oprește nimic.</em></h2>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--muted);">Mesajele și minutele peste limită se taxează automat, la un cost care scade pe planurile superioare. Te anunțăm înainte să depășești, ca să nu fie surprize.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            {{-- Messages overage --}}
            <div class="fade-up rounded-3xl p-7 bg-paper border border-line">
                <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center mb-4">
                    <span class="text-2xl">💬</span>
                </div>
                <h3 class="display text-xl font-medium mb-1">Mesaje suplimentare</h3>
                <p class="text-sm mb-5" style="color: var(--muted);">Când treci de conversațiile incluse.</p>
                @if($webchatPlans->count())
                    <div class="space-y-2 text-sm">
                        @foreach($webchatPlans as $plan)
                            @php $cpm = is_array($plan->overage ?? null) ? ($plan->overage['cost_per_message'] ?? null) : null; @endphp
                            @if($cpm)
                                <div class="flex items-center justify-between py-2 border-b border-line last:border-0">
                                    <span style="color: var(--muted);">{{ $plan->name }}</span>
                                    <span class="font-semibold text-ink">{{ $fmtRo($cpm, 2) }} lei</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm" style="color: var(--muted);">Detaliile vor fi disponibile în curând.</p>
                @endif
            </div>

            {{-- Extra bots --}}
            <div class="fade-up rounded-3xl p-7 bg-paper border border-line">
                <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center mb-4">
                    <span class="text-2xl">🤖</span>
                </div>
                <h3 class="display text-xl font-medium mb-1">Agenți suplimentari</h3>
                <p class="text-sm mb-5" style="color: var(--muted);">Pentru mai multe brand-uri sau divizii.</p>
                @if($webchatPlans->count())
                    <div class="space-y-2 text-sm">
                        @foreach($webchatPlans as $plan)
                            @php $cpb = is_array($plan->overage ?? null) ? ($plan->overage['cost_per_extra_bot'] ?? null) : null; @endphp
                            @if($cpb)
                                <div class="flex items-center justify-between py-2 border-b border-line last:border-0">
                                    <span style="color: var(--muted);">{{ $plan->name }}</span>
                                    <span class="font-semibold text-ink">{{ $fmtRo($cpb, fmod($cpb, 1) > 0 ? 2 : 0) }} lei/lună</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm" style="color: var(--muted);">Detaliile vor fi disponibile în curând.</p>
                @endif
            </div>

            {{-- Minutes overage --}}
            <div class="fade-up rounded-3xl p-7 bg-paper border border-line">
                <div class="w-12 h-12 rounded-2xl accent-soft-bg flex items-center justify-center mb-4">
                    <span class="text-2xl">📞</span>
                </div>
                <h3 class="display text-xl font-medium mb-1">Minute voce suplimentare</h3>
                <p class="text-sm mb-5" style="color: var(--muted);">Doar când cineva efectiv vorbește.</p>
                @if($voicePlans->count())
                    <div class="space-y-2 text-sm">
                        @foreach($voicePlans as $plan)
                            @php $cpm = is_array($plan->overage ?? null) ? ($plan->overage['cost_per_minute'] ?? null) : null; @endphp
                            @if($cpm)
                                <div class="flex items-center justify-between py-2 border-b border-line last:border-0">
                                    <span style="color: var(--muted);">{{ $plan->name }}</span>
                                    <span class="font-semibold text-ink">{{ $fmtRo($cpm, 2) }} lei/min</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm" style="color: var(--muted);">Planurile vocale se lansează în curând.</p>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- WHAT'S INCLUDED EVERYWHERE --}}
<section class="py-16 bg-cream">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-10">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ incluse pe toate planurile</div>
            <h2 class="display h-display-m mb-4">Tot ce contează, <em class="italic accent-text">fără upgrade.</em></h2>
        </div>
        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            @foreach([
                ['🇷🇴', 'Hosting în România', 'Date stocate pe servere din UE, GDPR nativ.'],
                ['⚡', 'Setup în 10 minute', 'Upload documente, ajustezi tonul, publici widget-ul.'],
                ['🔐', 'Izolare pe tenant', 'Datele tale separate fizic de alte conturi.'],
                ['📊', 'Dashboard complet', 'Conversații, apeluri, lead-uri, conversii — în timp real.'],
                ['🛠️', 'Suport în română', 'E-mail și chat, oameni reali, zile lucrătoare.'],
                ['🔄', 'Actualizări incluse', 'Features noi, fără taxe de upgrade, fără versiuni.'],
            ] as $i)
                <div class="flex items-start gap-3 p-4 rounded-2xl bg-paper border border-line">
                    <span class="text-2xl shrink-0">{{ $i[0] }}</span>
                    <div>
                        <div class="font-semibold mb-0.5">{{ $i[1] }}</div>
                        <div class="text-xs" style="color: var(--muted);">{{ $i[2] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-16 bg-paper border-t border-line">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-10">
            <div class="mono text-[11px] uppercase tracking-[0.2em] mb-3" style="color: var(--muted);">◇ întrebări frecvente</div>
            <h2 class="display h-display-m">Răspunsuri <em class="italic accent-text">sincere.</em></h2>
        </div>
        <div class="space-y-3">
            @foreach([
                ['Plătesc ceva în primele 7 zile?', 'Nu. 7 zile complete de testare fără card. La final decizi dacă vrei să continui — dacă nu, nu se întâmplă nimic, nu te sunăm.'],
                ['Ce se întâmplă dacă depășesc limita de conversații?', 'Te anunțăm înainte să o atingi — prin e-mail și în dashboard. Fie cumperi un top-up la unitate, fie treci pe un plan superior. Serviciul nu se oprește brusc.'],
                ['Pot schimba planul oricând?', 'Da. Upgrade e instant, downgrade la finalul perioadei curente. Fără taxe de schimbare, fără negociere. Diferența de preț se calculează pro-rata.'],
                ['Pot combina chat + voce?', 'Da. Alege un plan chat și adaugă unul de voce separat. Ambele funcționează cu aceeași bază de cunoștințe, același ton, același brand.'],
                ['Emiteți factură fiscală cu TVA?', 'Da, pentru firme românești — facturi automate prin Stripe, cu TVA conform legislației (21% începând cu 2026). Facturile ajung în inbox imediat după plată.'],
                ['Aveți planuri custom pentru volum mare?', 'Da. Dacă ai nevoie de mai mulți agenți, suport dedicat, SLA sau integrări particulare — scrie-ne și facem un plan pe măsură.'],
                ['Oferiți discount pentru ONG-uri sau școli?', 'Da — 30% reducere permanentă pe toate planurile pentru organizații non-profit și instituții de învățământ acreditate. Scrie-ne cu dovada statutului.'],
                ['Ce metode de plată acceptați?', 'Card bancar prin Stripe (Visa / Mastercard / Maestro). Pentru planurile Enterprise și facturi pe firmă, acceptăm și transfer bancar în RON.'],
            ] as $f)
                <details class="rounded-2xl bg-cream border border-line px-5 py-4 group">
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-semibold pr-6">{{ $f[0] }}</span>
                        <svg class="chev w-4 h-4 shrink-0 transition-transform group-open:rotate-180" style="color: var(--muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-3 leading-relaxed text-sm" style="color: var(--muted);">{{ $f[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA FINAL --}}
<section class="py-20">
    <div class="max-w-4xl mx-auto px-6">
        <div class="rounded-[2.5rem] bg-ink p-10 md:p-14 text-center relative overflow-hidden">
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-3xl" style="background: radial-gradient(circle, color-mix(in srgb, var(--accent) 40%, transparent) 0%, transparent 70%);"></div>
            <div class="relative">
                <div class="mono text-[11px] uppercase tracking-[0.2em] mb-5" style="color:#F2E59A;">◇ nu ești sigur?</div>
                <h2 class="display text-4xl md:text-5xl font-medium leading-[1.05] mb-4 text-cream">
                    <em class="italic accent-text">Vorbim</em> întâi.
                </h2>
                <p class="text-lg mb-8" style="color: #D7D3CA;">Ne spui ce faci, te ajutăm să alegi planul care ți se potrivește. Sau pornești direct pe 7 zile gratuit.</p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="{{ url('/register') }}" class="btn btn-primary" style="background:#F2E59A; color:#1C1917;">
                        Începe gratuit 7 zile
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ route('new.contact') }}" class="btn btn-outline" style="border-color: rgba(255,255,255,.4); color: #F5F1E8;">Vorbește cu noi</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.dataLayer = window.dataLayer || [];

    /* GA4 — view_item_list pe pageload cu planurile reale din DB,
       nu hardcoded. Colectăm toți CTA-urile plan și extragem id/name/
       price. Dacă consent nu e dat, dataLayer e coadă, nu no-op. */
    var planEls = document.querySelectorAll('[data-analytics-plan]');
    if (planEls.length) {
        var items = [];
        planEls.forEach(function (el) {
            items.push({
                item_id:       el.getAttribute('data-analytics-plan'),
                item_name:     el.getAttribute('data-analytics-plan-name') || el.getAttribute('data-analytics-plan'),
                price:         parseFloat(el.getAttribute('data-analytics-price') || '0'),
                item_category: 'monthly',
                quantity:      1,
            });
        });
        window.dataLayer.push({ event: 'view_item_list', item_list_name: 'pachete', items: items });
    }

    /* Select_item + begin_checkout la click pe CTA plan */
    planEls.forEach(function (el) {
        el.addEventListener('click', function () {
            var planId   = el.getAttribute('data-analytics-plan');
            var planName = el.getAttribute('data-analytics-plan-name') || planId;
            var price    = parseFloat(el.getAttribute('data-analytics-price') || '0');
            var item = { item_id: planId, item_name: planName, price: price, item_category: 'monthly', quantity: 1 };
            window.dataLayer.push({ event: 'select_item', item_list_name: 'pachete', items: [item] });
            window.dataLayer.push({ event: 'begin_checkout', currency: 'RON', value: price, items: [item] });
        });
    });
});
</script>
@endpush
