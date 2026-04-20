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
    $slots = [
        ['t' => 'top-6 left-[5%]',        'size' => 'w-28 h-28',  'rot' => '-rotate-12', 'op' => 'opacity-[0.07]', 'stroke' => '1.2'],
        ['t' => 'top-10 right-[7%]',      'size' => 'w-40 h-40',  'rot' => 'rotate-6',   'op' => 'opacity-[0.06]', 'stroke' => '1.1'],
        ['t' => 'top-[32%] left-[44%]',   'size' => 'w-16 h-16',  'rot' => 'rotate-3',   'op' => 'opacity-[0.05]', 'stroke' => '1.5'],
        ['t' => 'bottom-10 left-[8%]',    'size' => 'w-32 h-32',  'rot' => 'rotate-12',  'op' => 'opacity-[0.08]', 'stroke' => '1.2'],
        ['t' => 'bottom-14 right-[18%]',  'size' => 'w-20 h-20',  'rot' => '-rotate-6',  'op' => 'opacity-[0.07]', 'stroke' => '1.3'],
        ['t' => 'top-[20%] right-[28%]',  'size' => 'w-12 h-12',  'rot' => 'rotate-6',   'op' => 'opacity-[0.06]', 'stroke' => '1.6'],
        ['t' => 'top-[50%] left-[15%]',   'size' => 'w-14 h-14',  'rot' => '-rotate-12', 'op' => 'opacity-[0.055]','stroke' => '1.5'],
        ['t' => 'bottom-[28%] right-[5%]','size' => 'w-24 h-24',  'rot' => 'rotate-12',  'op' => 'opacity-[0.07]', 'stroke' => '1.2'],
        ['t' => 'top-24 left-[28%]',      'size' => 'w-10 h-10',  'rot' => '-rotate-3',  'op' => 'opacity-[0.05]', 'stroke' => '1.7'],
        ['t' => 'bottom-6 left-[52%]',    'size' => 'w-14 h-14',  'rot' => 'rotate-3',   'op' => 'opacity-[0.06]', 'stroke' => '1.5'],
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
