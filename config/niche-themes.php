<?php

/**
 * Niche theme palettes for vertical landing pages.
 *
 * Each theme is a flat array of Tailwind class strings injected into the
 * blade templates. Only standard Tailwind 3.x palette steps are used so that
 * the JIT/purge step keeps them in the final CSS bundle.
 */

$make = function (string $color) {
    return [
        'eyebrow_bg'      => "bg-{$color}-50",
        'eyebrow_text'    => "text-{$color}-700",
        'gradient_from'   => "from-{$color}-600",
        'gradient_via'    => "via-{$color}-500",
        'gradient_to'     => "to-{$color}-400",
        'button_primary'  => "inline-flex items-center gap-2 px-6 py-4 bg-gradient-to-r from-{$color}-700 to-{$color}-600 text-white font-bold rounded-xl hover:from-{$color}-600 hover:to-{$color}-500 transition-all duration-300 shadow-lg shadow-{$color}-900/30",
        'button_secondary'=> "inline-flex items-center gap-2 px-6 py-4 bg-white border border-{$color}-200 text-{$color}-700 font-bold rounded-xl hover:bg-{$color}-50 transition-all duration-300",
        'badge_solid'     => "bg-{$color}-600 text-white",
        'card_border'     => "border-{$color}-100 hover:border-{$color}-300",
        'card_accent'     => "bg-{$color}-50 text-{$color}-700",
        'icon_bg'         => "bg-{$color}-100",
        'icon_text'       => "text-{$color}-600",
        'check_text'      => "text-{$color}-600",
        'hero_blob'       => "bg-{$color}-900/20",
        'hero_blob_2'     => "bg-{$color}-800/10",
        'demo_bot_bg'     => "bg-{$color}-50",
        'demo_bot_text'   => "text-{$color}-900",
        'ring'            => "ring-{$color}-500/20",
    ];
};

return [
    'red'     => $make('red'),
    'emerald' => $make('emerald'),
    'blue'    => $make('blue'),
    'amber'   => $make('amber'),
    'rose'    => $make('rose'),
    'purple'  => $make('purple'),
    'indigo'  => $make('indigo'),
    'teal'    => $make('teal'),
    'cyan'    => $make('cyan'),
    'orange'  => $make('orange'),
];
