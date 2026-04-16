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

];
