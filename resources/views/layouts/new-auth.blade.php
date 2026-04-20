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

    {{-- Tailwind build real — aceeași stylesheet ca layouts/new, deci
         vizualul rămâne identic fără CDN. --}}
    @vite('resources/css/new.css')

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
