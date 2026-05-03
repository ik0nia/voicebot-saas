@extends('layouts.dashboard')

@section('title', 'Butoane rapide — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-muted hover:text-inkSoft">Canale</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Butoane rapide</span>
@endsection

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-ink">Butoane rapide (Quick Replies)</h1>
            <p class="mt-1 text-sm text-muted">Editează sugestiile care apar când vizitatorul deschide widget-ul, în funcție de tipul paginii. Fiecare agent îşi poate avea setul lui.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Context banner — the 3 "phases" when chips show up --}}
        <div class="mb-6 rounded-xl border border-line bg-white p-5">
            <h2 class="text-sm font-semibold text-ink">Când apar butoanele</h2>
            <ul class="mt-3 space-y-2 text-sm text-muted">
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700">1</span>
                    <span><strong>La deschiderea conversației</strong> — setate mai jos, grupate pe tipul paginii. <em>Editabile.</em></span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-cream text-[11px] font-bold text-inkSoft">2</span>
                    <span><strong>După fiecare răspuns al agentului</strong> — butoane adaptive care se schimbă în funcție de starea conversației (compară, aproape de finalizare, blocat, sensibil la preț). <em>Optimizate automat — nu-s editabile momentan.</em></span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[11px] font-bold text-amber-700">3</span>
                    <span><strong>Momente speciale</strong> — „Lasă-mi datele" când agentul nu știe răspunsul, „Adaugă în coș" pe pagină de produs, „Până la livrare gratuită" sub prag. <em>Automat.</em></span>
                </li>
            </ul>
        </div>

        <form method="POST" action="{{ route('dashboard.bots.channels.chips.update', [$bot, $channel]) }}"
              x-data="chipEditor({{ Js::from($cards) }})"
              @submit="prepareSubmit">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <template x-for="(card, idx) in cards" :key="card.key">
                    <div class="rounded-xl border border-line bg-white">
                        <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-ink" x-text="card.title"></h3>
                                    <span x-show="card.is_overridden"
                                          class="inline-flex items-center rounded-full bg-coralsoft px-2 py-0.5 text-[10px] font-medium text-coralh">
                                        Personalizat
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-muted" x-text="card.description"></p>
                                <p class="mt-1 font-mono text-[11px] text-muted" x-text="'page_type: ' + card.key"></p>
                            </div>
                            <button type="button" @click="resetCard(idx)"
                                    class="whitespace-nowrap rounded-lg border border-line bg-white px-3 py-1.5 text-xs font-medium text-muted hover:bg-cream">
                                Resetează la default
                            </button>
                        </div>

                        <div class="space-y-4 p-5">
                            <div>
                                <label class="block text-xs font-medium text-muted mb-1">Mesaj de întâmpinare (opțional)</label>
                                <input type="text" x-model="card.opening" maxlength="240"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm text-inkSoft focus:border-coral focus:ring-1 focus:ring-coral outline-none"
                                       placeholder="Afișat deasupra butoanelor la prima deschidere">
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label class="block text-xs font-medium text-muted">Butoane (max 6)</label>
                                    <span class="text-[11px] text-muted" x-text="card.chips.length + '/6'"></span>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(chip, cidx) in card.chips" :key="idx + '-' + cidx">
                                        <div class="flex gap-2 items-start">
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2">
                                                <input type="text" x-model="chip.label" maxlength="40" placeholder="Label (ce vede userul)"
                                                       class="md:col-span-2 rounded-lg border border-line px-3 py-2 text-sm text-inkSoft focus:border-coral focus:ring-1 focus:ring-coral outline-none">
                                                <input type="text" x-model="chip.text" maxlength="500" placeholder="Text trimis ca mesaj (ce „spune" userul la click)"
                                                       class="md:col-span-3 rounded-lg border border-line px-3 py-2 text-sm text-inkSoft focus:border-coral focus:ring-1 focus:ring-coral outline-none">
                                            </div>
                                            <button type="button" @click="removeChip(idx, cidx)"
                                                    class="shrink-0 rounded-lg border border-line bg-white px-2.5 py-2 text-muted hover:border-coral/30 hover:bg-coralsoft hover:text-coral"
                                                    title="Șterge butonul">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M10 7V4a1 1 0 011-1h2a1 1 0 011 1v3"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <button type="button" @click="addChip(idx)" x-show="card.chips.length < 6"
                                        class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-line px-3 py-1.5 text-xs font-medium text-muted hover:border-red-300 hover:text-coralh">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Adaugă buton
                                </button>

                                <p x-show="card.chips.length === 0" class="mt-2 text-xs italic text-muted">
                                    Fără butoane — la deschidere nu se afişează sugestii rapide pentru acest tip de pagină.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                   class="text-sm text-muted hover:text-inkSoft">← Înapoi la canale</a>
                <div class="flex items-center gap-3">
                    <button type="button" @click="resetAll"
                            class="rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-muted hover:bg-cream">
                        Resetează totul la default
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-coral">
                        Salvează butoanele
                    </button>
                </div>
            </div>

            {{-- Hidden payload — Alpine writes the final JSON here on submit --}}
            <input type="hidden" name="_payload" x-ref="payload" value="">
            <template x-for="card in cards" :key="'input-' + card.key">
                <div>
                    <input type="hidden" :name="'widget_contexts[' + card.key + '][opening]'" :value="card.opening">
                    <template x-for="(chip, cidx) in card.chips" :key="'in-' + card.key + '-' + cidx">
                        <div>
                            <input type="hidden" :name="'widget_contexts[' + card.key + '][quick_replies][' + cidx + '][label]'" :value="chip.label">
                            <input type="hidden" :name="'widget_contexts[' + card.key + '][quick_replies][' + cidx + '][text]'" :value="chip.text">
                        </div>
                    </template>
                </div>
            </template>
        </form>
    </div>

    <script>
        function chipEditor(initialCards) {
            return {
                cards: JSON.parse(JSON.stringify(initialCards)),
                addChip(idx) {
                    if (this.cards[idx].chips.length >= 6) return;
                    this.cards[idx].chips.push({ label: '', text: '' });
                },
                removeChip(idx, cidx) {
                    this.cards[idx].chips.splice(cidx, 1);
                },
                resetCard(idx) {
                    const card = this.cards[idx];
                    card.opening = card.defaults_opening || '';
                    card.chips = JSON.parse(JSON.stringify(card.defaults_chips || []));
                    card.is_overridden = false;
                },
                resetAll() {
                    if (!confirm('Resetezi toate butoanele la valorile implicite? Personalizările curente se pierd.')) return;
                    this.cards.forEach((_, i) => this.resetCard(i));
                },
                prepareSubmit(e) {
                    // Hidden inputs are already bound via Alpine — nothing to do.
                },
            };
        }
    </script>
@endsection
