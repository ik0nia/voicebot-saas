import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Instrument Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                mono:    ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Legacy site palette (keep as-is)
                primary: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: '#dc2626',
                    600: '#b91c1c',
                    700: '#991b1b',
                    800: '#7f1d1d',
                    900: '#450a0a',
                },

                // /new warm palette — identic cu blocul inline din
                // layouts/new ca să dea același rezultat după build.
                cream:     '#F5F1E8',
                paper:     '#FAF7EF',
                sand:      '#EFE5D0',
                sandy:     '#E5DCC4',
                ink:       '#1C1917',
                inkSoft:   '#3A3532',
                muted:     '#78716C',
                line:      '#E7E0CE',
                coral:     '#DC2626',
                coralh:    '#991B1B',
                coralsoft: '#FEE2E2',
                peach:     '#FDBA8C',
                sun:       '#F2E59A',
                lilac:     '#C7B8E8',
                sky:       '#A7C7F0',
            },
        },
    },
    // Safelist pentru clase construite dinamic (slug niche, preț) care
    // altfel ar fi purgate la build.
    safelist: [
        'bg-paper', 'bg-cream', 'bg-sand', 'bg-ink',
        'text-ink', 'text-muted', 'text-cream',
        'border-line',
        'bg-[var(--accent)]',
        'text-[var(--accent)]',
        'border-[var(--accent)]',
        'ring-[var(--accent)]',
    ],
    plugins: [],
};
