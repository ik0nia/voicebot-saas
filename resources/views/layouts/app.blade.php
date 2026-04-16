<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'Sambla - Agenți AI multi-canal pentru afacerea ta. Automatizează comunicarea pe telefon, WhatsApp, Facebook, Instagram și agent AI web cu inteligență artificială.')">
    <title>@yield('title', 'Sambla - Agenți AI Multi-Canal pentru Afacerea Ta')</title>

    {{-- Favicon — SVG version of the Sambla logo, served from CDN --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.svg') }}">
    <meta name="theme-color" content="#991b1b">
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

    {{-- Marketing & analytics: Consent Mode v2 defaults + GTM must
         fire as early as possible, before any tag can attempt
         storage access. --}}
    @include('partials.analytics.head')
    @include('partials.analytics.flash-events')

    @yield('jsonld')

    {{-- Structured data: Organization + SoftwareApplication. Helps Google,
         ChatGPT, Claude, Perplexity, Gemini understand what Sambla is and
         answer user questions about us accurately. --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Organization",
                "@id": "https://sambla.ro/#organization",
                "name": "Sambla",
                "url": "https://sambla.ro",
                "logo": "https://cdn.sambla.ro/images/logo-icon.svg",
                "description": "Sambla este o platformă SaaS românească de agenți AI conversaționali (agent AI și agent AI vocal) pentru afaceri. Răspunde clienților 24/7 pe chat și telefon, în limba română, cu o voce naturală, folosind RAG (Retrieval-Augmented Generation) pentru a învăța din documentele și produsele tale reale, fără să inventeze.",
                "foundingDate": "2025",
                "areaServed": {"@type": "Country","name":"Romania"},
                "knowsLanguage": ["ro","en"],
                "email": "servus@sambla.ro",
                "telephone": "+40775222333",
                "sameAs": [
                    "https://facebook.com/sambla.ai",
                    "https://x.com/sambla_ai",
                    "https://linkedin.com/company/sambla-ai"
                ],
                "address": {
                    "@type": "PostalAddress",
                    "addressCountry": "RO"
                }
            },
            {
                "@type": "WebSite",
                "@id": "https://sambla.ro/#website",
                "url": "https://sambla.ro",
                "name": "Sambla — Agenți AI pentru afacerea ta",
                "publisher": {"@id": "https://sambla.ro/#organization"},
                "inLanguage": "ro-RO"
            },
            {
                "@type": "SoftwareApplication",
                "@id": "https://sambla.ro/#software",
                "name": "Sambla AI Platform",
                "applicationCategory": "BusinessApplication",
                "applicationSubCategory": "Conversational AI / Agent AI / Agent AI vocal",
                "operatingSystem": "Web (Cloud SaaS)",
                "url": "https://sambla.ro",
                "description": "Platformă SaaS care permite oricărei afaceri să creeze agenți AI conversaționali — agent AI pe site-ul propriu și agent AI vocal care preia apeluri telefonice — într-o oră, fără cunoștințe tehnice. AI-ul răspunde DOAR din documentele și produsele tale reale (anti-halucinare), învață singur din întrebările clienților și escaladează inteligent la operator uman când e nevoie.",
                "offers": [
                    {"@type":"Offer","name":"Plan Starter","priceCurrency":"EUR","price":"49","description":"Agent AI web pentru o afacere mică"},
                    {"@type":"Offer","name":"Plan Pro","priceCurrency":"EUR","price":"149","description":"Agent AI + agent AI vocal, multi-canal, integrare WooCommerce"},
                    {"@type":"Offer","name":"Plan Business","priceCurrency":"EUR","price":"399","description":"Volum mare, voce premium, multi-bot, suport dedicat"}
                ],
                "featureList": [
                    "Agent AI AI pe site cu RAG (răspunde din documentele tale)",
                    "Agent AI vocal cu voce naturală în română (preia apeluri telefonice 24/7)",
                    "Anti-halucinare: răspunde DOAR ce știe, nu inventează",
                    "RAG Pipeline cu Hybrid Search (vector + full-text + reranker)",
                    "Integrare nativă WooCommerce și WordPress",
                    "Multi-canal: web, telefon, WhatsApp, Facebook Messenger, Instagram",
                    "Captare lead-uri și pipeline CRM integrat",
                    "Programări automate cu reminder",
                    "Detectare frustrare în timp real și escaladare la operator",
                    "Dashboard analitice complete",
                    "Hosting 100% în România (GDPR compliant, date izolate per cont)"
                ],
                "softwareHelp": "https://sambla.ro/functionalitati",
                "potentialAction": {
                    "@type": "RegisterAction",
                    "target": "https://sambla.ro/register"
                },
                "audience": {
                    "@type": "BusinessAudience",
                    "audienceType": "IMM, e-commerce, servicii (cabinete medicale, avocați, contabili, service auto, saloane beauty, agenții imobiliare, restaurante, pensiuni, etc.)"
                },
                "inLanguage": "ro-RO",
                "countryOfOrigin": "Romania"
            }
        ]
    }
    </script>
</head>
<body class="bg-white min-h-screen flex flex-col">
    @include('partials.analytics.body')
    @include('components.navbar')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    {{-- GDPR cookie consent (Consent Mode v2 widget). Alpine + local
         persistence + server-side audit log. --}}
    @include('partials.analytics.consent-widget')

    {{-- Agent AI widget served from CDN. async + defer so it never blocks render. --}}
    <script src="{{ rtrim(config('app.cdn_url') ?: config('app.url'), '/') }}/widget/sambla-chat.min.js" data-channel-id="1" data-bot-name="Sambla" data-color="#991b1b" data-lang="ro" data-greeting="Salut! 👋 Sunt Sambla, asistentul virtual al platformei. Pot să îți povestesc cum funcționează agentul AI și agent AI vocal-ul nostru AI, sau să te ajut cu orice întrebare. Cu ce pot să te ajut?" async defer></script>

    @stack('scripts')
</body>
</html>
