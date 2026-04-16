{{--
  Drains AnalyticsTracker's server-side event queue and emits
  dataLayer pushes. Include once per layout, AFTER the GTM snippet
  (so window.dataLayer exists) — which it does: head.blade.php
  initialises dataLayer in the same pass, before body renders.
--}}
@php
    $cfg = app(\App\Services\Analytics\AnalyticsConfig::class);
    $events = app(\App\Services\Analytics\AnalyticsTracker::class)->collect();
@endphp
@if($cfg->isEnabled() && !empty($events))
    <script>
        window.dataLayer = window.dataLayer || [];
        @foreach($events as $e)
            window.dataLayer.push(Object.assign(
                { event: {!! json_encode($e['event']) !!} },
                {!! json_encode($e['params'] ?? (object) [], JSON_UNESCAPED_UNICODE) !!}
            ));
        @endforeach
    </script>
@endif
