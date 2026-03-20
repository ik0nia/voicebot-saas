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
    {{-- Subtle Romanian traditional motif strip under navbar (Ardeal/Bihor inspired) --}}
    @include('components.navbar')
    <div class="fixed top-16 lg:top-20 left-0 right-0 z-40 h-[3px] overflow-hidden">
        <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 1200 3" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="nav-motif" x="0" y="0" width="12" height="3" patternUnits="userSpaceOnUse">
                    <rect x="0" y="0" width="3" height="3" fill="#991b1b" opacity="0.7"/>
                    <rect x="3" y="0" width="3" height="3" fill="#b91c1c" opacity="0.4"/>
                    <rect x="6" y="0" width="3" height="3" fill="#991b1b" opacity="0.7"/>
                    <rect x="9" y="0" width="3" height="3" fill="#fecaca" opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="1200" height="3" fill="url(#nav-motif)"/>
        </svg>
    </div>

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')
    @stack('scripts')
</body>
</html>
