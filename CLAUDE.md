# Sambla - Architecture Document

## Overview
Multi-tenant SaaS platform for AI-powered voice bots. Businesses can create, configure, and deploy conversational voice agents that handle inbound/outbound phone calls using OpenAI's Realtime API and Telnyx for telephony.

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
- **Telephony:** Telnyx (voice calls, phone numbers)
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
- **Tenant isolation:** Single database, tenant_id column on all tenant-scoped tables
- **Tenant scoping:** Global query scopes on Eloquent models
- **Roles:** super-admin (platform), admin (tenant), agent, viewer
- **Billing:** Per-tenant Stripe subscriptions via Cashier

## Key Modules
1. **Tenant Management** - registration, onboarding, settings
2. **Voice Bot Builder** - prompt configuration, personality, knowledge base
3. **Phone Numbers** - Telnyx number provisioning per tenant
4. **Call Handling** - inbound/outbound calls via Telnyx + OpenAI Realtime
5. **Knowledge Base** - document upload, embedding with pgvector, RAG
6. **Analytics** - call logs, duration, sentiment, cost tracking
7. **Billing** - Stripe subscriptions, usage-based billing for call minutes
8. **API** - REST API with Sanctum tokens for integrations

## Docker Services
- `app` - PHP-FPM application server
- `nginx` - Web server with WebSocket proxy
- `queue` - Laravel queue worker (Redis)
- `scheduler` - Laravel task scheduler
- `reverb` - WebSocket server (Laravel Reverb)

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
