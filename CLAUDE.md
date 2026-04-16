# Sambla - Architecture Document

## Overview
Multi-tenant SaaS platform for AI-powered voice bots. Businesses can create, configure, and deploy conversational voice agents that handle inbound/outbound phone calls using OpenAI's Realtime API and Twilio for telephony (migrating off Telnyx due to contractual issues and slow number approval — existing Telnyx numbers keep working until cutover).

## Domain
- Production: https://sambla.ro
- Managed by: Coolify (self-hosted PaaS)

## Tech Stack
- **Framework:** Laravel 11 (PHP 8.3)
- **Frontend:** Inertia.js + Vue 3 (or React) + Tailwind CSS
- **Database:** PostgreSQL 16 with pgvector (for embeddings/RAG)
- **Cache/Queue/Session:** Redis 7
- **WebSocket:** Laravel Reverb
- **AI Voice:** OpenAI Realtime API (GPT-4o voice)
- **Telephony:** Twilio (voice calls, phone numbers). Abstracted behind `App\Services\Telephony\TelephonyProvider`; Telnyx (`TelnyxService`) kept as secondary implementation for numbers provisioned before cutover. Default selected via `config('telephony.default')` / `TELEPHONY_DEFAULT_PROVIDER`.
- **Payments:** Stripe via Laravel Cashier
- **Auth/Permissions:** Laravel Sanctum + spatie/laravel-permission
- **Error Tracking:** Sentry
- **Containerization:** Docker (multi-stage PHP 8.3 FPM Alpine)
- **Reverse Proxy:** Nginx + Traefik (via Coolify)

## Infrastructure (Coolify)
- **Server:** Ubuntu 24.04, 185.104.181.113
- **PostgreSQL:** Coolify-managed, container UUID `hvmz3tv0yocndy261khok7dm`
- **Redis:** Coolify-managed, container UUID `ya3ev0yj5ix17lsol1xfhslw`
- **Credentials:** `/var/www/voicebot-saas/.env.coolify` (NOT in git)

## Multi-Tenant Architecture
- **Tenant isolation:** Single database, `tenant_id` column on all tenant-scoped tables
- **Tenant scoping:** Global `TenantScope` via `BelongsToTenant` trait on ~22 models
- **Roles:** super_admin (platform), tenant_admin, tenant_manager, tenant_viewer — stored via spatie/laravel-permission; role names use underscores
- **Billing:** Per-tenant Stripe subscriptions via Cashier 16; `Tenant` is the Billable customer

## Key Modules
1. **Tenant Management** – registration, onboarding wizard, settings, trial lifecycle (reminder + expiry)
2. **Voice Bot Builder** – prompt configuration, personality, knowledge base, A/B prompt variants
3. **Phone Numbers** – Twilio number provisioning per tenant (inbound + outbound); Telnyx numbers kept active via dual-provider support in `TelephonyManager::forNumber()` until cutover
4. **Call Handling** – Twilio webhooks at `/webhook/twilio/{voice,status}` (X-Twilio-Signature HMAC-SHA1 + CallSid idempotency + state machine, iter 18); Telnyx webhooks at `/webhook/telnyx/*` (ed25519 + timestamp replay window, iter 10) still live for pre-migration numbers. Media stream bridge at `services/media-stream/` (Node.js, deployed as a separate container) accepts Twilio WebSocket at `wss://host/ws/media-stream`, transcodes audio (mulaw 8k ↔ PCM16 24k), bridges to OpenAI Realtime, handles barge-in / DTMF. Stateless — resolves bot config from Postgres + Redis on stream start.
5. **Knowledge Base** – document upload, pgvector embeddings with HNSW + FTS (hybrid RAG with RRF + sibling chunks), re-embed on change
6. **Chat / Messaging** – web chatbot widget (embeddable), SSE streaming endpoint, per-channel config
7. **Channels** – WhatsApp / Facebook / Instagram inbound webhooks with HMAC signature verification (outbound send paths partial)
8. **WooCommerce** – WP plugin (`wordpress-plugin/sambla-woocommerce/`) syncs products to `BotKnowledge`; recommendations inside chat + voice
9. **Leads & Callbacks** – chat auto-extract (≥3 messages) + explicit callback widget API with GDPR consent, 7-stage pipeline, dashboard
10. **ElevenLabs** – voice cloning pipeline (sample → job → cloned_voice row → used in RealtimeSession)
11. **Analytics** – call logs, duration, sentiment, cost; admin reports (revenue / cost / margin)
12. **Billing** – Stripe subscriptions (monthly + yearly), one-off top-up credit bundles, TVA 21% RO +TVA pricing, custom per-tenant plans (hidden from public)
13. **Social Media Factory** – Gemini 3 / 3.1 image pipeline, FB + IG posting, approval workflow (currently paused)
14. **API** – REST API with Sanctum tokens for integrations

## Docker Services
- `app` - PHP-FPM application server
- `nginx` - Web server with WebSocket proxy
- `queue` - Laravel queue worker (Redis)
- `scheduler` - Laravel task scheduler
- `reverb` - WebSocket server (Laravel Reverb, dashboard realtime events)
- `media-stream` - Node.js Twilio ↔ OpenAI Realtime bridge (separate container, `services/media-stream/`)

## Commands
- `composer install` - Install PHP dependencies
- `npm install && npm run build` - Build frontend assets
- `php artisan migrate` - Run database migrations
- `php artisan test` - Run test suite
- `docker compose up -d` - Start all services
- `docker compose build` - Rebuild containers

## File Structure Conventions
- Controllers: `app/Http/Controllers/`
- Models: `app/Models/`
- Services: `app/Services/` (business logic)
- Actions: `app/Actions/` (single-purpose classes)
- Events/Listeners: `app/Events/`, `app/Listeners/`
- API routes: `routes/api.php`
- Web routes: `routes/web.php`
- WebSocket channels: `routes/channels.php`
