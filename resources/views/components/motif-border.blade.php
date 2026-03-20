@props(['class' => '', 'color' => 'primary-200'])

{{-- Traditional Romanian geometric border pattern (inspired by ie/costum popular) --}}
<div {{ $attributes->merge(['class' => 'w-full overflow-hidden ' . $class]) }}>
    <svg class="w-full" height="16" viewBox="0 0 1200 16" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
        <pattern id="romanianMotif-{{ $color }}" x="0" y="0" width="48" height="16" patternUnits="userSpaceOnUse">
            {{-- Cross/rhombus pattern typical of Romanian embroidery --}}
            <rect x="22" y="0" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="18" y="4" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="26" y="4" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="14" y="8" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="22" y="8" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="30" y="8" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="18" y="12" width="4" height="4" class="fill-{{ $color }}"/>
            <rect x="26" y="12" width="4" height="4" class="fill-{{ $color }}"/>
            {{-- Small dots --}}
            <rect x="6" y="6" width="3" height="3" class="fill-{{ $color }}" opacity="0.5"/>
            <rect x="39" y="6" width="3" height="3" class="fill-{{ $color }}" opacity="0.5"/>
        </pattern>
        <rect width="1200" height="16" fill="url(#romanianMotif-{{ $color }})"/>
    </svg>
</div>
