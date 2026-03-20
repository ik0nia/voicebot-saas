@props(['title', 'value', 'change' => null, 'icon' => null, 'color' => 'primary'])

@php
    $colorMap = [
        'primary' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        'blue'    => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber'   => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
        'red'     => ['bg' => 'bg-red-50', 'text' => 'text-red-600'],
        'purple'  => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
        'indigo'  => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600'],
        'slate'   => ['bg' => 'bg-slate-50', 'text' => 'text-slate-600'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $value }}</p>
            @if($change)
                <p class="text-sm mt-1 {{ str_starts_with($change, '+') ? 'text-emerald-600' : 'text-red-500' }}">
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
