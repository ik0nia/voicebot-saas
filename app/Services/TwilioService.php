<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Services\Telephony\TelephonyProvider;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

/**
 * Twilio implementation of TelephonyProvider. Built as the successor
 * to TelnyxService once Telnyx number approval started blocking
 * customer onboarding and contract issues made the migration
 * mandatory.
 *
 * The public interface matches TelephonyProvider so call-sites
 * (PhoneNumberController, CallApiController, webhook handlers) can
 * swap providers via config without code changes. Provider-specific
 * differences smoothed over here:
 *
 *  - Twilio provisioning is synchronous; getOrderStatus() always
 *    returns 'completed'. Telnyx surfaces a real pending/approved
 *    lifecycle. The interface keeps `pending` as a meaningful state
 *    because the platform UI still shows "Se activează" for Telnyx
 *    carry-overs.
 *  - Twilio has no native tags; updateNumberTags writes a joined
 *    string to friendly_name, which is Twilio's closest equivalent
 *    and renders in the Twilio console for debugging.
 */
class TwilioService implements TelephonyProvider
{
    protected ?Client $client = null;

    /**
     * Override credentials for this instance. Used by
     * TelephonyManager::forTenant() to return a TwilioService
     * authenticated as a tenant's subaccount. When both are null the
     * service uses master platform credentials from PlatformSetting /
     * config — the legacy single-account path.
     */
    public function __construct(
        protected ?string $overrideSid = null,
        protected ?string $overrideToken = null,
    ) {}

    public function name(): string
    {
        return 'twilio';
    }

    protected function client(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $sid = $this->overrideSid
            ?: (PlatformSetting::get('twilio_account_sid') ?: config('services.twilio.account_sid'));
        $token = $this->overrideToken
            ?: (PlatformSetting::get('twilio_auth_token') ?: config('services.twilio.auth_token'));

        if (empty($sid) || empty($token)) {
            throw new \RuntimeException('Twilio credentials not configured. Set them in Admin → Settings → Twilio.');
        }

        return $this->client = new Client($sid, $token);
    }

    /**
     * Master-account client — used for administrative operations that
     * can't run on a subaccount (subaccount creation itself, listing
     * all subaccounts for audit). Always authenticates with the
     * platform-level credentials, ignoring any overrides.
     */
    public function masterClient(): Client
    {
        $sid = PlatformSetting::get('twilio_account_sid') ?: config('services.twilio.account_sid');
        $token = PlatformSetting::get('twilio_auth_token') ?: config('services.twilio.auth_token');

        if (empty($sid) || empty($token)) {
            throw new \RuntimeException('Twilio master credentials not configured.');
        }
        return new Client($sid, $token);
    }

    /**
     * Ensure the given tenant has a Twilio subaccount, creating one on
     * first call. The returned array is suitable for populating
     * tenants.telephony_subaccount_{sid,auth_token}. Idempotent —
     * subsequent calls with populated columns return them unchanged
     * without touching the Twilio API.
     *
     * Shared Bundle / Address means the subaccount inherits
     * regulatory eligibility from the master account per Twilio's
     * "Subaccount Regulatory" feature — no document re-submission.
     *
     * @return array{sid: string, auth_token: string}
     */
    public function ensureSubaccount(\App\Models\Tenant $tenant): array
    {
        if ($tenant->telephony_subaccount_sid && $tenant->telephony_subaccount_auth_token) {
            return [
                'sid' => $tenant->telephony_subaccount_sid,
                'auth_token' => $tenant->telephony_subaccount_auth_token,
            ];
        }

        $friendlyName = substr("Sambla · {$tenant->name} (#{$tenant->id})", 0, 64);
        $sub = $this->masterClient()->api->v2010->accounts->create([
            'friendlyName' => $friendlyName,
        ]);

        $tenant->forceFill([
            'telephony_subaccount_sid' => $sub->sid,
            'telephony_subaccount_auth_token' => $sub->authToken,
        ])->save();

        Log::info('TwilioService: provisioned subaccount', [
            'tenant_id' => $tenant->id,
            'subaccount_sid' => $sub->sid,
        ]);

        return ['sid' => $sub->sid, 'auth_token' => $sub->authToken];
    }

    /**
     * Toggle a subaccount's status. 'suspended' stops calls +
     * provisioning instantly; 'active' resumes. Used by the tenant
     * billing-default flow and the admin "pause tenant" action.
     *
     * 'closed' is one-way — Twilio releases every number on the
     * subaccount and it's gone. Only call on tenant hard-delete.
     */
    public function setSubaccountStatus(\App\Models\Tenant $tenant, string $status): bool
    {
        if (!in_array($status, ['active', 'suspended', 'closed'], true)) {
            throw new \InvalidArgumentException("Invalid subaccount status: {$status}");
        }
        if (!$tenant->telephony_subaccount_sid) {
            return false;
        }
        try {
            $this->masterClient()
                ->api->v2010->accounts($tenant->telephony_subaccount_sid)
                ->update(['status' => $status]);
            Log::info('TwilioService: subaccount status changed', [
                'tenant_id' => $tenant->id,
                'status' => $status,
            ]);
            if ($status === 'closed') {
                $tenant->forceFill([
                    'telephony_subaccount_sid' => null,
                    'telephony_subaccount_auth_token' => null,
                ])->save();
            }
            return true;
        } catch (RestException $e) {
            Log::error('TwilioService: setSubaccountStatus failed', [
                'tenant_id' => $tenant->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function makeCall(string $to, string $from, string $webhookUrl): object
    {
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
            throw new \InvalidArgumentException("Invalid E.164 phone number: {$to}");
        }
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $from)) {
            throw new \InvalidArgumentException("Invalid E.164 caller number: {$from}");
        }

        try {
            $call = $this->client()->calls->create($to, $from, [
                'url' => $webhookUrl,
                'method' => 'POST',
            ]);

            // Shape the response to match the TelephonyProvider contract's
            // "has ->id" guarantee. Telnyx returns a uuid; Twilio returns
            // the CallSid (CAxxxx…).
            return (object) [
                'id' => $call->sid,
                'status' => $call->status,
                'from' => $call->from,
                'to' => $call->to,
            ];
        } catch (RestException $e) {
            throw new \RuntimeException('Twilio makeCall failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getAvailableNumbers(string $country = 'RO', string $type = 'local', int $limit = 10): array
    {
        try {
            $accessor = $this->client()->availablePhoneNumbers($country);
            $list = match ($type) {
                'mobile' => $accessor->mobile,
                'tollFree', 'toll_free' => $accessor->tollFree,
                default => $accessor->local,
            };

            $numbers = $list->read([], $limit);

            return array_map(function ($n) {
                // Twilio SDK returns PhoneNumberCapabilities as an object
                // with getters, not an array. The same "capabilities" key
                // cohabits as an array on other SDK response types, which
                // makes it easy to reach for $n->capabilities['voice']
                // and get a runtime TypeError. Route through the getters.
                $caps = $n->capabilities;
                return [
                    'number' => $n->phoneNumber,
                    'friendly_name' => $n->friendlyName ?: $n->phoneNumber,
                    'capabilities' => [
                        'voice' => is_object($caps) ? (bool) $caps->getVoice() : (bool) ($caps['voice'] ?? false),
                        'sms' => is_object($caps) ? (bool) $caps->getSms() : (bool) ($caps['SMS'] ?? false),
                    ],
                    'region' => array_filter([
                        'region_name' => $n->region ?: null,
                        'locality' => $n->locality ?: null,
                        'iso_country' => $n->isoCountry ?: null,
                    ]),
                    'monthly_cost' => 1.00,
                ];
            }, $numbers);
        } catch (RestException $e) {
            Log::warning('TwilioService: getAvailableNumbers failed', [
                'country' => $country,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function purchaseNumber(string $phoneNumber): ?object
    {
        try {
            // Regulatory bundle + address are required for RO (and most
            // non-US countries). Twilio rejects the purchase with
            // error 21649 ("RegulatoryBundleSid is required") without
            // them. We infer country from the number prefix and look
            // up the configured SIDs; US numbers go through without
            // bundle (not required).
            $country = $this->countryFromNumber($phoneNumber);
            $options = [
                'phoneNumber' => $phoneNumber,
            ];

            $bundleSid = $country
                ? (PlatformSetting::get("twilio_{$country}_bundle_sid")
                    ?: config("services.twilio.bundle_sid_{$country}"))
                : null;
            $addressSid = $country
                ? (PlatformSetting::get("twilio_{$country}_address_sid")
                    ?: config("services.twilio.address_sid_{$country}"))
                : null;

            if ($bundleSid) {
                $options['bundleSid'] = $bundleSid;
            }
            if ($addressSid) {
                $options['addressSid'] = $addressSid;
            }

            $number = $this->client()->incomingPhoneNumbers->create($options);

            return (object) [
                'id' => $number->sid,
                'phone_number' => $number->phoneNumber,
                'status' => 'completed',
            ];
        } catch (RestException $e) {
            Log::error('TwilioService: purchaseNumber failed', [
                'number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Best-effort country code from a phone number. E.164 numbers start
     * with a country calling code, but Twilio Bundle keys use ISO 3166
     * country codes (RO, DE, FR…). Map the handful of countries we
     * actually support; extend as needed.
     */
    private function countryFromNumber(string $phoneNumber): ?string
    {
        $prefixes = [
            '+40' => 'ro',
            '+44' => 'gb',
            '+49' => 'de',
            '+33' => 'fr',
            '+34' => 'es',
            '+39' => 'it',
            '+31' => 'nl',
            // US numbers don't require a Bundle — returning null skips
            // bundle/address lookup entirely.
            '+1' => null,
        ];
        foreach ($prefixes as $prefix => $iso) {
            if (str_starts_with($phoneNumber, $prefix)) {
                return $iso;
            }
        }
        return null;
    }

    public function releaseNumber(string $externalId): bool
    {
        try {
            $this->client()->incomingPhoneNumbers($externalId)->delete();
            return true;
        } catch (RestException $e) {
            Log::error('TwilioService: releaseNumber failed', [
                'id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function updateNumberTags(string $phoneNumber, array $tags): bool
    {
        // Twilio has no tag concept — the closest equivalent is
        // friendly_name, which shows up in the console and is searchable.
        // Join tags so both identifying pieces (tenant / bot) land there.
        try {
            $results = $this->client()->incomingPhoneNumbers->read(['phoneNumber' => $phoneNumber], 1);
            if (empty($results)) {
                Log::warning('TwilioService: number not found for tagging', ['number' => $phoneNumber]);
                return false;
            }

            $sid = $results[0]->sid;
            $this->client()->incomingPhoneNumbers($sid)->update([
                'friendlyName' => implode(' · ', array_map('strval', $tags)),
            ]);
            return true;
        } catch (RestException $e) {
            Log::warning('TwilioService: updateNumberTags failed', [
                'number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getNumberStatus(string $phoneNumber): string
    {
        try {
            $results = $this->client()->incomingPhoneNumbers->read(['phoneNumber' => $phoneNumber], 1);
            return empty($results) ? 'not_found' : 'active';
        } catch (RestException $e) {
            Log::warning('TwilioService: getNumberStatus failed', [
                'number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return 'not_found';
        }
    }

    public function getOrderStatus(string $orderId): ?string
    {
        // Twilio provisioning is synchronous — there is no async order.
        // Returning 'completed' keeps the UI happy for code paths that
        // were written against Telnyx's pending lifecycle.
        return 'completed';
    }

    public function generateMediaStreamTexml(string $botId, string $callId): string
    {
        // The media-stream bridge lives on its own subdomain so the cert
        // / sticky-session policy is independent of the main app. Fall
        // back to the legacy path-style URL only if explicitly
        // overridden via config (useful for local dev without DNS).
        $host = config('telephony.media_stream_host', 'ms.sambla.ro');
        $wsUrl = "wss://{$host}/ws/media-stream";

        // TwiML grammar — same shape as Telnyx TeXML with one important
        // difference: Twilio's <Stream> uses `<Parameter>` nested inside
        // `<Stream>` (not `<Connect>` like Telnyx), and the attribute is
        // `url`, not `track`. The media-stream bridge reads bot_id /
        // call_id from the custom parameters on the first message.
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Response>';
        $xml .= '<Say language="ro-RO">Bună ziua! Vă conectăm cu asistentul nostru virtual.</Say>';
        $xml .= '<Connect>';
        $xml .= '<Stream url="' . htmlspecialchars($wsUrl, ENT_XML1) . '">';
        $xml .= '<Parameter name="bot_id" value="' . htmlspecialchars($botId, ENT_XML1) . '"/>';
        $xml .= '<Parameter name="call_id" value="' . htmlspecialchars($callId, ENT_XML1) . '"/>';
        $xml .= '</Stream>';
        $xml .= '</Connect>';
        $xml .= '</Response>';

        return $xml;
    }
}
