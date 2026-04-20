{{-- Fallback pricing cards when the Plans table is empty (fresh DB) or
     unreachable during a deploy. Static values mirror the live site's
     marketing copy so no /new visitor sees an empty price grid. --}}
<div class="fade-up rounded-3xl p-7 bg-paper border border-line">
    <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">Starter</div>
    <div class="flex items-baseline gap-1 mb-3"><span class="display text-5xl font-medium">29</span><span style="color: var(--muted);">lei / lună</span></div>
    <p class="text-sm mb-5 pb-5 border-b border-line" style="color: var(--muted);">Un agent AI pe un singur site.</p>
    <ul class="space-y-2.5 text-sm mb-8">
        <li class="flex gap-2"><span class="accent-text">✓</span>1 agent · 500 conversații/lună</li>
        <li class="flex gap-2"><span class="accent-text">✓</span>Widget chat + 1 site</li>
        <li class="flex gap-2"><span class="accent-text">✓</span>Bază de cunoștințe nelimitată</li>
    </ul>
    <a href="{{ url('/register') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Începe gratuit</a>
</div>
<div class="fade-up rounded-3xl p-7 bg-ink relative">
    <div class="absolute -top-3 left-1/2 -translate-x-1/2 chip accent-bg text-[10px] font-semibold" style="color:#fff;">Recomandat</div>
    <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--sun);">Professional</div>
    <div class="flex items-baseline gap-1 mb-3"><span class="display text-5xl font-medium" style="color: var(--cream);">79</span><span style="color:#A8A29E;">lei / lună</span></div>
    <p class="text-sm mb-5 pb-5 border-b" style="color:#D7D3CA; border-color: rgba(255,255,255,.1);">Multi-canal + pipeline CRM.</p>
    <ul class="space-y-2.5 text-sm mb-8" style="color:#D7D3CA;">
        <li class="flex gap-2"><span style="color: var(--sun);">✓</span>3 agenți · 2500 conversații/lună</li>
        <li class="flex gap-2"><span style="color: var(--sun);">✓</span>WooCommerce · WhatsApp</li>
        <li class="flex gap-2"><span style="color: var(--sun);">✓</span>Lead scoring + pipeline</li>
    </ul>
    <a href="{{ url('/register') }}" class="btn btn-primary w-full justify-center" style="background: var(--sun); color: var(--ink); width:100%;">Alege Professional</a>
</div>
<div class="fade-up rounded-3xl p-7 bg-paper border border-line">
    <div class="mono text-xs uppercase tracking-wider mb-3" style="color: var(--muted);">Business</div>
    <div class="flex items-baseline gap-1 mb-3"><span class="display text-5xl font-medium">199</span><span style="color: var(--muted);">lei / lună</span></div>
    <p class="text-sm mb-5 pb-5 border-b border-line" style="color: var(--muted);">Volum mare, toate canalele, voce AI.</p>
    <ul class="space-y-2.5 text-sm mb-8">
        <li class="flex gap-2"><span class="accent-text">✓</span>10 agenți · 10.000 conversații/lună</li>
        <li class="flex gap-2"><span class="accent-text">✓</span>Voce AI disponibilă</li>
        <li class="flex gap-2"><span class="accent-text">✓</span>API + webhooks + suport prioritar</li>
    </ul>
    <a href="{{ url('/register') }}" class="btn btn-outline w-full justify-center" style="width:100%;">Alege Business</a>
</div>
