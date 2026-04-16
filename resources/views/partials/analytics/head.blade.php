{{--
  Analytics <head> — Consent Mode v2 default + GTM snippet.
  Include this ONCE, as early in <head> as possible (before any
  other scripts) so the consent defaults fire before any tag can
  read storage. Values resolve from PlatformSetting at runtime.
--}}
@php
    $cfg = app(\App\Services\Analytics\AnalyticsConfig::class);
    $gtmId = $cfg->gtmId();
    $consentEnabled = (bool) $cfg->get('consent_enabled', true);
    $defaults = $cfg->consentDefaults();
    $sgtmUrl = $cfg->sgtmUrl();
@endphp
@if($cfg->isEnabled() && $gtmId)
    {{-- Consent Mode v2 — must fire BEFORE GTM so every tag
         loaded by GTM gets the correct default signal state.
         `wait_for_update` gives the banner 500ms to read
         previously-saved consent and call gtag('consent','update')
         before tags resolve storage access. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        // Restore previously-saved consent (if any) before defaults
        // so a returning visitor doesn't see the banner re-prompt.
        (function() {
            try {
                var saved = localStorage.getItem('sambla_consent');
                if (saved) {
                    var parsed = JSON.parse(saved);
                    // Only honor saved consent if version matches
                    if (parsed && parsed.v === '{{ config('analytics.consent_version', '1.0') }}' && parsed.signals) {
                        gtag('consent', 'default', Object.assign(
                            @json((object) $defaults, JSON_UNESCAPED_UNICODE),
                            parsed.signals,
                            { wait_for_update: 500 }
                        ));
                        window.__samblaConsentKnown = true;
                        return;
                    }
                }
            } catch (e) {}

            gtag('consent', 'default', Object.assign(
                @json((object) $defaults, JSON_UNESCAPED_UNICODE),
                { wait_for_update: 500 }
            ));
            window.__samblaConsentKnown = false;
        })();

        // Ads data redaction + URL passthrough — safe defaults per
        // Google's guidance for consent-gated ads tags.
        gtag('set', 'ads_data_redaction', true);
        gtag('set', 'url_passthrough', true);
    </script>

    {{-- Google Tag Manager --}}
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
        j.src='{{ $sgtmUrl ? $sgtmUrl.'/gtm.js' : 'https://www.googletagmanager.com/gtm.js' }}?id='+i+dl;
        f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');
    </script>
    {{-- End GTM --}}

    {{-- User identity + global dimensions — fired early so page_view
         tags have them available. Server-side templates inject the
         authenticated user's ID (hashed) + role for User-ID tracking. --}}
    @auth
        <script>
            dataLayer.push({
                user_id: '{{ hash('sha256', (string) auth()->id()) }}',
                user_role: '{{ auth()->user()?->roles?->pluck('name')->first() ?? 'user' }}',
                @if(auth()->user()?->tenant_id)
                tenant_id: '{{ auth()->user()->tenant_id }}',
                @endif
            });
        </script>
    @endauth
@endif
