<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla - Agenți AI multi-canal pentru afacerea ta. Automatizează comunicarea pe telefon, WhatsApp, Facebook, Instagram și chatbot web cu inteligență artificială.')">
    <title>@yield('title', 'Sambla - Agenți AI Multi-Canal pentru Afacerea Ta')</title>
    {{-- Open early TCP+TLS connection to the CDN so the first asset request lands fast. --}}
    <link rel="preconnect" href="https://cdn.sambla.ro" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.sambla.ro">

    {{-- Self-hosted Inter VARIABLE FONT — one woff2 per subset contains
         all weights (100-900). Romanian visitors fetch only ~85KB
         (latin-ext) for the entire font family, vs the 338KB / 16-request
         pull from fonts.bunny.net we used to do. --}}
    <link rel="preload" as="font" type="font/woff2"
          href="https://cdn.sambla.ro/fonts/inter/inter-latin-ext-variable.woff2" crossorigin>
    <link href="https://cdn.sambla.ro/fonts/inter/inter.css" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-white min-h-screen flex flex-col">
    @include('components.navbar')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    {{-- Chatbot widget served from CDN. async + defer so it never blocks render. --}}
    <script src="{{ rtrim(config('app.cdn_url') ?: config('app.url'), '/') }}/widget/sambla-chat.min.js" data-channel-id="1" data-bot-name="Sambla" data-color="#991b1b" data-lang="ro" data-greeting="Salut! 👋 Sunt Sambla, asistentul virtual al platformei. Pot să îți povestesc cum funcționează chatbot-ul și voicebot-ul nostru AI, sau să te ajut cu orice întrebare. Cu ce pot să te ajut?" async defer></script>

    @stack('scripts')
</body>
</html>
