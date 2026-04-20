{{--
  Niche background decor — iconițe SVG semitransparente distribuite
  non-overlapping peste o secțiune. Apelat cu:
    @include('new.partials.niche-decor', ['icons' => $nicheIcons, 'seed' => 'problema'])

  Folosim inline `style=""` pentru poziție/mărime — nu depinde de
  Tailwind JIT (care poate să nu recunoască variante negative sau
  arbitrare până la rebuild). Garantat să nu cadă pe default 0,0.
--}}
@php
    /* 4 iconițe în colțuri, trase parțial în afara secțiunii
       (negative inset-urile pe 24-32px) ca să nu atingă conținutul. */
    $slots = [
        ['top' => '-24px',    'left' => '-24px',    'w' => '80px',  'rot' => '-12deg', 'op' => '0.05'],
        ['top' => '-32px',    'right' => '-24px',   'w' => '96px',  'rot' => '6deg',   'op' => '0.05'],
        ['bottom' => '-24px', 'left' => '-16px',    'w' => '80px',  'rot' => '12deg',  'op' => '0.05'],
        ['bottom' => '-32px', 'right' => '-32px',   'w' => '96px',  'rot' => '-6deg',  'op' => '0.05'],
    ];

    $offset = abs(crc32($seed ?? 'default'));
    $iconCount = count($icons);
@endphp

@if($iconCount > 0)
<div class="absolute inset-0 overflow-hidden pointer-events-none hidden md:block" aria-hidden="true" style="z-index: 0;">
    @foreach($slots as $i => $slot)
        @php
            $iconIdx = ($offset + $i * 7) % $iconCount;
            $pos = [];
            foreach (['top','right','bottom','left'] as $k) {
                if (isset($slot[$k])) $pos[] = $k . ':' . $slot[$k];
            }
            $style = 'position:absolute;'
                . implode(';', $pos) . ';'
                . 'width:' . $slot['w'] . ';height:' . $slot['w'] . ';'
                . 'opacity:' . $slot['op'] . ';'
                . 'transform:rotate(' . $slot['rot'] . ');'
                . 'color:var(--accent);';
        @endphp
        <svg style="{{ $style }}" fill="none" stroke="currentColor" stroke-width="1.3"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            {!! $icons[$iconIdx] !!}
        </svg>
    @endforeach
</div>
@endif
