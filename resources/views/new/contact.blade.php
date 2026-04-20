@extends('layouts.new')

@section('title', 'Contact Sambla — vorbește cu noi despre agenți AI')
@section('meta_description', 'Scrie-ne despre afacerea ta și ce ai vrea să automatizezi. Răspundem în ziua lucrătoare. Pentru urgențe, sună direct.')
@section('canonical', url('/new/contact'))

@section('content')

<section class="hero-glow">
    <div class="max-w-4xl mx-auto px-6 pt-16 pb-10 text-center">
        <div class="chip chip-soft mb-6 mono text-[11px] uppercase tracking-wider inline-flex">◇ contact</div>
        <h1 class="display h-display-xl mb-5">Hai să vorbim <em class="italic accent-text">pe bune</em>.</h1>
        <p class="text-xl" style="color: var(--muted);">Îți răspundem în ziua lucrătoare. Pentru urgențe, sună direct.</p>
    </div>
</section>

<section class="pb-20">
    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-12 gap-10">
        {{-- Form --}}
        <div class="md:col-span-7 fade-up">
            <form method="POST" action="{{ url('/contact') }}" class="rounded-3xl p-7 md:p-8 bg-paper border border-line space-y-5">
                @csrf
                @if(session('success'))
                    <div class="rounded-xl px-4 py-3 text-sm" style="background:#D1FAE5; color:#047857;">
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="rounded-xl px-4 py-3 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                {{-- Honeypot for bots — real users leave it empty --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                <input type="hidden" name="source" value="contact_form">

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Nume</span>
                        <input required name="name" type="text" value="{{ old('name') }}" placeholder="Numele tău"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">E-mail</span>
                        <input required name="email" type="email" value="{{ old('email') }}" placeholder="nume@afacerea-ta.ro"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Companie <span style="color: var(--muted);" class="font-normal">(opțional)</span></span>
                        <input name="company" type="text" value="{{ old('company') }}" placeholder="Numele afacerii tale"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium mb-1 block">Telefon <span style="color: var(--muted);" class="font-normal">(opțional)</span></span>
                        <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="+40 7xx xxx xxx"
                               class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color: var(--accent);">
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-medium mb-1 block">Despre ce vrei să vorbim?</span>
                    <select name="subject"
                            class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2 appearance-none"
                            style="--tw-ring-color: var(--accent); background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23A8A29E%22><path stroke-linecap=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1rem;">
                        <option value="">Alege un subiect…</option>
                        <option value="demo"          {{ old('subject') === 'demo' ? 'selected' : '' }}>Vreau să văd un demo live</option>
                        <option value="pricing"       {{ old('subject') === 'pricing' ? 'selected' : '' }}>Întrebări despre prețuri / plan</option>
                        <option value="integration"   {{ old('subject') === 'integration' ? 'selected' : '' }}>Integrare cu magazinul / CRM-ul meu</option>
                        <option value="voice"         {{ old('subject') === 'voice' ? 'selected' : '' }}>Agent vocal pe telefon</option>
                        <option value="ong"           {{ old('subject') === 'ong' ? 'selected' : '' }}>Reducere ONG / școală</option>
                        <option value="partnership"   {{ old('subject') === 'partnership' ? 'selected' : '' }}>Parteneriat / revânzător</option>
                        <option value="support"       {{ old('subject') === 'support' ? 'selected' : '' }}>Suport tehnic (sunt client)</option>
                        <option value="other"         {{ old('subject') === 'other' ? 'selected' : '' }}>Altceva</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium mb-1 block">Cum te putem ajuta?</span>
                    <textarea required name="message" rows="5"
                              placeholder="Povestește-ne scurt despre afacere și ce ai vrea să facă agentul AI…"
                              class="w-full rounded-xl bg-white border border-line px-4 py-3 text-sm focus:outline-none focus:ring-2"
                              style="--tw-ring-color: var(--accent);">{{ old('message') }}</textarea>
                </label>

                <label class="flex items-start gap-2 text-xs" style="color: var(--muted);">
                    <input required type="checkbox" name="gdpr_consent" value="1" class="mt-0.5" {{ old('gdpr_consent') ? 'checked' : '' }}>
                    <span>Sunt de acord ca datele mele să fie procesate conform <a href="{{ route('new.legal.confidentialitate') }}" class="underline hover:text-ink">politicii de confidențialitate</a>. Nu le partajăm și nu trimitem newsletter fără acordul tău explicit.</span>
                </label>

                <button type="submit" class="btn btn-primary">
                    Trimite mesajul
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Channels --}}
        <div class="md:col-span-5 space-y-4 fade-up" style="transition-delay:.1s;">
            <div class="rounded-3xl p-6 bg-cream border border-line">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--muted);">◇ e-mail</div>
                <a href="mailto:servus@sambla.ro" class="display text-2xl font-semibold hover:text-ink accent-text">servus@sambla.ro</a>
                <p class="text-sm mt-2" style="color: var(--muted);">Răspuns tipic: sub 4 ore în zilele lucrătoare.</p>
            </div>
            <div class="rounded-3xl p-6 bg-cream border border-line">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--muted);">◇ telefon</div>
                <a href="tel:+40775222333" class="display text-2xl font-semibold hover:text-ink accent-text">+40 775 222 333</a>
                <p class="text-sm mt-2" style="color: var(--muted);">Luni – Vineri, 09:00 – 18:00 (ora României)</p>
            </div>
            <div class="rounded-3xl p-6 bg-ink">
                <div class="mono text-[11px] uppercase tracking-wider mb-2" style="color: var(--sun);">◇ demo live</div>
                <p class="text-sm leading-relaxed mb-3" style="color:#D7D3CA;">Vrei să vezi agentul în acțiune, cu datele afacerii tale? Scrie-ne — îl configurăm împreună într-un apel de 20 min.</p>
                <a href="mailto:servus@sambla.ro?subject=Demo+Sambla" class="btn btn-primary" style="background: var(--sun); color: var(--ink);">Programează demo</a>
            </div>
        </div>
    </div>
</section>

@endsection
