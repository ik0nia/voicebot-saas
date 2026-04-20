<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Contul tău Sambla — agenți AI pentru afaceri românești.')">
    <title>@yield('title', 'Sambla')</title>
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.svg') }}">
    <meta name="theme-color" content="#DC2626">
    <meta name="robots" content="noindex, follow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CDN — aceeași configurație ca în layouts/new ca să
         potrivească 1:1 vizualul warm. Urmează să fie înlocuit de
         build-ul real odată cu /new/*. --}}
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans:    ['Inter','system-ui','sans-serif'],
                    display: ['Instrument Sans','Inter','sans-serif'],
                },
                colors: {
                    cream:'#F5F1E8', paper:'#FAF7EF', sand:'#EFE5D0', sandy:'#E5DCC4',
                    ink:'#1C1917', inkSoft:'#3A3532', muted:'#78716C', line:'#E7E0CE',
                    coral:'#DC2626', coralh:'#991B1B', coralsoft:'#FEE2E2',
                    sun:'#F2E59A',
                }
            }
        }
    }
    </script>

    <style>
        :root { --accent: #DC2626; --accent-soft: #FEE2E2; --accent-dark: #991B1B; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; }
        .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
        .accent-text { color: var(--accent); }
        .accent-bg   { background: var(--accent); }
        .accent-soft-bg { background: var(--accent-soft); }

        .btn-primary { background: var(--accent); color: #fff; border-radius: 999px; padding: 12px 24px;
            font-weight: 600; transition: all .2s ease; display: inline-flex; align-items: center;
            justify-content: center; gap: 8px; text-decoration: none; }
        .btn-primary:hover { background: var(--accent-dark); transform: translateY(-1px);
            box-shadow: 0 10px 30px color-mix(in srgb, var(--accent) 30%, transparent); }

        /* Auth card warm layered look */
        .auth-bg {
            background:
              radial-gradient(ellipse 45% 35% at 20% 15%, rgba(220,38,38,0.15) 0%, transparent 60%),
              radial-gradient(ellipse 40% 30% at 85% 80%, rgba(242,229,154,0.35) 0%, transparent 60%),
              #F5F1E8;
            min-height: 100vh;
        }

        .field-input {
            width: 100%; padding: 12px 16px; border-radius: 12px;
            border: 1px solid #E7E0CE; background: #FFF; color: #1C1917;
            font-size: 14px; transition: all .2s ease;
        }
        .field-input::placeholder { color: #A8A29E; }
        .field-input:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
        }
    </style>

    {{-- Analytics identice cu layouts/app — consent, GTM, flash events. --}}
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')

    @stack('styles')
</head>
<body class="antialiased auth-bg flex flex-col">
    @include('partials.analytics.body')

    {{-- Header minimal — logo spre home /new, link „înapoi la site" --}}
    <header class="px-6 py-5 flex items-center justify-between max-w-7xl mx-auto w-full">
        <a href="{{ url('/new') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('images/logo-light.svg') }}" alt="Sambla" class="h-9 w-auto">
        </a>
        <a href="{{ url('/new') }}" class="text-sm font-medium text-muted hover:text-ink transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 12H5m0 0l7-7m-7 7l7 7"/></svg>
            Înapoi la site
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-8">
        @yield('content')
    </main>

    <footer class="px-6 py-6 text-center text-xs" style="color: var(--muted);">
        © {{ date('Y') }} Sambla · Oradea, România ·
        <a href="{{ route('new.legal.termeni') }}" class="hover:text-ink underline">Termeni</a> ·
        <a href="{{ route('new.legal.confidentialitate') }}" class="hover:text-ink underline">Confidențialitate</a>
    </footer>

    @include('partials.analytics.consent-widget')
</body>
</html>
