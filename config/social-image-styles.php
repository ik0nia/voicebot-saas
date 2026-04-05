<?php

/**
 * Visual style presets for social media image generation.
 * Based on sambla.ro design language.
 */
return [
    'dark' => [
        'name' => 'Dark Tech',
        'logo' => 'dark',
        'prompt' => 'Dark slate background (#0f172a to #1e293b). White and light gray text. Red accent elements (#991b1b, #dc2626). '
            . 'Glassmorphism cards with backdrop blur and subtle borders. Soft shadows. '
            . 'Modern SaaS/tech aesthetic. Chat interface elements with red user bubbles and gray bot bubbles.',
    ],

    'light' => [
        'name' => 'Clean Light',
        'logo' => 'light',
        'prompt' => 'Clean white/off-white background (#ffffff, #f8fafc). Dark slate text (#0f172a). '
            . 'White cards with soft shadows (0 2px 12px rgba(0,0,0,0.06)) and rounded corners. '
            . 'Red accent color (#991b1b) for headings and CTAs. Light gray borders (#e2e8f0). '
            . 'Minimalist, spacious layout. Professional SaaS feel.',
    ],

    'red_bold' => [
        'name' => 'Red Bold',
        'logo' => 'dark',
        'prompt' => 'Bold red gradient background (from #991b1b to #dc2626). White text, large and impactful. '
            . 'Dark overlay elements. Glossy red surfaces. White iconography. '
            . 'High contrast, energetic, attention-grabbing. CTA buttons in white with red text.',
    ],

    'warm_traditional' => [
        'name' => 'Warm Traditional',
        'logo' => 'light',
        'prompt' => 'Warm cream/ivory background (#fff7ed, #fefce8). Dark red (#991b1b) as primary color. '
            . 'Subtle Romanian traditional geometric patterns as decorative borders or background texture — '
            . 'inspired by ie romaneasca cross-stitch motifs in red and dark tones. '
            . 'Elegant serif-style headings mixed with modern sans-serif body. '
            . 'Rounded cards with warm shadows. Cultural yet modern tech feel.',
    ],

    'pastel_fresh' => [
        'name' => 'Pastel Fresh',
        'logo' => 'light',
        'prompt' => 'Soft pastel background — mix of light tints: pale red (#fef2f2), pale green (#f0fdf4), pale blue (#eff6ff). '
            . 'Gradient mesh or soft color blobs in background. Dark slate text (#0f172a). '
            . 'Red accent (#dc2626) for CTAs and highlights. '
            . 'Playful yet professional. Rounded shapes, friendly icons. Modern startup aesthetic.',
    ],

    'dark_red_split' => [
        'name' => 'Dark Red Split',
        'logo' => 'dark',
        'prompt' => 'Split design — left half dark slate (#0f172a), right half deep red (#991b1b). '
            . 'White text on both sides. Diagonal or curved split line. '
            . 'Product mockup or chat interface on the dark side, text/CTA on the red side. '
            . 'Bold, high-impact, duotone. Geometric shapes as accents.',
    ],

    'gradient_modern' => [
        'name' => 'Gradient Modern',
        'logo' => 'dark',
        'prompt' => 'Smooth gradient background from dark slate (#0f172a) through deep red (#991b1b) to bright red (#ef4444). '
            . 'White text with subtle glow effect. Floating UI elements — notification badges, chat bubbles, analytics widgets. '
            . 'Abstract tech patterns — circuit lines, dots grid, subtle mesh. Futuristic, premium feel.',
    ],
];
