<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla - Agenți AI multi-canal pentru afacerea ta. Automatizează comunicarea pe telefon, WhatsApp, Facebook, Instagram și chatbot web cu inteligență artificială.')">
    <title>@yield('title', 'Sambla - Agenți AI Multi-Canal pentru Afacerea Ta')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-white min-h-screen flex flex-col">
    @include('components.navbar')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    {{-- Chatbot widget: Sambla - Asistent Site (bot #65, channel #1) --}}
    <script src="{{ rtrim(config('app.url'), '/') }}/widget/sambla-chat.min.js" data-channel-id="1" data-bot-name="Sambla" data-color="#991b1b" data-lang="ro" data-greeting="Salut! 👋 Sunt Sambla, asistentul virtual al platformei. Pot să îți povestesc cum funcționează chatbot-ul și voicebot-ul nostru AI, sau să te ajut cu orice întrebare. Cu ce pot să te ajut?" async defer></script>

    @stack('scripts')
</body>
</html>
