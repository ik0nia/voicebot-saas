@extends('layouts.app')

@section('title', 'Contact - Sambla')
@section('meta_description', 'Contactează echipa Sambla. Suntem aici să te ajutăm cu orice întrebare despre platforma noastră de agenți vocali AI.')

@section('content')

{{-- Hero Section --}}
<section class="pt-32 lg:pt-40 pb-16 lg:pb-20 bg-gradient-to-b from-primary-50 to-white">
    <div class="container-custom text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 animate-fade-in">
            <span class="gradient-text">Contactează-ne</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed animate-fade-in-delay-1">
            Suntem aici să te ajutăm. Răspundem în maxim 2 ore.
        </p>
    </div>
</section>

{{-- Contact Form + Info --}}
<section class="section-padding">
    <div class="container-custom">
        <div class="grid lg:grid-cols-5 gap-12 lg:gap-16">
            {{-- Left: Contact Form --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl p-8 lg:p-10 shadow-sm border border-slate-100">
                    <h2 class="text-2xl font-bold text-slate-900 mb-8">Trimite-ne un mesaj</h2>

                    {{-- Success Message (hidden by default) --}}
                    <div id="contact-success" class="hidden mb-8 p-6 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-emerald-800 font-medium">Mulțumesc! Mesajul tău a fost trimis. Te vom contacta în curând.</p>
                        </div>
                    </div>

                    <form id="contact-form" action="/contact" method="POST">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-6 mb-6">
                            {{-- Nume --}}
                            <div>
                                <label for="nume" class="block text-sm font-semibold text-slate-700 mb-2">Nume <span class="text-red-500">*</span></label>
                                <input type="text" id="nume" name="nume" required placeholder="Numele tău complet"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" required placeholder="email@companie.ro"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Companie --}}
                            <div>
                                <label for="companie" class="block text-sm font-semibold text-slate-700 mb-2">Companie <span class="text-red-500">*</span></label>
                                <input type="text" id="companie" name="companie" required placeholder="Numele companiei"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>

                            {{-- Telefon --}}
                            <div>
                                <label for="telefon" class="block text-sm font-semibold text-slate-700 mb-2">Telefon <span class="text-slate-400 font-normal">(opțional)</span></label>
                                <input type="tel" id="telefon" name="telefon" placeholder="+40 7XX XXX XXX"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400">
                            </div>
                        </div>

                        {{-- Subiect --}}
                        <div class="mb-6">
                            <label for="subiect" class="block text-sm font-semibold text-slate-700 mb-2">Subiect <span class="text-red-500">*</span></label>
                            <select id="subiect" name="subiect" required
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 bg-white">
                                <option value="" disabled selected>Selectează un subiect</option>
                                <option value="informatii">Informații generale</option>
                                <option value="demo">Solicitare demo</option>
                                <option value="suport">Suport tehnic</option>
                                <option value="parteneriat">Parteneriat</option>
                                <option value="altele">Altele</option>
                            </select>
                        </div>

                        {{-- Mesaj --}}
                        <div class="mb-8">
                            <label for="mesaj" class="block text-sm font-semibold text-slate-700 mb-2">Mesaj <span class="text-red-500">*</span></label>
                            <textarea id="mesaj" name="mesaj" required rows="5" placeholder="Scrie mesajul tău aici..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all duration-200 text-slate-900 placeholder-slate-400 resize-y"></textarea>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" class="btn-primary text-lg px-8 py-4 w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            Trimite mesajul
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right: Contact Info --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Contact Details --}}
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
                    <h3 class="text-xl font-bold text-slate-900 mb-6">Informații de contact</h3>
                    <div class="space-y-6">
                        {{-- Email --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Email</p>
                                <a href="mailto:contact@voicebot.ro" class="text-sm text-primary-600 hover:text-primary-700 transition-colors">contact@voicebot.ro</a>
                            </div>
                        </div>

                        {{-- Telefon --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Telefon</p>
                                <a href="tel:+4021XXXXXXX" class="text-sm text-slate-600 hover:text-primary-600 transition-colors">+40 21 XXX XXXX</a>
                            </div>
                        </div>

                        {{-- Adresă --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Adresă</p>
                                <p class="text-sm text-slate-600">Str. Exemplu nr. 10,<br>București, România</p>
                            </div>
                        </div>

                        {{-- Program --}}
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 mb-1">Program</p>
                                <p class="text-sm text-slate-600">Luni - Vineri, 09:00 - 18:00</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Map Placeholder --}}
                <div class="bg-slate-100 rounded-2xl border-2 border-dashed border-slate-300 h-56 flex flex-col items-center justify-center">
                    <svg class="w-10 h-10 text-slate-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                    </svg>
                    <p class="text-sm font-medium text-slate-500">Google Maps</p>
                    <p class="text-xs text-slate-400 mt-1">Hartă interactivă</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ Mini-section --}}
<section class="section-padding bg-slate-50">
    <div class="container-custom">
        <h2 class="text-3xl md:text-4xl font-bold text-slate-900 text-center mb-4">Întrebări frecvente</h2>
        <p class="text-lg text-slate-600 text-center max-w-2xl mx-auto mb-12">Răspunsuri rapide la cele mai comune întrebări.</p>
        <div class="max-w-3xl mx-auto space-y-6">
            {{-- Q1 --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-900 mb-3">Cât durează implementarea?</h3>
                <p class="text-slate-600 leading-relaxed">În medie, configurarea inițială durează 1-2 zile. Echipa noastră te ghidează pas cu pas prin întreg procesul.</p>
            </div>

            {{-- Q2 --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-900 mb-3">Oferiți suport în română?</h3>
                <p class="text-slate-600 leading-relaxed">Da, întreaga echipă de suport vorbește română nativ. Comunicăm în limba ta, fără bariere.</p>
            </div>

            {{-- Q3 --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-900 mb-3">Putem avea o demonstrație live?</h3>
                <p class="text-slate-600 leading-relaxed">Desigur! Completează formularul de mai sus sau sună-ne direct. Programăm o demonstrație personalizată în funcție de nevoile tale.</p>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function() {
        var form = document.getElementById('contact-form');
        var success = document.getElementById('contact-success');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                form.style.display = 'none';
                success.classList.remove('hidden');
                success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
    })();
</script>
@endpush
