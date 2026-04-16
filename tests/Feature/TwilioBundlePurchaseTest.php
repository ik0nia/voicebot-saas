<?php

namespace Tests\Feature;

use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the Bundle + Address SID wiring on number provisioning.
 *
 * The Twilio SDK is expensive to mock fully (PHP SDK uses internal
 * resource builders) — we do it via partial mock on the Client,
 * asserting that `purchaseNumber('+40...')` forwards the RO Bundle
 * and Address SIDs we configured. Without them Twilio rejects RO
 * purchases with error 21649, so a regression that stops forwarding
 * them is a silent prod outage waiting to happen.
 */
class TwilioBundlePurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_ro_purchase_forwards_bundle_and_address_sids(): void
    {
        \App\Models\PlatformSetting::set('twilio_account_sid', 'ACmaster');
        \App\Models\PlatformSetting::set('twilio_auth_token', 'secret');
        \App\Models\PlatformSetting::set('twilio_ro_bundle_sid', 'BUtest123');
        \App\Models\PlatformSetting::set('twilio_ro_address_sid', 'ADtest123');

        // Swap the Client instance the service uses. The Twilio PHP SDK
        // exposes `incomingPhoneNumbers` as a magic property; a simple
        // stub with a `create` spy captures the options.
        $captured = null;
        $clientStub = new class($captured) {
            public $captured;
            public object $incomingPhoneNumbers;
            public function __construct(&$captured)
            {
                $this->captured = &$captured;
                $this->incomingPhoneNumbers = new class($captured) {
                    public $captured;
                    public function __construct(&$captured) { $this->captured = &$captured; }
                    public function create(array $opts): object
                    {
                        $this->captured = $opts;
                        return (object) [
                            'sid' => 'PN123abc',
                            'phoneNumber' => $opts['phoneNumber'],
                        ];
                    }
                };
            }
        };

        $svc = new class($clientStub) extends TwilioService {
            public function __construct(private $clientStub) {
                parent::__construct();
            }
            protected function client(): \Twilio\Rest\Client { return $this->clientStub; }
        };

        $result = $svc->purchaseNumber('+40731234567');

        $this->assertNotNull($result);
        $this->assertSame('BUtest123', $captured['bundleSid'] ?? null, 'Bundle SID was not forwarded');
        $this->assertSame('ADtest123', $captured['addressSid'] ?? null, 'Address SID was not forwarded');
        $this->assertSame('+40731234567', $captured['phoneNumber']);
    }

    public function test_us_purchase_skips_bundle_and_address(): void
    {
        // US numbers don't require a Regulatory Bundle — the service
        // must NOT forward a BU/AD SID on those or Twilio errors.
        \App\Models\PlatformSetting::set('twilio_account_sid', 'ACmaster');
        \App\Models\PlatformSetting::set('twilio_auth_token', 'secret');
        // Even if RO settings exist, a US number must ignore them.
        \App\Models\PlatformSetting::set('twilio_ro_bundle_sid', 'BUtest123');
        \App\Models\PlatformSetting::set('twilio_ro_address_sid', 'ADtest123');

        $captured = null;
        $clientStub = new class($captured) {
            public $captured;
            public object $incomingPhoneNumbers;
            public function __construct(&$captured)
            {
                $this->captured = &$captured;
                $this->incomingPhoneNumbers = new class($captured) {
                    public $captured;
                    public function __construct(&$captured) { $this->captured = &$captured; }
                    public function create(array $opts): object
                    {
                        $this->captured = $opts;
                        return (object) ['sid' => 'PNus', 'phoneNumber' => $opts['phoneNumber']];
                    }
                };
            }
        };

        $svc = new class($clientStub) extends TwilioService {
            public function __construct(private $clientStub) {
                parent::__construct();
            }
            protected function client(): \Twilio\Rest\Client { return $this->clientStub; }
        };

        $svc->purchaseNumber('+14155551234');

        $this->assertArrayNotHasKey('bundleSid', $captured);
        $this->assertArrayNotHasKey('addressSid', $captured);
    }
}
