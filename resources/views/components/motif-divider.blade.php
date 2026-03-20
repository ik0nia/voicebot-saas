@props(['class' => ''])

{{-- Traditional Romanian cross-stitch inspired divider --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-3 ' . $class]) }}>
    <div class="h-px flex-1 bg-gradient-to-r from-transparent to-primary-200"></div>
    <svg width="40" height="20" viewBox="0 0 40 20" fill="none" class="shrink-0">
        {{-- Romanian rhombus/diamond motif --}}
        <path d="M20 2 L26 10 L20 18 L14 10 Z" class="fill-primary-100 stroke-primary-300" stroke-width="1"/>
        <path d="M20 6 L23 10 L20 14 L17 10 Z" class="fill-primary-200"/>
        <rect x="19" y="9" width="2" height="2" class="fill-primary-500"/>
        {{-- Small side crosses --}}
        <rect x="5" y="9" width="4" height="2" class="fill-primary-200"/>
        <rect x="6" y="8" width="2" height="4" class="fill-primary-200"/>
        <rect x="31" y="9" width="4" height="2" class="fill-primary-200"/>
        <rect x="32" y="8" width="2" height="4" class="fill-primary-200"/>
    </svg>
    <div class="h-px flex-1 bg-gradient-to-l from-transparent to-primary-200"></div>
</div>
