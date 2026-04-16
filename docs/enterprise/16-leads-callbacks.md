# Leads & Callbacks

## TL;DR

Sambla captures prospect contact info from two sources — passive auto-extraction from conversational channels (chat widget, WhatsApp, Facebook, Instagram) and explicit callback-widget submissions on service pages — and funnels both into a single `leads` table. Every lead moves through a 7-stage pipeline (`new → contacted → scheduled → met → quoted → won|lost`) with per-stage timestamp stamps so SLA/conversion math is trivial. Callback submissions additionally append to `callback_requests` (an immutable activity log), upsert the lead on `(tenant_id, phone)`, rate-limit to 5/hour/IP, email the tenant, and emit a `LEAD_COMPLETED` taxonomy event. Tenant scoping is enforced by the `BelongsToTenant` global scope on the models plus an explicit `authorizeAccess()` guard in the dashboard controller. No outbound CRM sync, no SMS reminders, no Slack webhook — everything stops at the dashboard + a plain-text email.

Relevant files:

- `/var/www/voicebot-saas/app/Models/Lead.php`
- `/var/www/voicebot-saas/app/Models/CallbackRequest.php`
- `/var/www/voicebot-saas/app/Models/Contact.php`
- `/var/www/voicebot-saas/database/migrations/2026_03_30_000001_create_v2_platform_tables.php`
- `/var/www/voicebot-saas/database/migrations/2026_03_30_000004_create_callback_requests_table.php`
- `/var/www/voicebot-saas/database/migrations/2026_03_30_000005_add_pipeline_to_leads.php`
- `/var/www/voicebot-saas/app/Services/ChannelMessageService.php` (lines 128–201)
- `/var/www/voicebot-saas/app/Http/Controllers/Api/CallbackController.php`
- `/var/www/voicebot-saas/app/Http/Controllers/Dashboard/LeadController.php`

## Lead schema

The `leads` table is the single source of truth. Schema is split between the V2 foundation migration and the pipeline extension migration.

**Identity / tenancy**
- `tenant_id` (FK, indexed) — global scope enforced via `BelongsToTenant`
- `bot_id`, `conversation_id`, `contact_id`, `session_id` — origin linkage
- `capture_source` (`chat`, `widget`, `service_page`, `voice`, …)
- `capture_reason` (`channel_auto_extract`, `callback_request`, …)
- `source_page_url` — where the callback widget posted from

**Contact info**
- `name`, `email`, `phone`, `company`
- `preferred_contact` (phone/email/whatsapp)

**Qualification**
- `qualification_score` (smallint, 0–60 in practice)
- `project_type`, `service_type`, `budget_range`
- `products_shown` (jsonb) — products surfaced during chat
- `custom_fields` (jsonb) — tenant-specific extensions

**Pipeline**
- `status` — legacy field (`new` / `partial` / `qualified` / `converted` / `dismissed`); kept in sync by `Lead::advanceTo()`
- `pipeline_stage` — modern field (see pipeline table below)
- Per-stage timestamps: `contacted_at`, `scheduled_at`, `met_at`, `quoted_at`, `won_at`, `lost_at`
- `lost_reason` — free-text string

**Scheduling (callback fields folded into leads)**
- `preferred_date` (date), `preferred_time_slot` (`dimineata` / `dupa-amiaza` / `seara`)

**Outcome**
- `assigned_to` (staff name, free-text), `outcome`, `estimated_value` (decimal 10,2)
- `sent_to_crm_at` — reserved for future CRM integration (never written today)
- `internal_notes` — append-only text log, each line timestamped + signed by the dashboard user

**Compliance**
- `gdpr_consent` (bool, default `false`; set to `true` on widget submission)

The `Lead::STAGES` constant defines display labels (Romanian) and `Lead::STAGE_COLORS` defines Tailwind badge classes.

## Capture paths

### 1. Chat auto-extract (passive)

Implemented in `ChannelMessageService::tryExtractChannelLead()` and invoked on every inbound channel message (WhatsApp / Facebook / Instagram / webchat bridged through channels).

Flow:

1. Look up existing lead by `conversation_id`. If found, merge new fields (only fill nulls) and exit.
2. Run extraction regexes on the message text:
   - **Email:** `/[\w.+-]+@[\w.-]+\.\w{2,}/`, lowercased.
   - **Phone:** first `0\d{9}` match starting `07` against digits-only string; fallback to a spaced/hyphenated `0 7 x x x x x x x` pattern; fallback to `contactId` itself if it parses as `+40 / 40 / 07xxxxxxxx` (true for WhatsApp).
   - **Name:** use `contactName` when present and not literal `"Unknown"`.
3. Abort if **nothing** was extracted.
4. Abort if `conversation.messages_count < 3` — the 3-turn floor prevents false positives from a single "hi" message.
5. Compute score: `+30` email, `+20` phone, `+10` name (max 60).
6. Create `Lead` with `capture_source='chat'`, `capture_reason='channel_auto_extract'`, `status='qualified'` if any of email/phone is present else `'partial'`, and `pipeline_stage` defaults to `'new'`.

Note: this path does **not** set `gdpr_consent=true` (the user never ticked a box). That matters for downstream marketing use.

### 2. Callback widget (explicit)

`POST /api/v1/chatbot/{channel}/callback` → `Api\CallbackController@store`.

Request is validated: `name` + `phone` required, `email` optional, `preferred_date` must be today-or-later, `preferred_time_slot` restricted to the three enum values.

Rate limit: Laravel `RateLimiter` key `callback:{ip}:{channelId}`, 5 attempts per hour, decay 3600s. Over-limit returns HTTP 429 with Romanian error.

Resolution:

1. `Lead::updateOrCreate(['tenant_id' => $bot->tenant_id, 'phone' => $phone], [...])` — this upsert on `(tenant_id, phone)` is how duplicate leads from the same caller are merged across sessions.
2. `$lead->scheduleCallback(...)` advances the lead to `pipeline_stage='scheduled'`, stamps `scheduled_at`, copies over `service_type` / `preferred_date` / `preferred_time_slot`.
3. A `CallbackRequest` row is always inserted — even if the lead already existed. The `callback_requests` table is an append-only activity log, never updated in this flow; each submission is a new row.
4. `qualification_score` is hard-coded to `60` (widget submissions are already high-intent).
5. `gdpr_consent = true` — the widget form implicitly carries consent.
6. Email notification to the tenant (see "Notifications" below).
7. Emits `EventTaxonomy::LEAD_COMPLETED` via `ConversationEventService::track()` with `idempotency_key = "callback:{callback_id}"`.

Supporting endpoint: `GET /api/v1/chatbot/{channel}/callback/services` returns the bot's custom service list (`$bot->settings['callback_services']`) or a default list (instalare / măsurători / consultanță / ofertă / altele) plus the three fixed time slots.

## Pipeline stages

| Stage | Timestamp field | Meaning |
|---|---|---|
| `new` | *(none — `created_at` serves)* | Freshly captured, nobody has touched it yet. |
| `contacted` | `contacted_at` | Someone from the tenant has reached out (call, email, reply). |
| `scheduled` | `scheduled_at` | A callback / meeting is booked. Set automatically by callback-widget submissions via `scheduleCallback()`. |
| `met` | `met_at` | The scheduled contact actually happened. |
| `quoted` | `quoted_at` | A commercial quote / offer has been sent. |
| `won` | `won_at` | Deal closed positively. `estimated_value` is the expected recorded revenue. |
| `lost` | `lost_at` | Deal dead. `lost_reason` captures why. |

`Lead::advanceTo($stage, $extra)` is the canonical transition method. It stamps the corresponding timestamp **only if not already set** (idempotent re-advance) and mirrors the stage back to the legacy `status` column (`new|qualified|converted|dismissed`) for any code still reading that field.

`Lead::STAGES` is also the source of truth for the `updateStatus` validator: `pipeline_stage` is constrained to `implode(',', array_keys(Lead::STAGES))`.

Note: the older legacy stage `qualified` mentioned in early docs has been collapsed — `status='qualified'` is derived from `pipeline_stage in (contacted, scheduled, met, quoted)`, but there is no separate `qualified` pipeline stage today.

## Dashboard

All routes under `/dashboard/leads/*` in `routes/web.php`.

### `LeadController@index`

- Eager-loads `bot` and `conversation`, orders by `created_at` desc, paginates 25.
- Filters (all optional, read from query string):
  - `stage` → `where pipeline_stage`
  - `status` → `where status` (legacy filter)
  - `bot_id`
  - `from` / `to` — date range on `created_at` (`to` is extended to `23:59:59`)
- Computes per-stage counts for the pipeline summary (seven `COUNT(*)` queries — cheap under current volumes, worth a `groupBy` if scale grows).
- `$stats` exposes `total`, `active` (non-`won`/`lost`), `won`, `scheduled`, and the full `pipeline` histogram.

### `LeadController@show`

- Calls `authorizeAccess($lead)` — aborts 403 unless user is `super_admin` or `tenant_id` matches.
- Loads `bot`, `conversation.messages`, `contact`.
- Also fetches `ChatEvent` rows for the conversation ordered chronologically — drives the timeline view.

### `LeadController@updateStatus` (POST `/leads/{lead}/status`)

Validates `pipeline_stage` (required, must be a known key) plus optional scheduling / outcome fields, then calls `$lead->advanceTo(...)`. Assignment is free-text string — no FK to `users`, which is deliberately loose so tenants can write a name without an account.

### `LeadController@addNote` (POST `/leads/{lead}/notes`)

Appends to `internal_notes` — never overwrites. Each append is prefixed with `[YYYY-MM-DD HH:MM - author name]`. Max 2000 chars per note.

### `LeadController@export` (GET `/leads/export`)

Streams CSV (`php://output`, `streamDownload`) with columns `Nume, Email, Telefon, Companie, Scor, Status, Bot, Data`. Filename `leads-YYYY-MM-DD.csv`. **No filter parameters are honoured on export today** — it always dumps the full tenant (scoped by `TenantScope`). Follow-up work is to re-use the index filter set.

## Notifications

`CallbackController::notifyTenant()` sends a plain-text email via `Mail::raw()`. Resolution order:

1. `$tenant->company_email` — preferred.
2. Fallback: `$tenant->users()->first()?->email` — first user row (no role filter, no ordering).

Subject: `📞 Programare nouă: {name} — {service_type}`. Body includes contact details, date/time slot, notes, source, source page URL, and a deep link to `/dashboard/callbacks`. Failures are swallowed to `Log::debug()` so a broken SMTP never breaks the widget. Mail transport is configured per the `reference_mailcow` memo (authenticated `noreply@sambla.ro` via STARTTLS on 587).

There are **no** channels configured for SMS, Slack, webhook, or WhatsApp notifications. Adding one is a new method — do not extend `Mail::raw()`.

## Multi-tenant isolation

Both `Lead` and `CallbackRequest` use the `BelongsToTenant` trait, which:

- Boots the `TenantScope` global scope (filters all queries by the auth user's `tenant_id`; super-admins opt out via the scope's toggle).
- Auto-stamps `tenant_id` from `auth()->user()->tenant_id` on create when the caller didn't supply it.

Because the public callback endpoint has no authenticated user, the controller sets `tenant_id` explicitly from `$bot->tenant_id` (after fetching the bot via `Bot::withoutGlobalScopes()`). This is the correct pattern — never trust a `tenant_id` from the request payload.

The dashboard additionally runs `authorizeAccess($lead)` on `show`, `updateStatus`, and `addNote` as defence-in-depth: the global scope already prevents cross-tenant reads, but an explicit check catches scope-bypass mistakes (e.g. a future route using `withoutGlobalScopes` or route-model binding with a super-admin impersonation toggle).

## GDPR consent

- `gdpr_consent` (bool, default `false`).
- Widget submissions set it to `true` — the form is expected to carry a checkbox before POST.
- Chat auto-extract leaves it `false` — the user typed their phone into a conversation without ticking anything, so it is capture but not consent.

Consequence: any outbound marketing / cold-contact workflow MUST filter `where gdpr_consent = true`. This is not enforced in the DB; it is the caller's responsibility. There is no audit trail of consent changes today.

## Limitations

- **No outbound CRM sync.** The `sent_to_crm_at` column exists but nothing writes to it. No HubSpot / Pipedrive / Salesforce / Zoho integration.
- **No Slack / webhook notification.** Only email on callback; zero fan-out on auto-extracted chat leads.
- **No bi-directional sync.** Editing a lead in an external CRM will never flow back.
- **No SMS reminder** to the prospect before their scheduled callback — preferred-date + preferred-time-slot are captured but nothing reads them for outbound messaging.
- **No deduplication for chat-captured leads** across conversations — the upsert-on-phone lives only in the widget path. A WhatsApp prospect who starts three conversations will generate three `leads` rows.
- **CSV export ignores filters** (see Dashboard section).
- **No lead-assignment FK.** `assigned_to` is a string; no per-user queue views.
- **No SLA fields / aging alerts.** You can derive "in stage N for X days" from the stamp fields but nothing surfaces it.

## Gotchas

- **Phone normalization is partial.** The extractor converts WhatsApp `+40` / `40xxxxxxxxx` into `07xxxxxxxx`, but the callback widget stores whatever the user typed into `phone` verbatim. The `updateOrCreate` key is therefore literal — `0722 111 222` and `0722111222` are **different leads**. If you add international numbers, normalize before upsert.
- **Duplicate handling is phone-only.** No email or name fallback. A callback submission without a phone would fail validation anyway (`phone` is required), but if the lead already exists with a different phone for the same email, you get two rows.
- **3-turn floor is `messages_count`-based.** If `messages_count` is null (legacy conversations), the `<3` check treats it as `0` and the lead is suppressed. Backfill `messages_count` before changing this threshold.
- **Legacy `status` column still gets written** by `advanceTo()`. Don't drop it without auditing reporting queries.
- **Rate limit is per IP+channel.** Behind a CDN that does not forward `X-Forwarded-For` correctly, you can accidentally rate-limit every tenant user to one shared key. Verify `TrustProxies` is configured in prod.
- **Auto-extract swallows all exceptions** at `Log::debug()`. If extraction silently stops working, nothing in stdout will signal it — check the debug channel.

## Runbook

### Add a new pipeline stage

1. Add the key + Romanian label to `Lead::STAGES` (order matters — this is the display order).
2. Add a colour class to `Lead::STAGE_COLORS`.
3. If the stage needs a stamp field, add a `{stage}_at` timestamp to the `leads` table in a new migration, include it in `$fillable` and `casts()`, and add the `match` arm in `Lead::advanceTo()`.
4. If the stage maps to a different legacy `status`, extend the second `match` in `advanceTo()`.
5. Update the dashboard kanban view (`dashboard.leads.index` Blade) if it hard-codes the stage list.
6. No controller change required — `updateStatus` derives the validator from `array_keys(Lead::STAGES)`.

### Trigger a test callback

```bash
curl -X POST https://sambla.ro/api/v1/chatbot/{CHANNEL_ID}/callback \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Test User",
    "phone": "0722000000",
    "email": "test@example.com",
    "service_type": "consultanta",
    "preferred_date": "2026-04-20",
    "preferred_time_slot": "dimineata",
    "notes": "test submission",
    "source": "runbook"
  }'
```

Expect `200 { success, callback_id, message }`. Verify: (a) `leads` row with `pipeline_stage='scheduled'` and `scheduled_at` set; (b) `callback_requests` row with `status='pending'`; (c) `chat_events` row with `event_name=lead_completed` and `idempotency_key='callback:{id}'`; (d) email in the tenant's `company_email` inbox. Re-submitting with the same `phone` upserts the lead (same `id`) but creates a **new** `callback_requests` row. After 5 submissions from the same IP+channel in an hour, expect `429`.

### Export leads for a tenant

Dashboard → Leads → *Export CSV* button, which hits `GET /dashboard/leads/export`. File is `leads-{today}.csv`, full tenant dump, no filter support. For a filtered export, currently the only workaround is a `tinker` query plus manual CSV dump — a TODO to pipe the index filters into `export()` is tracked here implicitly.
