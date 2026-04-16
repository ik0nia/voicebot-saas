<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default telephony provider
    |--------------------------------------------------------------------------
    |
    | Which provider the TelephonyManager returns from default() and which
    | one gets used when a new number is provisioned. During the Telnyx →
    | Twilio migration this is 'twilio' for new accounts; existing Telnyx
    | numbers are routed to the Telnyx provider via TelephonyManager::
    | forNumber() regardless of this setting, so the cutover is gradual.
    |
    | Supported: "telnyx", "twilio"
    */

    'default' => env('TELEPHONY_DEFAULT_PROVIDER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Media-stream bridge host
    |--------------------------------------------------------------------------
    |
    | Hostname used in the TwiML <Connect><Stream url="wss://{host}/ws/..."/>
    | response. Default is the dedicated subdomain the bridge lives on; a
    | local dev can override via MEDIA_STREAM_HOST to point at ngrok.
    */

    'media_stream_host' => env('MEDIA_STREAM_HOST', 'ms.sambla.ro'),

];
