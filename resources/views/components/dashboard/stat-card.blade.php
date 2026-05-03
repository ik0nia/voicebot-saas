@props(['title', 'value', 'change' => null, 'icon' => null, 'color' => 'primary'])

@php
    $colorMap = [
        'primary' => ['bg' => 'bg-coralsoft', 'text' => 'text-coralh'],
        'blue'    => ['bg' => 'bg-coralsoft', 'text' => 'text-coralh'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber'   => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
        'red'     => ['bg' => 'bg-coralsoft', 'text' => 'text-coral'],
        'purple'  => ['bg' => 'bg-coralsoft', 'text' => 'text-coralh'],
        'indigo'  => ['bg' => 'bg-coralsoft', 'text' => 'text-coralh'],
        'slate'   => ['bg' => 'bg-cream', 'text' => 'text-muted'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="bg-white rounded-xl border border-line p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-muted">{{ $title }}</p>
            <p class="text-2xl font-bold text-ink mt-1">{{ $value }}</p>
            @if($change)
                <p class="text-sm mt-1 {{ str_starts_with($change, '+') ? 'text-emerald-600' : 'text-coral' }}">
                    {{ $change }} vs. ieri
                </p>
            @endif
        </div>
        @if($icon)
            <div class="w-12 h-12 rounded-xl {{ $colors['bg'] }} flex items-center justify-center {{ $colors['text'] }}">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
