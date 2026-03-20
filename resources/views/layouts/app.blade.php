<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'VoiceBot SaaS - Agenți vocali AI pentru afacerea ta. Automatizează apelurile telefonice cu inteligență artificială.')">
    <title>@yield('title', 'VoiceBot SaaS - Agenți Vocali AI pentru Afacerea Ta')</title>
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
    @stack('scripts')
</body>
</html>
