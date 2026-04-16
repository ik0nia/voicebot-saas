{{--
  GTM <body> noscript fallback — include directly after the opening
  <body> tag. Required by Google so JS-disabled sessions still hit
  the container with a pageview.
--}}
@php
    $cfg = app(\App\Services\Analytics\AnalyticsConfig::class);
    $gtmId = $cfg->gtmId();
    $sgtmUrl = $cfg->sgtmUrl();
@endphp
@if($cfg->isEnabled() && $gtmId)
    <noscript>
        <iframe src="{{ $sgtmUrl ? $sgtmUrl.'/ns.html' : 'https://www.googletagmanager.com/ns.html' }}?id={{ $gtmId }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif
