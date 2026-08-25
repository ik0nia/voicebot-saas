@extends('layouts.dashboard')

@section('title', 'Livrare & comenzi — ' . $bot->name)

@php
    // Cents → the decimal string an operator types. Null stays empty rather
    // than becoming "0,00": an empty free-delivery threshold means "no free
    // delivery", which is not the same as "free over 0 lei".
    $money = fn (?int $cents) => $cents === null ? '' : number_format($cents / 100, 2, ',', '');

    $zones = old('zone_name')
        ? collect(old('zone_name'))->map(fn ($name, $i) => [
            'name'            => $name,
            'fee_cents'       => null,
            'min_order_cents' => null,
            '_fee'            => old('zone_fee')[$i] ?? '',
            '_min'            => old('zone_min')[$i] ?? '',
        ])->all()
        : collect($settings->delivery_zones ?? [])->map(fn ($z) => [
            'name'  => $z['name'] ?? '',
            '_fee'  => isset($z['fee_cents']) ? number_format(((int) $z['fee_cents']) / 100, 2, ',', '') : '',
            '_min'  => isset($z['min_order_cents']) ? number_format(((int) $z['min_order_cents']) / 100, 2, ',', '') : '',
        ])->all();
@endphp

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4">
    <div class="text-xs text-muted mb-1">
        <a href="{{ route('dashboard.bots.edit', $bot) }}" class="hover:text-coralh">← {{ $bot->name }}</a>
    </div>
    <h1 class="text-2xl font-bold text-ink">Livrare & comenzi</h1>
    <p class="text-sm text-muted mt-1 mb-6">
        Tot ce ține de politica localului. Agentul nu inventează niciun preț și niciun termen —
        le citește de aici.
    </p>

    @include('dashboard.bots.restaurant._nav', ['active' => 'settings'])

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @unless($configured)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span class="mr-1">⚠️</span>
            Localul nu a fost configurat încă. Până salvezi această pagină cu
            <strong>„Agentul preia comenzi”</strong> bifat, la telefon va răspunde că nu se iau comenzi.
        </div>
    @endunless

    <form method="POST" action="{{ route('dashboard.bots.restaurant.settings.update', $bot) }}"
          x-data="{
              delivery: {{ old('delivery_enabled', $settings->delivery_enabled) ? 'true' : 'false' }},
              zones: {{ Js::from(array_values($zones)) }},
              addZone() { this.zones.push({ name: '', _fee: '', _min: '' }) },
          }"
          class="space-y-5">
        @csrf
        @method('PUT')

        {{-- Master switch --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="ordering_enabled" value="1"
                       @checked(old('ordering_enabled', $settings->ordering_enabled))
                       class="mt-1 rounded border-line text-coral">
                <span>
                    <span class="block text-sm font-semibold text-ink">Agentul preia comenzi</span>
                    <span class="block text-xs text-muted mt-0.5">
                        Cât timp e debifat, agentul răspunde politicos că localul nu ia comenzi prin telefon.
                        Rezervările de masă nu sunt afectate.
                    </span>
                </span>
            </label>
        </div>

        {{-- Fulfilment --}}
        <div class="bg-white rounded-xl border border-line p-5 space-y-4">
            <div class="text-xs font-semibold text-muted uppercase">Cum ajunge mâncarea la client</div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="delivery_enabled" value="1" x-model="delivery"
                       class="rounded border-line text-coral">
                <span class="text-sm text-ink">Livrare la domiciliu</span>
            </label>

            <div x-show="delivery" x-cloak class="pl-6 space-y-4 border-l-2 border-cream">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Taxă livrare</label>
                        <div class="relative">
                            <input type="text" name="delivery_fee" inputmode="decimal"
                                   value="{{ old('delivery_fee', $money($settings->delivery_fee_cents)) }}"
                                   placeholder="0,00"
                                   class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-14 text-sm">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">RON</span>
                        </div>
                        <p class="text-2xs text-muted mt-1">Taxa implicită, folosită unde zona nu are alta.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Livrare gratuită peste</label>
                        <div class="relative">
                            <input type="text" name="free_delivery_threshold" inputmode="decimal"
                                   value="{{ old('free_delivery_threshold', $money($settings->free_delivery_threshold_cents)) }}"
                                   placeholder="lasă gol"
                                   class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-14 text-sm">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">RON</span>
                        </div>
                        <p class="text-2xs text-muted mt-1">Gol = fără livrare gratuită.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Comandă minimă</label>
                        <div class="relative">
                            <input type="text" name="min_order" inputmode="decimal"
                                   value="{{ old('min_order', $money($settings->min_order_cents)) }}"
                                   placeholder="0,00"
                                   class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-14 text-sm">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">RON</span>
                        </div>
                    </div>
                </div>

                {{-- Zones --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-ink">Zone de livrare</label>
                        <button type="button" @click="addZone()" class="text-xs text-coralh hover:underline">+ adaugă zonă</button>
                    </div>

                    <template x-for="(zone, i) in zones" :key="i">
                        <div class="flex flex-wrap items-end gap-2 mb-2">
                            <div class="flex-1 min-w-[140px]">
                                <input type="text" :name="`zone_name[${i}]`" x-model="zone.name"
                                       placeholder="ex. Gherla"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="w-28">
                                <input type="text" :name="`zone_fee[${i}]`" x-model="zone._fee" inputmode="decimal"
                                       placeholder="taxă"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="w-28">
                                <input type="text" :name="`zone_min[${i}]`" x-model="zone._min" inputmode="decimal"
                                       placeholder="min."
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <button type="button" @click="zones.splice(i, 1)"
                                    class="text-xs text-muted hover:text-coralh p-2 rounded hover:bg-coralsoft">🗑</button>
                        </div>
                    </template>

                    <p x-show="zones.length === 0" class="text-xs text-muted">
                        Nicio zonă. Agentul aplică taxa implicită pentru orice adresă.
                    </p>

                    <label class="flex items-start gap-3 cursor-pointer mt-3">
                        <input type="checkbox" name="delivery_zones_only" value="1"
                               @checked(old('delivery_zones_only', $settings->delivery_zones_only))
                               class="mt-1 rounded border-line text-coral">
                        <span>
                            <span class="block text-sm text-ink">Livrez <strong>doar</strong> în zonele de mai sus</span>
                            <span class="block text-2xs text-muted mt-0.5">
                                O adresă care nu se potrivește niciunei zone e refuzată, iar agentul propune ridicarea personală.
                                Fără asta, orice adresă e acceptată cu taxa implicită.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="w-40">
                    <label class="block text-sm font-medium text-ink mb-1">Timp livrare</label>
                    <div class="relative">
                        <input type="number" name="delivery_minutes" min="1" max="600"
                               value="{{ old('delivery_minutes', $settings->delivery_minutes) }}"
                               class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-12 text-sm">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">min</span>
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer pt-2 border-t border-line">
                <input type="checkbox" name="pickup_enabled" value="1"
                       @checked(old('pickup_enabled', $settings->pickup_enabled))
                       class="rounded border-line text-coral">
                <span class="text-sm text-ink">Ridicare personală</span>
            </label>
            <div class="pl-6 w-40">
                <label class="block text-sm font-medium text-ink mb-1">Timp pregătire</label>
                <div class="relative">
                    <input type="number" name="pickup_minutes" min="1" max="600"
                           value="{{ old('pickup_minutes', $settings->pickup_minutes) }}"
                           class="w-full rounded-lg border border-line bg-white px-3 py-2 pr-12 text-sm">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted">min</span>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="bg-white rounded-xl border border-line p-5 space-y-3">
            <div class="text-xs font-semibold text-muted uppercase">Plată</div>
            @php $selectedMethods = old('payment_methods', $settings->paymentMethods()); @endphp
            <div class="flex flex-wrap gap-4">
                @foreach(['cash' => 'Cash la livrare/ridicare', 'card_on_delivery' => 'Card la livrare'] as $value => $label)
                    <label class="inline-flex items-center gap-2 text-sm text-inkSoft">
                        <input type="checkbox" name="payment_methods[]" value="{{ $value }}"
                               @checked(in_array($value, (array) $selectedMethods, true))
                               class="rounded border-line text-coral">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="text-2xs text-muted">
                Platforma nu procesează plăți — comanda se plătește la livrare și e doar înregistrată aici.
            </p>
        </div>

        {{-- Featured dishes --}}
        @php $featured = old('featured_item_ids', $settings->featured_item_ids ?? []); @endphp
        <div class="bg-white rounded-xl border border-line p-5"
             x-data="{ picked: {{ Js::from(array_map('intval', (array) $featured)) }},
                       toggle(id) {
                           const i = this.picked.indexOf(id);
                           if (i > -1) this.picked.splice(i, 1);
                           else if (this.picked.length < 3) this.picked.push(id);
                       } }">
            <div class="text-xs font-semibold text-muted uppercase mb-1">Ce recomandă agentul</div>
            <p class="text-sm text-muted mb-3">
                Când clientul întreabă „ce aveți?", agentul spune exact aceste preparate, cu prețul lor.
                Alege până la trei, în ordinea în care vrei să le spună. Dacă nu alegi niciunul,
                întreabă doar ce dorește clientul, fără să sugereze nimic.
            </p>

            @if($menuItems->isEmpty())
                <p class="text-sm text-amber-700">
                    Niciun preparat disponibil în meniu.
                    <a href="{{ route('dashboard.bots.restaurant.menu', $bot) }}" class="text-coralh hover:underline">Adaugă preparate →</a>
                </p>
            @else
                {{-- Submitted in click order, which is the order the bot says them in. --}}
                <template x-for="id in picked" :key="id">
                    <input type="hidden" name="featured_item_ids[]" :value="id">
                </template>

                <div class="flex flex-wrap gap-2">
                    @foreach($menuItems as $mi)
                        <button type="button" @click="toggle({{ $mi->id }})"
                                :disabled="picked.length >= 3 && !picked.includes({{ $mi->id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill text-xs font-medium border transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="picked.includes({{ $mi->id }})
                                    ? 'bg-coral text-white border-coral'
                                    : 'bg-white text-inkSoft border-line hover:bg-cream'">
                            <span x-show="picked.includes({{ $mi->id }})"
                                  x-text="picked.indexOf({{ $mi->id }}) + 1"
                                  class="w-4 h-4 rounded-full bg-white/25 grid place-items-center text-2xs font-bold"></span>
                            {{ $mi->name }}
                            <span class="opacity-70">{{ $mi->formattedPrice() }}</span>
                        </button>
                    @endforeach
                </div>

                <p class="text-2xs text-muted mt-3" x-show="picked.length >= 3">
                    Trei e maximul — la telefon, o listă mai lungă nu se reține.
                </p>
            @endif
        </div>

        {{-- Notice --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <label class="block text-sm font-medium text-ink mb-1">Mențiune spusă la final</label>
            <textarea name="order_notice" rows="2"
                      placeholder="ex. Livrare gratuită doar în Gherla. Plata la livrare, cash sau card."
                      class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">{{ old('order_notice', $settings->order_notice) }}</textarea>
            <p class="text-2xs text-muted mt-1">O propoziție pe care agentul o are la îndemână când confirmă comanda.</p>
        </div>

        <input type="hidden" name="currency" value="{{ $settings->currency ?: 'RON' }}">

        <div class="flex items-center justify-end">
            <button type="submit" class="btn-coral rounded-lg px-5 py-2.5 text-sm font-semibold">
                Salvează setările
            </button>
        </div>
    </form>
</div>
@endsection
