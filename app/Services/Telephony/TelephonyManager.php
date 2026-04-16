<?php

namespace App\Services\Telephony;

use App\Models\PhoneNumber;
use App\Services\TelnyxService;
use App\Services\TwilioService;

/**
 * Resolves which concrete TelephonyProvider handles a request. Two
 * resolution points:
 *
 *  1. By default, the provider set in `config('telephony.default')`
 *     (env `TELEPHONY_DEFAULT_PROVIDER`) — used for outbound calls and
 *     new number provisioning.
 *  2. By PhoneNumber — outbound calls to an existing number MUST use
 *     the provider that owns the number (column `phone_numbers.provider`),
 *     otherwise the call fails at the provider API. Same for webhooks:
 *     the webhook URL is registered against the owning provider.
 *
 * During the Telnyx → Twilio migration, default is 'twilio' for new
 * numbers; existing Telnyx numbers keep working until they're cut over.
 */
class TelephonyManager
{
    public function __construct(
        private TelnyxService $telnyx,
        private TwilioService $twilio,
    ) {}

    public function default(): TelephonyProvider
    {
        return $this->for(config('telephony.default', 'twilio'));
    }

    public function for(string $name): TelephonyProvider
    {
        return match ($name) {
            'telnyx' => $this->telnyx,
            'twilio' => $this->twilio,
            default => throw new \InvalidArgumentException("Unknown telephony provider: {$name}"),
        };
    }

    public function forNumber(PhoneNumber $number): TelephonyProvider
    {
        // provider column is 'telnyx' | 'twilio' | 'manual'. 'manual'
        // means the number was added without an API integration and has
        // no API-side operations; we fall back to default for any call
        // attempt, but mutations should 404 at the controller.
        $name = $number->provider ?: config('telephony.default', 'twilio');
        if ($name === 'manual') {
            return $this->default();
        }
        return $this->for($name);
    }
}
