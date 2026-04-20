{{--
  Niche background decor — iconițe SVG semitransparente distribuite
  non-overlapping peste o secțiune. Apelat cu:
    @include('new.partials.niche-decor', ['icons' => $nicheIcons, 'seed' => 'problema'])

  Strategie:
  - 10 sloturi pre-validate (poziții + mărimi + rotații non-overlapping
    pentru secțiuni de 500-900px înălțime).
  - Seed-ul (string-ul secțiunii) e hash-uit și folosit ca offset la
    alegerea slot-urilor și a iconițelor, deci fiecare secțiune arată
    diferit dar consistent la refresh.
  - Hidden pe mobile unde overlap-ul cu conținutul e problematic.
--}}
@php
    /* Doar colțuri + middle-axe, FĂRĂ sloturi în zona centrală
       (0.3-0.7 orizontal × 0.3-0.7 vertical). Fiecare slot are o
       zonă de siguranță în jur garantată de poziția relativă. */
    $slots = [
        // 4 colțuri mari
        ['t' => 'top-4 left-[3%]',        'size' => 'w-24 h-24',  'rot' => '-rotate-12', 'op' => 'opacity-[0.07]', 'stroke' => '1.2'],
        ['t' => 'top-6 right-[4%]',       'size' => 'w-32 h-32',  'rot' => 'rotate-6',   'op' => 'opacity-[0.06]', 'stroke' => '1.1'],
        ['t' => 'bottom-6 left-[4%]',     'size' => 'w-28 h-28',  'rot' => 'rotate-12',  'op' => 'opacity-[0.07]', 'stroke' => '1.2'],
        ['t' => 'bottom-8 right-[3%]',    'size' => 'w-24 h-24',  'rot' => '-rotate-6',  'op' => 'opacity-[0.065]','stroke' => '1.3'],
        // 2 accente pe marginile lateral-middle (minim 30% depărtare de colțuri)
        ['t' => 'top-[45%] left-[2%]',    'size' => 'w-14 h-14',  'rot' => '-rotate-3',  'op' => 'opacity-[0.055]','stroke' => '1.5'],
        ['t' => 'top-[48%] right-[2%]',   'size' => 'w-16 h-16',  'rot' => 'rotate-3',   'op' => 'opacity-[0.055]','stroke' => '1.4'],
    ];

    $offset = abs(crc32($seed ?? 'default'));
    $iconCount = count($icons);
    $slotCount = count($slots);
@endphp

@if($iconCount > 0)
<div class="absolute inset-0 overflow-hidden pointer-events-none hidden md:block" aria-hidden="true">
    @foreach($slots as $i => $slot)
        @php
            // Alegem o iconiță "pseudo-random" per slot, dar deterministic pe seed.
            $iconIdx = ($offset + $i * 7) % $iconCount;
        @endphp
        <svg class="absolute {{ $slot['t'] }} {{ $slot['size'] }} {{ $slot['rot'] }} {{ $slot['op'] }} accent-text"
             fill="none" stroke="currentColor" stroke-width="{{ $slot['stroke'] }}"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            {!! $icons[$iconIdx] !!}
        </svg>
    @endforeach
</div>
@endif
