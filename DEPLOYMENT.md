# Sambla — Deployment & Architecture Guide

## Arhitectura runtime

```
Internet
  │
  ▼
Traefik (reverse proxy, SSL)
  │
  ├──► Container NGINX ──► Container APP (php-fpm:9000)
  │      static files         Laravel app
  │      from image            ├── s6: php-fpm
  │                            ├── s6: horizon (Coolify, high+default only)
  │                            ├── s6: scheduler
  │                            └── s6: nginx (internal)
  │
  ├──► Container QUEUE
  │      php artisan horizon
  │      ├── chat-workers (high, default) — 2-6 procese, autoscale
  │      └── knowledge-workers (knowledge) — 1-3 procese, autoscale
  │
  └──► Container REVERB
         WebSocket server
```

## Sursa de adevăr

| Ce                | Unde                              |
|-------------------|-----------------------------------|
| Cod               | Git (master branch)               |
| Configurare cozi  | `config/horizon.php`              |
| Containere        | `docker-compose.yml` (citit de Coolify) |
| Deploy            | Coolify (voice.ikonia.cloud)      |
| Assets (CSS/JS)   | Pre-built în git (`public/build/`) |
| Secrets           | `.env.coolify` (NU în git)        |

## Reguli OBLIGATORII

### 1. TOTUL trebuie să fie în git ÎNAINTE de deploy
- Views, CSS, JS, config-uri, migrări — TOTUL commituit
- `public/build/` e tracked în git (nu e în .gitignore)
- Coolify face deploy din git, NU de pe disc

### 2. Assets (CSS/JS) se buildează LOCAL, nu în container
- Dockerfile NU are `npm run build`
- Workflow: `npm run build` local → `git add public/build/` → commit → deploy
- Alpine Docker produce CSS Tailwind incomplet

### 3. docker-compose.yml NU e un fișier de dev
- Coolify îl citește și îl execută ca atare
- Orice schimbare aici = schimbare în producție la deploy
- NU adăuga servicii fără testare

### 4. Fișiere locale ≠ Producție
- Fișierele de pe disc (/var/www/voicebot-saas) sunt pentru dev
- Site-ul e servit din CONTAINER, nu de pe host
- Schimbări locale nu apar pe site fără commit + deploy

### 5. Queue-uri
- `high` + `default` = chat (rapid, latență mică)
- `knowledge` = RAG/embeddings (mai lent, mai multă memorie)
- Configurare: `config/horizon.php` → secțiunea `environments.production`
- Dashboard: https://sambla.ro/horizon (admin only)

## Workflow deploy

```bash
# 1. Modifici cod
# 2. Dacă ai modificat views/CSS:
npm run build

# 3. Commit
git add -A  # sau fișiere specifice
git commit -m "descriere"

# 4. Push
git push origin master

# 5. Deploy (una din opțiuni):
# a) Din Coolify UI — click Deploy
# b) Via API:
curl -X POST "http://localhost:8000/api/v1/deploy" \
  -H "Authorization: Bearer $COOLIFY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"uuid": "ld7mc5p77cpreg8dhqud53es"}'

# 6. Verificare post-deploy:
curl -s https://sambla.ro | grep '<title>'
curl -s https://sambla.ro/build/manifest.json
```

## Ce NU face Coolify

- NU citește Dockerfile CMD (suprascrie cu s6-overlay)
- NU citește supervisord.conf
- NU rulează `npm run build` (scos din Dockerfile)
- NU folosește fișierele locale de pe disc

## Rollback

```bash
# Revert la commit anterior
git revert HEAD
git push origin master
# Deploy din Coolify
```
