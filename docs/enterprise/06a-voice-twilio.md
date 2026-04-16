# Voice — Twilio Integration

## TL;DR

Twilio is the primary telephony provider as of the 2026-04 migration
off Telnyx. Inbound traffic lands at `POST /webhook/twilio/voice`, the
controller resolves the dialed number to a `PhoneNumber` → `Bot`,
creates a `Call` row, and returns TwiML with `<Connect><Stream>` that
bridges audio into `wss://{host}/ws/media-stream`. Call-progress status
posts to `/webhook/twilio/status` and drive a state machine on the
`Call` row. Number provisioning is synchronous via
`TwilioService::purchaseNumber()`; unlike Telnyx there is no async
order approval lifecycle — numbers go live the moment the API returns
2xx. Both webhook routes are guarded by `VerifyTwilioSignature`
(HMAC-SHA1 over URL + sorted POST params keyed with the Auth Token).

Inbound calls reach the media-stream bridge at
`services/media-stream/` — a Node.js service deployed as a separate
container. It answers the WebSocket at `wss://ms.sambla.ro/ws/media-stream`,
resolves the bot context from Postgres/Redis via the custom
parameters Twilio sends on the `start` event, opens a parallel WS to
OpenAI Realtime, transcodes audio (mulaw 8k ↔ PCM16 24k), and handles
barge-in / DTMF / call teardown. See
`services/media-stream/README.md` for the deployment model, env vars,
and observability hooks. **Follow-up work**: transcript persistence
back into the `transcripts` table, cost tracking per
`response.done.usage` → `credit_transactions`, DTMF-to-agent
plumbing.

Relevant files:

- `app/Services/TwilioService.php` — `TelephonyProvider` implementation
- `app/Http/Controllers/Webhook/TwilioWebhookController.php`
- `app/Http/Middleware/VerifyTwilioSignature.php`
- `app/Services/Telephony/TelephonyProvider.php` — contract both providers implement
- `app/Services/Telephony/TelephonyManager.php` — routes operations to the right provider
- `config/telephony.php` — default provider selection
- `config/services.php` — Twilio credentials (`services.twilio.{account_sid,auth_token,twiml_app_sid}`)

## Provider abstraction

Everything tenant-facing goes through `TelephonyProvider`. The four
call-sites (`PhoneNumberController`, `CallApiController`, the two
webhook controllers) never instantiate a concrete client directly.
That keeps the next provider swap to one new class + one config flag.

```php
// Outbound: call routed through whichever provider owns the number.
$telephony = app(TelephonyManager::class);
$provider  = $telephony->forNumber($phoneNumber);
$providerCall = $provider->makeCall($to, $phoneNumber->number, $webhookUrl);

// Default provider for new numbers.
$provider = app(TelephonyManager::class)->default();  // config('telephony.default') → 'twilio'
```

`PhoneNumber.provider` is the single source of truth for which provider
owns a number (`telnyx`, `twilio`, or `manual` for numbers added
without an API integration). `TelephonyManager::forNumber()` reads
this column; the webhook URL and TwiML/TeXML grammar are selected at
send time, so a mixed inventory works without per-call configuration.

## Signature verification

Twilio signs every webhook with the Auth Token. The algorithm:

1. Take the full absolute URL Twilio called (respect `X-Forwarded-*`;
   already configured in `bootstrap/app.php`).
2. Append POST parameters in alphabetical order by key, concatenated
   as `key + value` with no separator. Query params stay in the URL
   and are not re-added.
3. `base64(HMAC-SHA1(data, auth_token))` — compare to `X-Twilio-Signature`.

Fail-closed behaviour mirrors the Meta / Telnyx middlewares:

| Condition | Status |
|---|---|
| `TWILIO_AUTH_TOKEN` or platform setting missing | **503** (don't pretend to verify) |
| `X-Twilio-Signature` header absent | **401** |
| Signature present but invalid | **403** |
| Signature valid | pass through |

Unlike Telnyx (which signs a timestamp and gives us a ±5 min replay
window — iter 10), Twilio has no timestamp in the signature. Replay
protection here has to be per-event idempotency on `CallSid`, mirroring
the pattern iter 11 established for Meta webhook `mid`/`wamid`.
**Not yet implemented** — planned for the post-migration hardening pass.

## Admin configuration

`Admin → Settings → Twilio`:

| Key (PlatformSetting) | Required | Notes |
|---|---|---|
| `twilio_account_sid` | ✅ | `ACxxxxxxxx…` |
| `twilio_auth_token` | ✅ | Used for API + signature verification |
| `twilio_twiml_app_sid` | optional | For Client SDK / browser calling, not used today |
| `twilio_webhook_url` | optional | Display-only hint; routes are fixed |

Values are read via `PlatformSetting::get(...)` first, falling back to
`config('services.twilio.{account_sid,auth_token,twiml_app_sid}')` so a
reseller install without DB can still configure via `.env`.

## Migration cutover — per tenant

1. `phone_numbers.provider` defaults to `twilio` for new rows
   (`config('telephony.default')`). Existing Telnyx numbers keep their
   existing value; `TelephonyManager::forNumber()` routes per-row.
2. To move a tenant onto Twilio: provision a new Twilio number, map
   `phoneNumber.bot_id` to the same bot, update DNS / Google My Business
   on the customer side, release the Telnyx number.
3. Port-out (keep the same number, move carrier) is a 2–4 week process
   through Twilio's Hosted Number or Porting Center. Required if the
   customer has the number printed on marketing collateral.
4. After the last Telnyx number is released, `TelnyxService` and the
   Telnyx webhook routes can be deleted in a cleanup iter.

## Call cost model

Per-minute cost is computed on `call.completed` status and stored in
`calls.cost_cents` (same as the Telnyx flow — the billing layer doesn't
care which provider sourced the call).

## Follow-up work

- **Media-stream WebSocket bridge** — the blocker for real voice. See
  ROADMAP. Node runtime, stateless design (Redis lookup by
  `streamSid`), horizontally scalable behind Traefik sticky sessions.
- **CallSid idempotency** on inbound webhooks — replay defense.
- **Call-progress state machine port** — Telnyx controller has a
  valid-transition table; Twilio controller currently does a best-effort
  normalise. Port the table once we see real traffic.
- **Twilio Tests** — iter 10 / 16 gave Telnyx full signature + replay
  coverage; Twilio should match before the last Telnyx number is cut.
