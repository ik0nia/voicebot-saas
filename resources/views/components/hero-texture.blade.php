{{-- Subtle Romanian traditional pattern texture for hero backgrounds --}}
@php $uid = 'motif-' . Str::random(6); @endphp
<div class="absolute inset-0 opacity-[0.03]">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="{{ $uid }}" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                <path d="M30 10 L40 20 L30 30 L20 20 Z" fill="#991b1b"/>
                <rect x="28" y="0" width="4" height="8" fill="#991b1b"/>
                <rect x="28" y="32" width="4" height="8" fill="#991b1b"/>
                <rect x="10" y="18" width="8" height="4" fill="#991b1b"/>
                <rect x="42" y="18" width="8" height="4" fill="#991b1b"/>
                <rect x="0" y="44" width="4" height="4" fill="#991b1b"/>
                <rect x="56" y="44" width="4" height="4" fill="#991b1b"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#{{ $uid }})"/>
    </svg>
</div>
