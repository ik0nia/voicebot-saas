# Testing & CI

## TL;DR

Sambla has a small but deliberately load-bearing PHPUnit suite (9 Feature classes, ~14 Unit classes, ~3100 LOC) that runs on a PostgreSQL 16 + pgvector service in GitHub Actions on every push to `master`/`main` and every pull request. The project runs on the **same server** as production (same checkout, same Coolify host), so the entire testing infrastructure is built around one principle: **a test run must never touch the production database**. That is enforced by four independent layers — `tests/bootstrap.php` (PhpunitGuard), `tests/TestCase.php` setUp checks, `SafeguardDatabase` event listener, and the `Blocked*` command overrides. The suite is structured around the paths that actually move money or leak tenant data: Stripe webhook idempotency, credit accounting, plan visibility scoping, cross-tenant 403s, and orchestrator/policy wiring. CI provisions Postgres with the `vector` extension and Redis as services, runs migrations with `--force`, then executes `vendor/bin/phpunit --no-coverage`.

Entrypoints:

- `phpunit.xml` — test config, pins `DB_DATABASE=voicebot_test` + `APP_ENV=testing`.
- `tests/bootstrap.php` — pre-Laravel production guard.
- `tests/TestCase.php` — per-test production guard.
- `app/Console/Commands/SafeguardDatabase.php` — artisan event guard.
- `app/Console/Commands/Blocked*.php` — artisan command overrides.
- `.github/workflows/tests.yml` — GitHub Actions workflow.

## Test suite overview

### Feature tests (9 files, ~1100 LOC)

| File | Scope | # tests |
|---|---|---|
| `BillingTest.php` | Stripe webhooks, credits, plan scoping | 7 |
| `ApiTest.php` | Sanctum token auth, rate limits, tenant isolation | 6 |
| `AuthTest.php` | Login/register/logout, guest redirects | 6 |
| `BotTest.php` | Bot CRUD + tenant isolation | 6 |
| `CallTest.php` | Call list, filters, tenant isolation | 5 |
| `DashboardTest.php` | Dashboard pages + metrics (dataProvider) | 3 |
| `EventTrackingApiTest.php` | `/track/batch` + `/capabilities`, idempotency, validation | 9 |
| `OrchestratorWiringTest.php` | Orchestrator feature flag + fallback | 5 |
| `PolicyWiringTest.php` | Policy injection, merge precedence, prompt output | 5 |

### Unit tests (~14 files, ~2000 LOC)

Focused on pure service classes:

- `BotServiceTest`, `KnowledgeSearchServiceTest`, `ConversationEventServiceTest`, `FillingMessageServiceTest` / `IntentTest` / `ToneTest` (3 files for the filling-message module), `CategoryNavigationLogicTest` / `ServiceTest`, `ElevenLabsClonedTtsTest`, `EventTaxonomyTest`, `PromptGuardrailsTest`, `TenantScopeTest`, `TtsChunkingTest`, `VoiceEdgeCasesTest`.

`tests/Unit/Services/` exists as a subdirectory for further service tests.

### Factories (`database/factories/`)

`TenantFactory`, `UserFactory`, `BotFactory`, `BotKnowledgeFactory`, `CallFactory`, `PhoneNumberFactory`, `TranscriptFactory`, `PlanFactory` (added today for `BillingTest`).

## BillingTest critical paths

`tests/Feature/BillingTest.php` is the most important file in the suite because it guards the code that charges customers. Seven tests, 18 assertions:

1. **`test_checkout_session_completed_increments_credits_and_is_idempotent`** — Fires `WebhookReceived` with a `checkout.session.completed` payload, asserts `tenant.message_credits` goes from 0 → 1000 and a row lands in `credit_purchases`. Then **replays the same event** and asserts credits stay at 1000 and purchase count stays at 1 — this is the idempotency contract that prevents double-billing on Stripe retries.
2. **`test_checkout_session_completed_ignores_unknown_unit`** — Top-up with `topup_unit=garbage` must be a no-op (no credits added, no purchase row). Defends against malformed metadata.
3. **`test_credit_service_decrements_atomically_and_refuses_overspend`** — `CreditService::consume()` must atomically decrement and return `false` when the tenant has insufficient credits, leaving the balance untouched. Only one `credit_consumptions` row is written across the success + failure attempt.
4. **`test_subscription_created_syncs_tenant_plan_slug`** — `customer.subscription.created` with a known `price_id` flips `tenant.plan_slug` from `free` → `chat-pro-test`.
5. **`test_subscription_deleted_resets_tenant_to_free`** — Cancellation must reset `plan_slug` to `free`. Prevents stale paid access after cancellation.
6. **`test_plan_visibility_scope_hides_other_tenant_custom_plans`** — `Plan::visibleTo($tenantId)` scope returns public plans + own custom plans, never other tenants' customs or private globals. Direct query-scope guarantee.
7. **`test_subscribe_rejects_another_tenants_custom_plan`** — HTTP-level defense: posting to `dashboard.billing.subscribe` for another tenant's custom plan returns `403`, not `500` or silent success.

## PhpunitGuard (tests/bootstrap.php)

PHPUnit loads `tests/bootstrap.php` **before** Laravel boots, which is the earliest possible interception point. It reads the real `.env` (not the `phpunit.xml` override) and looks at:

1. `APP_ENV` from `.env` — if it's `production` or `prod`, we are on a prod server.
2. `DB_DATABASE` from `.env` — the production DB name.
3. The runtime `DB_DATABASE` env (what `phpunit.xml` or the shell exports).

**What it blocks:** A test run on a production host where the runtime DB equals the production DB. It prints a red banner, writes a `production.CRITICAL` line to `storage/logs/laravel.log`, and `exit(1)`s before any autoloader even loads.

**What it permits (bypass):** If `DB_DATABASE` at runtime differs from the production DB, it `goto`s `passed_guard` and continues normally. `phpunit.xml` sets `DB_DATABASE=voicebot_test` by default, so the bypass is automatic — but only because `voicebot_test != voicebot`. **Do not ever set `DB_DATABASE=voicebot` in a shell before running phpunit on prod.** The safe invocation on any prod-adjacent host is:

```bash
DB_DATABASE=voicebot_test APP_ENV=testing vendor/bin/phpunit
```

After bootstrap passes, `Tests\TestCase::setUp()` re-checks three more invariants on every test: DB name is not `voicebot`, DB ends in `_test`, and `APP_ENV` is not `production`. Any failure calls `$this->fail()` immediately.

## SafeguardDatabase + Blocked command overrides

Even if a developer bypasses PHPUnit and uses artisan directly, `app/Console/Commands/SafeguardDatabase.php` intercepts destructive commands at the `CommandStarting` event. Registered from `AppServiceProvider::boot()` alongside `DB::prohibitDestructiveCommands(!app()->environment('local', 'testing'))`.

**Blocked commands** (`SafeguardDatabase::BLOCKED_COMMANDS`):

- `migrate:fresh` — drops all tables, re-runs migrations.
- `migrate:refresh` — rolls back + re-runs.
- `migrate:reset` — rolls back (drops tables).
- `db:wipe` — drops tables/views/types.
- `schema:dump` — with `--prune` can drop migrations.
- `test` — because `RefreshDatabase` truncates everything.

**Behaviour:** In any env other than `local`, the event listener logs with full forensic context (hostname, user, pid, argv, cwd) and `exit(1)`s with a red banner. **Local is the only env where these run unimpeded.** The `Blocked*` command classes (`BlockedMigrateFresh`, `BlockedMigrateRefresh`, `BlockedMigrateReset`, `BlockedDbWipe`, `BlockedTest`) are Layer 2: they **replace the actual Laravel commands** by taking the same signature, so even if the event listener fails to register, the override runs. Each one allows `local` + `testing` envs (in-process, e.g. `BlockedMigrateFresh` calls `Schema::dropAllTables()` + `migrate --force`; `BlockedTest` shells out to `vendor/bin/phpunit`) and errors out elsewhere.

This is why running `php artisan test` works locally and in CI (`APP_ENV=testing`), but returns `FAILURE` with instructions on a prod server.

## Running tests on the production server

`phpunit.xml` already exports the safe defaults. The canonical invocation is:

```bash
DB_DATABASE=voicebot_test APP_ENV=testing vendor/bin/phpunit
```

What happens:

1. `tests/bootstrap.php` sees `.env APP_ENV=production` + `.env DB_DATABASE=voicebot`, compares against runtime `DB_DATABASE=voicebot_test`, diverges, falls through `passed_guard`.
2. Laravel boots with `APP_ENV=testing` (from phpunit.xml) and connects to `voicebot_test`.
3. `TestCase::setUp()` re-verifies: DB name isn't `voicebot`, ends in `_test`, env isn't prod — all pass.
4. `RefreshDatabase` truncates `voicebot_test` only.

**Prereq:** `voicebot_test` must exist on the Coolify Postgres container and have the `vector` extension. One-time:

```bash
# Run inside the Coolify Postgres container hvmz3tv0yocndy261khok7dm
createdb -U voicebot voicebot_test
psql -U voicebot -d voicebot_test -c "CREATE EXTENSION IF NOT EXISTS vector;"
```

`php artisan test` on the server will hit `BlockedTest` and refuse; call `vendor/bin/phpunit` directly.

## CI workflow (`.github/workflows/tests.yml`)

Triggers: `push` to `master`/`main`, any `pull_request`. Single job `phpunit` on `ubuntu-latest`.

**Services:**

- `postgres` — `pgvector/pgvector:pg16` image, user/password/db = `voicebot`/`voicebot`/`voicebot_test`, port `5432`, `pg_isready` healthcheck.
- `redis` — `redis:7-alpine`, port `6379`, `redis-cli ping` healthcheck.

**Steps:**

1. `actions/checkout@v4`.
2. `shivammathur/setup-php@v2` — PHP 8.3 with `mbstring, pdo_pgsql, redis, bcmath, intl, sodium`, `coverage: none`.
3. Composer cache keyed on `composer.lock`.
4. `composer install --prefer-dist --no-interaction --no-progress`.
5. `cp .env.example .env || true` (tolerant).
6. `psql … CREATE EXTENSION IF NOT EXISTS vector` — pgvector must exist before migrations create `vector` columns.
7. `php artisan key:generate --force`.
8. `php artisan migrate --force` with explicit `DB_*` envs pointing at the Postgres service.
9. `vendor/bin/phpunit --no-coverage` with full test envs (`CACHE_STORE=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`).

There is **no** matrix, no parallelism, no coverage, no static analysis (Pint/PHPStan/Psalm), no JS test job. CI is deliberately minimal — it runs the same binary you run locally.

## Missing tests (top 10)

1. **Telnyx inbound-call webhook → OpenAI Realtime handshake** — zero coverage on the phone-to-bot bridge, arguably the core product.
2. **Knowledge RAG end-to-end** — document upload → embedding job → `KnowledgeSearchService` retrieval is only unit-tested on the retrieval half.
3. **Web chat widget WebSocket flow** — `/channels.php` presence/private channels, Reverb auth, message broadcast.
4. **Queue autoscale + Horizon supervisor** — `QueueAutoScale` command has no test.
5. **Stripe webhook signature verification** — `BillingTest` synthesises events directly and skips the Cashier signature middleware.
6. **Usage-based billing for call minutes** — per-minute metering / `CreditService::consume('call_seconds', …)` has no test beyond the generic decrement path.
7. **Multi-tenant impersonation + role escalation** — spatie permission gates (super-admin ↔ admin) aren't exercised.
8. **Onboarding / tenant registration flow** — `AuthTest` covers user register but not full tenant provisioning (phone number, default bot, Stripe customer creation).
9. **OpenAI Realtime session lifecycle** — ephemeral tokens, session expiry, reconnection.
10. **Crawl + ingest pipeline** — recent `CrawlWebsite` fixes (see commit `045927e`) landed without feature tests.

## Runbook

### Add a new test

1. Pick the right suite: Feature for anything hitting routes/DB/events; Unit for pure service classes.
2. `touch tests/Feature/MyThingTest.php`, extend `Tests\TestCase`, add `use RefreshDatabase`.
3. If you need a new model factory: create it under `database/factories/` (follow `PlanFactory` as the most recent template).
4. Run locally: `vendor/bin/phpunit --filter MyThingTest`.
5. Commit — CI picks it up automatically.

### Debug a flaky test

1. Reproduce with `--filter` + `--repeat=10` to confirm flakiness vs failure.
2. Check time-dependent assertions — the suite uses real `now()` in places; wrap with `Carbon::setTestNow()`.
3. Check queue mode — `QUEUE_CONNECTION=sync` in CI means jobs run inline; if a test passes locally but fails in CI, someone may have dispatched to a real queue.
4. Check DB ordering — PostgreSQL has no implicit row order; add `orderBy('id')` before any index-based assertion.
5. Dump on failure: add `$response->dump()` or `Log::info(DB::getQueryLog())` inside the failing test.
6. If `RefreshDatabase` itself is the culprit, ensure no migration was added that depends on seeded data.

### Run tests against staging DB

Staging shares the same Coolify Postgres. Use a scratch DB that ends in `_test`:

```bash
createdb -U voicebot voicebot_staging_test
psql -U voicebot -d voicebot_staging_test -c "CREATE EXTENSION IF NOT EXISTS vector;"

DB_DATABASE=voicebot_staging_test \
APP_ENV=testing \
DB_HOST=<coolify-pg-host> \
DB_USERNAME=voicebot \
DB_PASSWORD=<from .env.coolify> \
php artisan migrate --force
DB_DATABASE=voicebot_staging_test APP_ENV=testing vendor/bin/phpunit
```

The `_test` suffix is mandatory — `TestCase::setUp()` Check 2 will abort otherwise. Do **not** point tests at `voicebot_staging` (no suffix) or any DB used by the staging app. When done, `dropdb voicebot_staging_test`.
