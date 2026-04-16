# Security

## TL;DR

Sambla runs on Laravel 11 with a layered defence posture: (1) secrets live
in `platform_settings` and are encrypted at rest with `APP_KEY` whenever
the key name matches a sensitive suffix; (2) every inbound webhook is
verified before the controller sees the payload — Telnyx via ED25519,
Meta (WhatsApp/FB/IG) via HMAC-SHA256, Stripe via Cashier's built-in
signature check, and the WordPress purchase webhook via a per-tenant
HMAC; (3) tenant isolation is enforced by a global Eloquent scope
(`TenantScope`) that fails closed — the only bypass is a super-admin
whose role **and** `isSuperAdmin()` accessor **and** `admin_view_all`
session flag all agree; (4) every toggle of that flag and every Plan
CRUD write lands in `admin_audit_log`.

The posture is strong for a small team. The known gaps below (Sanctum
`['*']` scopes, unauthenticated `/api/upload-logo*`, `bot_knowledge`
without a `tenant_id` column, Meta signature fallback when the app
secret is empty) are real and tracked — production rollout should close
them before onboarding paying customers outside the design partner set.

## Encryption at rest

`APP_KEY` is the tier-1 secret for the platform. It is a 32-byte base64
value stored in `.env` (never in git, never in `platform_settings`), and
its loss is treated as a rotate-every-secret event (see
[Incident response](#incident-response-runbook)).

`App\Models\PlatformSetting` (`app/Models/PlatformSetting.php`) decides
whether to encrypt a given setting based on a suffix rule
(`PlatformSetting.php:12-19`):

```
_secret_key, _api_key, _webhook_secret,
_password,   _secret,  _token
```

`PlatformSetting::set()` (`PlatformSetting.php:67-82`) hashes the key
against this list; matches are run through `Crypt::encryptString()`
(which uses `APP_KEY` + AES-256-CBC + HMAC) before write, and the
`is_encrypted` boolean is stamped on the row. Reads go through the
`value` accessor (`PlatformSetting.php:40-54`), which decrypts lazily
and logs (but does not throw) on tamper/key-mismatch — the intent is
that a rotated `APP_KEY` degrades the setting to "missing" rather than
crashing the whole request cycle.

**What is encrypted**: Telnyx API key, OpenAI API key, Meta app secret,
Stripe secret+webhook secret (live and test), SMTP password, Gemini
key, Mailcow admin API token, any connector `*_token` or
`*_webhook_secret`.

**What is NOT encrypted**: non-secret platform config (`app_name`,
`mail_from_name`, feature flags, `telnyx_public_key` — this is a
public key, not a secret), plus everything in the `users`, `bots`,
`conversations`, and `messages` tables. Database-level encryption is
not currently enabled; the database lives on the Coolify host and is
reachable only over the internal Docker network.

The admin UI never renders stored ciphertext. `AdminSettingsController`
(`app/Http/Controllers/Admin/AdminSettingsController.php:38`) projects a
`<key>__present` boolean for every sensitive row and the form binds to
that instead of the value — "Stored ✓ / type to replace" UX, no
plaintext, no ciphertext in HTML. This is the fix for commit `a3aebff`.

## Webhook signature verification

Four inbound webhook surfaces, three algorithms:

| Source        | Algorithm       | Verifier                                                                 | Key source                                 |
|---------------|-----------------|--------------------------------------------------------------------------|--------------------------------------------|
| Telnyx        | ED25519         | `VerifyTelnyxSignature` middleware                                       | `platform_settings.telnyx_public_key` (DB) |
| Meta          | HMAC-SHA256     | `VerifyMetaWebhookSignature` middleware                                  | `META_APP_SECRET` / `services.meta.app_secret` |
| Stripe        | HMAC-SHA256     | Cashier `VerifyWebhookSignature` (auto-registered on `stripe/webhook`)   | `cashier.webhook.secret`                   |
| WordPress buy | HMAC-SHA256     | `PurchaseWebhookController::handle` (inline check)                       | Per-tenant `consumer_key` on the connector |

**Telnyx** (`app/Http/Middleware/VerifyTelnyxSignature.php`): reads the
`telnyx-signature-ed25519` and `telnyx-timestamp` headers, concatenates
`timestamp|body`, and calls `sodium_crypto_sign_verify_detached` with
the base64-decoded public key. Missing headers, decode failures, and
verification failures all return `403` with a `Log::warning` entry.
Local/testing environments bypass the check (`VerifyTelnyxSignature.php:14-16`).

**Meta** (`app/Http/Middleware/VerifyMetaWebhookSignature.php`):
computes `sha256=<hex>` over the raw body with `hash_hmac`, compares
with `hash_equals` against the `X-Hub-Signature-256` header. GET
requests are passed through (this is Meta's verification handshake,
which uses a hub challenge, not a signed body). **Gap**: when the app
secret is empty the middleware logs and lets the request through
(`VerifyMetaWebhookSignature.php:31-35`) — intended for local bring-up
but dangerous if the secret is ever accidentally cleared in prod.

**Stripe**: Laravel Cashier auto-registers `POST /stripe/webhook`; its
`VerifyWebhookSignature` middleware throws
`AccessDeniedHttpException` on mismatch, which Laravel maps to a 403
before the controller runs. `stripe/*` is excluded from CSRF in
`bootstrap/app.php:66-68` because Stripe does not send our CSRF token
— the Stripe signature replaces it.

**WooCommerce purchase** (`app/Http/Controllers/Api/PurchaseWebhookController.php:22-39`):
HMAC-SHA256 over the raw body; the secret is the tenant's
`consumer_key` on the `woocommerce` `KnowledgeConnector`, falling back
to `platform_settings.api_key` and finally to `config('app.key')`. The
fallback is pragmatic (bootstrap before the tenant has configured the
connector) but should be removed once setup flows guarantee a
`consumer_key`.

## CSRF

Default Laravel: all POST/PUT/DELETE under `routes/web.php` require a
session-bound CSRF token. The only exception is `stripe/*`
(`bootstrap/app.php:66-68`), justified above. Public API routes under
`routes/api.php` are stateless (no session cookie), so CSRF does not
apply — those routes rely on Sanctum bearer tokens or per-endpoint
signatures instead.

## Tenant isolation

All tenant-scoped Eloquent models apply `TenantScope`
(`app/Models/Scopes/TenantScope.php`). The scope fails closed: if
`auth()->check()` is false, the query returns empty; if the user has a
`tenant_id`, the query is narrowed to that tenant; only a super-admin
with all three of (role `super_admin`, `isSuperAdmin()` accessor true,
session flag `admin_view_all` true) bypasses the filter.

The triple check is deliberate (commit `e8cadb1`): a renamed role, a
missing column, or a stale session flag on its own cannot leak rows.
Re-reading both the role and the accessor on every query means demoting
a user takes effect on the next query, even if their session still
carries `admin_view_all=true`. Full walk-through is in
[03-multi-tenancy.md](03-multi-tenancy.md).

## Admin audit log

`admin_audit_log` (model `App\Models\AdminAuditLog`) captures every
privileged mutation with `user_id`, `action`, `subject_type`,
`subject_id`, a JSON `changes` diff, and `ip`. Writers today:

- `PlanObserver` (`app/Observers/PlanObserver.php`) — logs `plan.created`
  with the full row, `plan.updated` with a field-level `[old, new]`
  diff (skipping pure `updated_at` churn), and `plan.deleted` with the
  slug/name.
- `DashboardController::toggleAdminView`
  (`app/Http/Controllers/Dashboard/DashboardController.php:30-50`) —
  logs `admin.view_all.enabled` / `admin.view_all.disabled` with the
  actor's id and email every time the super-admin toggles cross-tenant
  view.

The log is admin-only (`resources/views/admin/audit.blade.php` behind
`super_admin` middleware). Suggested extensions: tenant user
add/remove, Stripe manual adjustments, and KB deletions.

## Role system

Spatie `laravel-permission` on top of Laravel Sanctum for API tokens.
Roles are seeded by `database/seeders/RolePermissionSeeder.php`:

| Role             | Scope    | Permissions                                                                  |
|------------------|----------|------------------------------------------------------------------------------|
| `super_admin`    | Platform | all 10: bots.*, calls.*, analytics.view, billing.manage, team.manage, settings.manage |
| `tenant_admin`   | Tenant   | all tenant permissions                                                       |
| `tenant_manager` | Tenant   | bots (no delete), calls.view, analytics.view, team.manage                    |
| `tenant_viewer`  | Tenant   | bots.view, calls.view, analytics.view                                        |

`EnsureSuperAdmin` middleware gates `/admin/*`. `TenantAccess`
middleware gates `/dashboard/*` (either has a `tenant_id` or is a
super-admin). Feature-level gating is done by `$user->can('…')` checks
using Spatie's permissions, not by hard-coded role names, so
permissions can be remixed per-role without touching controllers.

## Known gaps from audit

Listed honestly, severity in {low, medium, high}. Tracked in
`/var/www/voicebot-saas/docs/PRODUCT_AUDIT.md`.

- **[high] Sanctum tokens are created with `['*']`.**
  `app/Http/Controllers/Dashboard/SettingsController.php:87-90`. A
  user-issued API key carries wildcard ability; there is no enforced
  per-token scope (read-only keys, write-only keys, bot-scoped keys).
  Fix: surface a scope picker in the UI, default new tokens to
  `['read']`, switch controllers to `tokenCan('bots.write')` etc.

- **[high] `/api/upload-logo` and `/api/upload-logo-url` are
  unauthenticated.** `routes/api.php:16-38`. Any anonymous caller can
  overwrite `public/images/logo-{light,dark}.{png,jpg,svg,webp}`, and
  the URL variant uses `file_get_contents($url)` (no SSRF guard, no
  scheme/host allowlist, no size cap beyond `max:2000` on the URL
  string). Throttle of 10/min is not a substitute for auth. Fix:
  move behind `auth` + `super_admin` middleware; require `https://`;
  validate remote `Content-Length`; enforce SSRF allowlist.

- **[medium] `bot_knowledge` table has no `tenant_id` column.**
  `database/migrations/2024_01_01_000050_create_bot_knowledge_table.php`,
  `app/Models/BotKnowledge.php`. Isolation is enforced transitively
  via `bot_id → bots.tenant_id`, which works for Eloquent reads
  through the `Bot` relation but will not protect raw queries or
  future RAG search jobs that query `bot_knowledge` directly.
  Fix: add a `tenant_id` column + backfill + FK + `TenantScope`.

- **[medium] `VerifyMetaWebhookSignature` passes through when
  `META_APP_SECRET` is empty.**
  `app/Http/Middleware/VerifyMetaWebhookSignature.php:31-35`. Intended
  for local bring-up but is silently-dangerous in prod if the key is
  cleared. Fix: fail closed (`403`) in `production`.

- **[medium] `admin_view_all` is session-scoped rather than
  request-scoped.** A super-admin who toggles on and walks away
  leaves the flag active for the session lifetime. Audit mitigates
  (every toggle is logged), but migrating to a signed per-request
  query parameter would make every cross-tenant read explicit in
  access logs.

- **[low] PurchaseWebhookController fallback secret.** Falls back to
  `platform_settings.api_key` then `config('app.key')`. Remove the
  fallback once connector setup is mandatory.

## Recent remediations

- `e8cadb1` — **remove hardcoded SMTP password** from
  `SmartRegenerateImages`; moved to `platform_settings`. Also added
  the role+flag double-check to `TenantScope` and `toggleAdminView`.
- `a3aebff` — **stop exposing stored secrets** to the admin settings
  form; projected `<key>__present` instead and bound the UI to that
  boolean.
- `1bb841f` — **encrypt sensitive platform_settings**; override
  Cashier/mail config from DB so secrets are read lazily from
  `platform_settings` rather than baked into boot-time config.

## Incident response runbook

**Rotate a compromised `APP_KEY`.**
1. Generate a new key on a maintenance host: `php artisan key:generate --show`.
2. Re-encrypt every sensitive `platform_settings` row under the new
   key: export with the old key (accessor decrypts), swap `APP_KEY`,
   re-`set()` each value. The loader will write fresh ciphertexts.
3. Invalidate sessions (`php artisan session:flush` or truncate
   `sessions`) — Laravel encrypts the session cookie with `APP_KEY`.
4. Re-deploy all containers so every PHP-FPM worker reads the new key.
5. File an incident note; consider rotating dependent third-party
   secrets (Telnyx, OpenAI, Stripe) if the host was reachable.

**Revoke a Sanctum token.**
`auth()->user()->tokens()->where('id', $tokenId)->delete()` — already
wired into `SettingsController::revokeApiKey`. For platform-wide
revoke: `php artisan tinker --execute="Laravel\Sanctum\PersonalAccessToken::query()->delete()"`.

**Investigate suspected cross-tenant access.**
1. Pull the suspect user's recent requests from nginx logs (filter by
   IP + user-agent).
2. `SELECT * FROM admin_audit_log WHERE user_id = ? ORDER BY created_at DESC` —
   confirm whether `admin.view_all.enabled` events precede the
   suspect reads.
3. Scan app logs for `withoutGlobalScopes` hits from non-admin code
   paths; these are the only supported cross-tenant bypasses.
4. If foul play confirmed: revoke the user's tokens, demote the role
   (`$user->syncRoles(['tenant_viewer'])`), invalidate sessions.

**Check the audit log.**
`SELECT created_at, action, user_id, subject_type, subject_id, ip, changes
FROM admin_audit_log ORDER BY created_at DESC LIMIT 200;`

## Hardening recommendations for production

1. **CORS.** `VerifyChatbotDomain` already scopes chat-widget CORS to
   tenant-verified domains. Add a global middleware for the REST API
   (`/api/v1/*`) that echoes `Access-Control-Allow-Origin` only for an
   explicit allowlist in `platform_settings.api_cors_origins`.
2. **Rate limiting.** Tighten `api.rate` (currently 60/min) for
   unauthenticated chatbot endpoints and add per-tenant quotas, not
   just per-IP — an embedded widget can legitimately share IPs behind
   a corporate NAT.
3. **2FA for super-admins.** Fortify's TOTP package drops in behind
   `auth:sanctum` with one migration; enforce on any account holding
   `super_admin`.
4. **Close the known gaps above** (Sanctum scopes, logo upload auth,
   `bot_knowledge.tenant_id`, Meta fallback) before external
   onboarding.
5. **Security headers.** Add `Content-Security-Policy`,
   `Strict-Transport-Security`, `X-Frame-Options`, `Referrer-Policy`
   via a `SecurityHeaders` middleware. Traefik/Coolify can set
   HSTS, but app-level CSP is easier to version.
6. **Secret rotation cadence.** Quarterly for `APP_KEY`, Stripe live
   keys, Telnyx API key; monthly for Meta app secret. Document in
   the ops runbook and add a Coolify reminder.
7. **Database encryption at rest.** Coolify-managed Postgres lives on
   disk unencrypted; when moving to a managed provider, enable
   native TDE (AWS RDS, Neon, etc.) so a stolen disk image is not a
   total compromise.
8. **Dependency scanning.** `composer audit` in CI; Dependabot for
   `package.json` and `composer.json`; Sentry release tracking is
   already live and catches production exceptions with stack traces.
