# Sambla

Multi-tenant SaaS for AI-powered voice and chat agents. Businesses sign up, provision a Romanian phone number, configure a bot (persona, knowledge base, tools) and their customers then interact with that bot over phone, a web chat widget, WhatsApp, Facebook Messenger or Instagram DM.

**Production:** https://sambla.ro
**Stack:** Laravel 11 · PHP 8.3 · Inertia/Vue 3 · PostgreSQL 16 (pgvector) · Redis 7 · Laravel Reverb · OpenAI Realtime · Telnyx · Stripe (Cashier 16)
**Deploy:** single Ubuntu 24.04 host via Coolify · Docker (app, nginx, queue, scheduler, reverb)

## Documentation

The full technical architecture review is in [`docs/enterprise/`](docs/enterprise/00-README.md) — 20 code-grounded chapters covering infrastructure, multi-tenancy, voice, chat, channels, RAG, billing, analytics, security, and CI. Start at [`docs/enterprise/00-README.md`](docs/enterprise/00-README.md).

Quick index:

| Area | Doc |
|---|---|
| Deploy topology, volumes, TLS | [01-infrastructure](docs/enterprise/01-infrastructure.md) |
| Horizon, crons, rate limiter | [02-queues-scheduler](docs/enterprise/02-queues-scheduler.md) |
| `TenantScope`, role model | [03-multi-tenancy](docs/enterprise/03-multi-tenancy.md) |
| DB-backed config + secrets | [04-settings-secrets](docs/enterprise/04-settings-secrets.md) |
| Auth, Sanctum, Spatie roles | [05-auth](docs/enterprise/05-auth.md) |
| Telnyx voice webhooks | [06-voice-telnyx](docs/enterprise/06-voice-telnyx.md) |
| OpenAI Realtime + ElevenLabs | [07-voice-realtime](docs/enterprise/07-voice-realtime.md) |
| Embeddable chat widget + SSE | [08-chat-widget](docs/enterprise/08-chat-widget.md) |
| WhatsApp / FB / IG | [09-channels](docs/enterprise/09-channels.md) |
| pgvector hybrid RAG | [10-knowledge-rag](docs/enterprise/10-knowledge-rag.md) |
| Stripe wiring (Cashier) | [11-billing-stripe-wiring](docs/enterprise/11-billing-stripe-wiring.md) |
| Plans, top-ups, custom pricing | [12-billing-plans-topups](docs/enterprise/12-billing-plans-topups.md) |
| Romanian VAT (21% exclusive) | [13-billing-tax](docs/enterprise/13-billing-tax.md) |
| Trials, dunning, lifecycle | [14-billing-lifecycle](docs/enterprise/14-billing-lifecycle.md) |
| WooCommerce push sync | [15-woocommerce](docs/enterprise/15-woocommerce.md) |
| Leads, callback widget | [16-leads-callbacks](docs/enterprise/16-leads-callbacks.md) |
| Tenant + admin analytics | [17-analytics-reports](docs/enterprise/17-analytics-reports.md) |
| Social media factory (paused) | [18-social-factory](docs/enterprise/18-social-factory.md) |
| Security posture, gaps | [19-security](docs/enterprise/19-security.md) |
| PHPUnit suite, CI | [20-testing-ci](docs/enterprise/20-testing-ci.md) |

Project notes that change frequently live in [`CLAUDE.md`](CLAUDE.md) and [`ROADMAP.md`](ROADMAP.md).

## Local development

Prerequisites: PHP 8.3, Composer 2.x, Node 20+, PostgreSQL 16 with the `vector` extension, Redis 7.

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

For hot-reload during frontend work:

```bash
npm run dev
```

Queue worker (required for voice/RAG/social work):

```bash
php artisan horizon
```

Scheduler (required for trial lifecycle, knowledge batching, social posting):

```bash
php artisan schedule:work
```

Reverb WebSocket server (required for live dashboard updates):

```bash
php artisan reverb:start
```

## Docker

```bash
docker compose build
docker compose up -d
```

Five services come up: `app` (PHP-FPM), `nginx`, `queue` (Horizon), `scheduler`, `reverb`. See [`docker-compose.yml`](docker-compose.yml) and [`Dockerfile`](Dockerfile).

## Testing

```bash
php artisan test
```

The suite is guarded by four independent production-DB safeguards (see [`20-testing-ci`](docs/enterprise/20-testing-ci.md)). CI runs the same command on every push via [`.github/workflows/tests.yml`](.github/workflows/tests.yml) against a Postgres 16 + pgvector service container.

## Directory layout

```
app/
  Http/Controllers/       thin HTTP layer
  Services/               business logic (chat orchestration, RAG, billing, voice, social)
  Actions/                single-purpose classes
  Jobs/                   queue jobs (knowledge ingest, voice processing, Stripe sync, social publish)
  Models/                 Eloquent models (~60)
  Events/ Listeners/      domain events
bootstrap/                Laravel bootstrap, middleware aliases
config/                   static config; runtime overrides come from platform_settings table
database/migrations/      ordered schema history
docs/enterprise/          architecture review (this dossier)
public/widget/            embeddable JS chat widget
resources/js/             Inertia pages
resources/views/          Blade views (admin, widget embeds)
routes/                   web.php, api.php, admin.php, console.php, channels.php
tests/                    Feature + Unit tests (PHPUnit)
wordpress-plugin/         sambla-woocommerce WP plugin (push products → Sambla)
```

## Operational notes

- **Secrets are DB-backed**, not `.env`-driven at runtime. Rotate from `/admin/setari` (OpenAI, Stripe, Telnyx, SMTP, Meta). `.env.example` lists the boot-time fallbacks only.
- **Stripe mode switching** (live ↔ test) is done from the admin panel; the `BillingController` guard re-maps `tenants.stripe_id` if it belongs to the wrong mode.
- **Telnyx Media Stream sidecar is not in this repo.** Inbound calls bridge through a separate WebSocket process — see [`06-voice-telnyx`](docs/enterprise/06-voice-telnyx.md) for the contract.
- **Tenant isolation** relies on `App\Models\Scopes\TenantScope`. The super-admin bypass requires three independent checks (Spatie role + model accessor + session flag); never call `withoutGlobalScopes()` in tenant-facing controllers.

## Licence

Proprietary. All rights reserved.
