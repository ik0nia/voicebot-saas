# Messaging Channels (WhatsApp, Facebook, Instagram)

## TL;DR

Sambla ingests inbound messages from WhatsApp Cloud API, Facebook Messenger, and
Instagram DM through three near-identical Meta webhook endpoints
(`/webhook/whatsapp`, `/webhook/facebook`, `/webhook/instagram`). All three share
the same shape: a `GET` handler for Meta's `hub.challenge` verification, and a
`POST` handler that validates `X-Hub-Signature-256` (HMAC-SHA256 over the raw
body with `META_APP_SECRET`), resolves the target `Channel` by `external_id`
(WhatsApp phone number ID, Facebook page ID, Instagram account ID), and
dispatches a `ProcessChannelMessage` queue job per text message. The job runs
`ChannelMessageService::processIncomingMessage`, which persists the
`Conversation` + inbound/outbound `Message` pair, runs the full AI orchestrator
pipeline (intent routing, knowledge search, strategy engine, model routing),
and auto-extracts leads once the conversation reaches 3+ messages.

Outbound (platform → user) messaging is **partial**: `ChannelMessagingService`
can post to `graph.facebook.com` for all three surfaces, but the inbound path
currently *does not* call it — the AI reply is persisted as an outbound
`Message` row, but no Graph API call is made to actually deliver it back to
Meta. Wire-up is a known gap.

Key source files:

- `app/Http/Controllers/Webhook/WhatsAppWebhookController.php`
- `app/Http/Controllers/Webhook/FacebookWebhookController.php`
- `app/Http/Controllers/Webhook/InstagramWebhookController.php`
- `app/Http/Middleware/VerifyMetaWebhookSignature.php`
- `app/Jobs/ProcessChannelMessage.php`
- `app/Services/ChannelMessageService.php` (processing + lead extract)
- `app/Services/ChannelMessagingService.php` (outbound Graph API client)
- `app/Models/Channel.php`
- `routes/web.php` (lines 458–483)

## Inbound flow

```mermaid
sequenceDiagram
    autonumber
    participant Meta as Meta (WA / FB / IG)
    participant Nginx
    participant MW as VerifyMetaWebhookSignature
    participant Ctl as Channel webhook controller
    participant Q as Redis queue
    participant Job as ProcessChannelMessage
    participant Svc as ChannelMessageService
    participant AI as Orchestrator + ChatCompletion
    participant DB as Postgres
    participant Graph as graph.facebook.com

    Meta->>Nginx: POST /webhook/{whatsapp|facebook|instagram}<br/>X-Hub-Signature-256
    Nginx->>MW: route (csrf skipped)
    MW->>MW: hash_hmac('sha256', raw body, META_APP_SECRET)
    alt signature valid
        MW->>Ctl: handle(Request)
    else invalid / empty secret
        MW-->>Meta: 403 / (log + pass-through if secret empty)
    end
    Ctl->>DB: Channel::where('external_id', ...)->active()->first()
    Ctl->>Q: ProcessChannelMessage::dispatch(channelId, contactId, name, text)
    Ctl-->>Meta: 200 OK (always)
    Q->>Job: handle(ChannelMessageService)
    Job->>Svc: processIncomingMessage(channel, contactId, name, text)
    Svc->>DB: Conversation::firstOrCreate + inbound Message
    Svc->>AI: IntentOrchestrator::plan/execute + ChatCompletionService
    AI-->>Svc: AI reply text
    Svc->>DB: outbound Message + update counters + lead auto-extract
    Svc-..>Graph: (gap) ChannelMessagingService::send NOT called today
```

## Signature verification (HMAC-SHA256)

All three webhooks attach `App\Http\Middleware\VerifyMetaWebhookSignature` on
the `POST` route (CSRF is disabled on the group). The middleware:

1. Short-circuits for `GET` (Meta's `hub.challenge` handshake).
2. Rejects with `401` if the `X-Hub-Signature-256` header is missing.
3. Computes `'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret)`
   over the **raw request body** (`$request->getContent()`, not the parsed
   array) and compares with `hash_equals` (constant-time).
4. Returns `403 Invalid signature` on mismatch.

Each controller **also** re-runs the same HMAC check inside `handle()` as a
belt-and-braces defence — useful if the middleware is ever swapped out.

### Fallback when `META_APP_SECRET` is empty — **warning**

`VerifyMetaWebhookSignature.php:31-35` contains a backward-compatibility
fallback:

```php
$appSecret = config('services.meta.app_secret', env('META_APP_SECRET'));
if (empty($appSecret)) {
    Log::error('VerifyMetaWebhookSignature: META_APP_SECRET not configured');
    return $next($request);   // <-- passes unsigned request through
}
```

**If `META_APP_SECRET` is not set in the environment, the middleware logs an
error and forwards the request.** The in-controller re-check has the same
behaviour (it wraps the HMAC comparison in `if ($appSecret) { ... }`). This is
acceptable for dev bootstrapping but is a **critical hardening gap in
production** — anyone who can reach `/webhook/whatsapp` (or the other two) can
inject inbound messages and trigger AI replies, lead creation, and message
quota usage.

Also note: `config/services.php` does **not** declare a `meta` key, so the
middleware/controllers fall back to `env('META_APP_SECRET')` on every request.
Because config is cached in production (`php artisan config:cache`), the raw
`env()` call returns `null` under a cached config and the fallback path is
hit. Fix: add

```php
'meta' => [
    'app_secret' => env('META_APP_SECRET'),
],
```

to `config/services.php` so `config('services.meta.app_secret')` resolves
correctly after `config:cache`.

## `ProcessChannelMessage` job

`app/Jobs/ProcessChannelMessage.php` — a thin `ShouldQueue` job that owns the
retry policy for inbound message processing.

- Constructor args: `int $channelId, string $contactId, string $contactName, string $messageText`
- `public int $tries = 3;`
- `public array $backoff = [10, 30, 120];` — retry after 10s, 30s, 2m
- `handle(ChannelMessageService $messageService)` re-resolves the `Channel` by
  id, aborts if missing or `!is_active`, else delegates to
  `processIncomingMessage`.
- `failed(\Throwable)` logs the terminal failure with channel id, contact id,
  and exception message — no DLQ or alerting beyond `Log::error`.

The job does not specify `$queue`, so it runs on the default Redis queue.

## `ChannelMessageService`

`processIncomingMessage(Channel $channel, string $contactId, string $contactName, string $messageText)`
is the single entry point that all three channels funnel into. Steps:

1. **Plan-limit gate.** `PlanLimitService::canSendMessage($tenant, $bot)` — if
   the tenant has exhausted its message quota, returns a hard-coded Romanian
   refusal (`"Ne pare rău, limita de mesaje a fost atinsă..."`) and does **not**
   call the AI. Test-mode bots/tenants bypass the gate.
2. **Conversation resolution.** `Conversation::firstOrCreate` keyed on
   `(channel_id, contact_identifier, status='active')`. New conversations get
   `external_conversation_id = {channel_type}_{contactId}` and seed metadata
   with channel type + contact id.
3. **Contact-name upgrade.** If the stored `contact_name` differs from the
   incoming name and the incoming one is not `'Unknown'`, update.
4. **Persist inbound `Message`** (`direction=inbound`, `content_type=text`,
   `sent_at=now()`).
5. **AI reply** via `generateAiResponse()` — see below.
6. **Persist outbound `Message`** (`direction=outbound`).
7. Update `conversation.messages_count` (from a fresh `count()`) and
   `channel.last_activity_at`.
8. `PlanLimitService::recordMessage(tenant, 1, bot)` for usage metering.
9. `tryExtractChannelLead(...)` — lead auto-extract (next section).
10. Return `['response' => $reply, 'conversation' => $conversation]`.

`generateAiResponse()` is the full orchestrator pipeline, intentionally at
parity with the web chat widget and voice channels:

- `IntentOrchestratorService::plan` + `execute` — knowledge/product/order/lead
  intents.
- Single `Message::limit(30)` load, then reused across `FrustrationDetector`,
  `QueryIntelligence`, `ConversationStrategyEngine`, `ConversationSummaryService`,
  and `ChatModelRouter`.
- `PromptBuilder::for($bot)->with*()->build()` composes the system prompt.
- `TokenCounterService::truncateHistory` clamps to 95% of
  `ModelPricing::getMaxTokens($model)`.
- `ChatCompletionService::complete` returns `['content' => ..., 'cost_cents' => ...]`.
- `cost_cents` is incremented onto the conversation.
- On any exception: falls back to `$bot->buildSystemPrompt()` + single
  user-turn prompt. On double failure: returns a hard-coded Romanian apology.

### Lead auto-extract on ≥3 turns

`ChannelMessageService.php:128-201` implements `tryExtractChannelLead()`:

- Looks up an existing `Lead` by `conversation_id`.
- Regex-extracts:
  - Email: `/[\w.+-]+@[\w.-]+\.\w{2,}/` (lowercased)
  - Phone: `/(07\d{8})/` against digits-only text, or a spaced Romanian mobile
    pattern `0 7 x x x x x x x x`.
  - If no phone found in message, falls back to `contactId` when it looks like
    a Romanian mobile (`+?4?0?7XXXXXXXX`), normalising `40…` → `0…`.
- Name: `contactName` when not `'Unknown'`.
- **Early exit** if email/phone/name are all null.
- If a lead already exists: fills only the missing fields (`email`, `phone`,
  `name`) and returns.
- If no lead yet: **only creates one when `conversation.messages_count >= 3`**,
  to avoid false positives on greetings.
- Qualification score = `email ? 30` + `phone ? 20` + `name ? 10`. Status is
  `qualified` when email or phone present, else `partial`. Source is
  `capture_source = 'chat'`, `capture_reason = 'channel_auto_extract'`.
- All failures caught and logged at `debug` — never surfaces to the user.

## `GET` verify endpoint (`hub.challenge`)

All three controllers share the same `verify(Request)` shape:

1. Read `hub_mode`, `hub_verify_token`, `hub_challenge` query params
   (note: underscore, not the dotted form Meta sends — see Gotchas).
2. Require `hub_mode === 'subscribe'` and a non-empty token.
3. Look up a `Channel` by `type`, `webhook_secret = $token`, `is_active = true`.
4. On match: log and return `$challenge` with `Content-Type: text/plain` and
   HTTP 200.
5. On miss: log warning and return `403 Forbidden`.

The verify token is stored per-channel in `channels.webhook_secret`, so each
tenant's channel has its own token. This is how Meta's "Verify Token" field in
the app dashboard maps to a specific `Channel` row.

## Outbound messaging — state: partial

`app/Services/ChannelMessagingService.php` is the outbound client — separate
from `ChannelMessageService` (note the naming collision). It supports:

- `send(channel, recipientId, message, options)` → routes to
  `sendWhatsApp` / `sendFacebook` / `sendInstagram`.
- `sendTemplate(recipientId, templateName, params, language='ro')` —
  WhatsApp-only, for the 24h-window-template use case.
- Media support on WhatsApp/Facebook via `options.media_type` +
  `options.media_url`.
- All calls go to `https://graph.facebook.com/v18.0/…` with
  `Http::timeout(10)->withToken($token)`.

Token/config is read from:

- WhatsApp: `services.whatsapp.token`, `services.whatsapp.phone_number_id`
  (env `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`).
- Facebook: `services.facebook.page_token` (env `FACEBOOK_PAGE_TOKEN`).
- Instagram: `services.instagram.page_token` (env `INSTAGRAM_PAGE_TOKEN`).

**The gap:** `ChannelMessageService::processIncomingMessage` never calls
`ChannelMessagingService::send`. Today the AI reply is stored in the
`messages` table with `direction=outbound`, but the user on the other side of
WhatsApp/FB/IG **never receives it**. To close this, inject
`ChannelMessagingService` into `ChannelMessageService` and call it after the
outbound `Message::create(...)` block, passing the normalised channel name
(`whatsapp`/`facebook`/`instagram`) and the per-channel contactId. This is
tracked as known tech debt.

The tokens above are **global**, not per-tenant — fine for a single-Page
deployment but wrong for multi-tenant. A per-`Channel` token column (encrypted)
is the correct long-term design.

## `Channel` model + config

`app/Models/Channel.php` — one row per (bot, channel surface).

Columns of interest:

| column             | purpose                                                        |
|--------------------|----------------------------------------------------------------|
| `bot_id`           | FK to the bot that owns conversations on this channel          |
| `type`             | `voice` \| `whatsapp` \| `facebook_messenger` \| `instagram_dm` \| `web_chatbot` |
| `external_id`      | Meta's id: WA `phone_number_id`, FB `page_id`, IG `instagram_id` |
| `webhook_secret`   | Meta verify token (per-channel)                                |
| `config` (json)    | Free-form per-type config                                      |
| `is_active`        | All webhook lookups `->where('is_active', true)`               |
| `last_activity_at` | Bumped by `ChannelMessageService` on each inbound              |

Relationships: `belongsTo(Bot)`, `hasMany(Call)`, `hasMany(Conversation)`.
Helpers: `isVoice()`, `isTextBased()`, `getDisplayName()`, `getChannelIcon()`,
`scopeActive`. Type constants live on the model (`Channel::TYPE_WHATSAPP`
etc.) — always use constants, never literals.

## Gotchas

1. **`META_APP_SECRET` fallback allows unsigned requests.** See "Signature
   verification" above. Mitigation: always set `META_APP_SECRET` in
   `.env`/Coolify, and add a `meta.app_secret` entry to `config/services.php`
   so it survives `config:cache`.
2. **Graph API token expiry.** `FACEBOOK_PAGE_TOKEN` / `INSTAGRAM_PAGE_TOKEN`
   are long-lived Page tokens but still expire (60 days for user-derived
   tokens, indefinite for System User tokens). There is no refresh loop today
   — an expired token silently fails every outbound `send()` with a 190
   response. Use Meta System User tokens and/or wire `/debug_token` into a
   daily scheduled check.
3. **`hub_mode` vs `hub.mode`.** Laravel's `$request->query('hub_mode')`
   matches the underscore form that Laravel normalises dots to. If you ever
   hit these URLs through a proxy that preserves dots, the verify endpoint
   will 403. Prefer Laravel's query helper and log the raw query string when
   debugging.
4. **Echo messages are dropped.** FB/IG controllers early-out when
   `message.is_echo === true` so bot-sent messages do not loop. WhatsApp Cloud
   API uses a separate `statuses` array and the controller filters by
   `messaging_product === 'whatsapp'`, ignoring delivery/read events.
5. **Always 200 OK.** Controllers wrap processing in `try/catch` and always
   return `200` — Meta retries aggressively on 5xx and will disable the
   webhook after enough failures. The trade-off: lost messages on catch. Real
   failures are handled by the queue job's retry policy, not the HTTP layer.
6. **Only text is processed.** Non-text (media, location, sticker, reply)
   messages are skipped by the `type === 'text'` / `message.text` guards.
   Media ingestion is unimplemented.
7. **`messages_count` gating lead extract is racy.** When three messages
   arrive in parallel, each job sees a stale count and the first to cross the
   threshold wins. This is rarely an issue for humans on WA/FB/IG but worth
   knowing when load-testing.

## Runbook

### Wire a new Facebook Page

1. In Meta App Dashboard → Messenger → Settings → **Webhooks**: set callback
   URL to `https://sambla.ro/webhook/facebook`, choose a verify token (a
   random 32+ char string), subscribe to `messages`, `messaging_postbacks`.
2. Create a `Channel` row:
   ```php
   Channel::create([
       'bot_id' => $bot->id,
       'type' => Channel::TYPE_FACEBOOK_MESSENGER,
       'name' => 'Acme FB',
       'external_id' => $facebookPageId,    // from Meta
       'webhook_secret' => $verifyToken,     // same string you entered above
       'is_active' => true,
   ]);
   ```
3. Click **Verify and Save** in Meta — should see `Webhook verified` in
   `storage/logs/laravel.log` with the matching `channel_id`.
4. Subscribe the page to the app: `POST /{page-id}/subscribed_apps` with the
   page token (handled by `SetupFacebookPage` command for FB).
5. Ensure `META_APP_SECRET`, `FACEBOOK_PAGE_TOKEN` are set, then
   `php artisan config:cache`.
6. Send a test message to the Page — expect a `Message` row with
   `direction=inbound`, followed by one with `direction=outbound` within a
   few seconds. The outbound will not deliver to Messenger yet (see
   "Outbound messaging — state: partial").

WhatsApp and Instagram follow the same shape, differing only in what
`external_id` maps to (`phone_number_id` / Instagram account id) and which
env tokens gate the outbound send.

### Debug a channel message

1. Confirm it arrived at the webhook:
   ```
   grep 'WhatsApp webhook' storage/logs/laravel.log
   grep 'Processed incoming message' storage/logs/laravel.log
   ```
2. If the webhook logs show `channel not found`, the `external_id` on the
   `Channel` row does not match Meta's — cross-check the payload's
   `metadata.phone_number_id` (WA) / `recipient.id` (FB/IG).
3. If the handler ran but nothing happened downstream, inspect the queue:
   `php artisan queue:failed` / `php artisan horizon:list` (if Horizon).
   Failed `ProcessChannelMessage` jobs log channel id, contact id, exception.
4. Follow the conversation in Postgres:
   `SELECT id, contact_identifier, messages_count, cost_cents, updated_at
    FROM conversations WHERE channel_id = ? ORDER BY id DESC LIMIT 5;`
5. Look for lead extraction hits:
   `SELECT * FROM leads WHERE capture_reason = 'channel_auto_extract'
    AND conversation_id = ?;`
6. If the AI path failed, search for `ChannelMessage: orchestrator failed`
   and `ChannelMessage: total fallback failed`.

### Test signature verification

Replay a real Meta webhook against a local tunnel (e.g. ngrok):

```bash
SECRET='the-meta-app-secret'
BODY='{"object":"whatsapp_business_account","entry":[]}'
SIG="sha256=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | awk '{print $2}')"

curl -i https://sambla.ro/webhook/whatsapp \
  -H 'Content-Type: application/json' \
  -H "X-Hub-Signature-256: $SIG" \
  --data-raw "$BODY"
```

Expected: `200 OK` body `OK`. Flip one character in `$SIG` and expect
`403 {"error":"Invalid signature"}`. Unset `META_APP_SECRET` in the app env
(do **not** do this in prod) and the same request should pass through — this
reproduces the fallback gotcha.

For the `GET` handshake:

```bash
curl -i 'https://sambla.ro/webhook/facebook?hub_mode=subscribe&hub_verify_token=TOKEN&hub_challenge=ping'
```

Expect `200 ping` when `TOKEN` matches a `Channel.webhook_secret` and
`is_active = true`, else `403 Forbidden`.
