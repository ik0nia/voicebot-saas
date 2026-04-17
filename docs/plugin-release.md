# Sambla WooCommerce Plugin — release procedure

## TL;DR — happy path

```bash
# Inside the app container:
php artisan sambla:release-plugin 2.3.0
# ...read the next-steps block printed at the end, then:

# 1. Edit changelog
$EDITOR app/Http/Controllers/Api/V1/PluginUpdateController.php   # add <h4>2.3.0</h4>...

# 2. Commit + push
git add wordpress-plugin/ public/downloads/ app/Http/Controllers/Api/V1/PluginUpdateController.php .env
git commit -m "release: plugin 2.3.0"
git push origin master

# 3. Verify in WP admin of a test tenant
#    Dashboard → Updates → Check Again
#    Plugins → 'View version 2.3.0 details' → shows your changelog
#    → Update now — should install successfully, not loop.
```

That is the whole thing.

---

## What the artisan command does

Five steps, in order. Each is logged with `[n/5]`. Command fails early on any
step that can't complete.

| # | Step | Why it exists |
|---|------|---------------|
| 1 | Bump `Version:` header + `SAMBLA_VERSION` constant in `wordpress-plugin/sambla-woocommerce/sambla-woocommerce.php` | WP reads the header; the constant is used in the WP admin UI for the widget script query string |
| 2 | Build versioned ZIP at `public/downloads/sambla-woocommerce-{version}.zip` | `PluginUpdateController` prefers this versioned path because it busts any CDN cache — Cloudflare never served this URL before, guaranteed fresh fetch |
| 3 | Copy versioned ZIP over the stable pointer `public/downloads/sambla-woocommerce.zip` | Legacy fallback. Older PluginUpdateController builds (pre-f8e4fc8) still reference this path. Kept so nothing breaks during the rolling deploy window. |
| 4 | Update `SAMBLA_PLUGIN_VERSION=` in `.env` | `config('sambla.plugin_version')` reads from env. Without this, `/api/v1/plugin/update-check` still returns the old `new_version` and WP concludes there's no update |
| 5 | `php artisan config:clear` | Laravel caches config in production. Without this, step 4 doesn't take effect until a fresh `config:cache` run. |

## What the command does NOT do (intentional)

- **git commit / push** — you do that yourself so the commit message makes
  sense and you control when the change goes live.
- **Changelog edit** — the changelog sits inside
  `PluginUpdateController::getChangelog()` as a PHP heredoc. It's not
  mechanically derivable from commits, so the command just tells you where
  to paste the new entry.
- **Cloudflare purge** — the versioned URL in step 2 is the reason this
  isn't needed. Each release gets a fresh URL the CDN has never seen.

## Flags

- `--dry-run` — logs every step but writes nothing. Useful for sanity-checking
  before a real release.
- `--force` — allows re-running the same version (or a lower one). Not
  needed in normal flow; handy when a build step failed mid-way and you
  need to re-run without bumping the version.

## Common failure modes + fixes

### WP shows "update available" but clicking Update loops back to the old version

This is the CDN staleness bug that caused the f8e4fc8 fix. Only happens if:
- You bumped version but only updated the stable ZIP pointer (not the
  versioned one), or
- The `PluginUpdateController` is still returning the generic (non-versioned)
  `package` URL.

Fix: re-run the release command. It writes both the versioned ZIP and the
stable pointer. The controller since f8e4fc8 auto-picks the versioned path.

### Permission denied on stable ZIP

If `public/downloads/sambla-woocommerce.zip` is owned by root (from an older
deploy) the command tries `sudo -n cp` automatically. If that also fails
(no passwordless sudo), the command prints the exact `sudo cp` line — run
it by hand, then re-run the release command with `--force`.

### `.env` not updated

The command writes to `base_path('.env')`. If you're running the command
outside the container and the host `.env` differs from the container `.env`
(Coolify-style deploys often have a mounted `.env.production` or similar),
the update won't reach the live process. Verify with:

```bash
sudo docker exec <app-container> grep SAMBLA_PLUGIN_VERSION /var/www/html/.env
```

If it doesn't match, run the release command INSIDE the container instead:

```bash
sudo docker exec -it <app-container> php /var/www/html/artisan sambla:release-plugin 2.3.0
```

### ZIP built but missing a file you added

`zip -r` picks up whatever is on disk in `wordpress-plugin/sambla-woocommerce/`.
The command excludes `.DS_Store`, `*.bak*`, and `__MACOSX`. If you added a
new file and git doesn't track it (forgot `git add`), it WILL still land in
the ZIP because the file exists on disk. Verify with:

```bash
unzip -l public/downloads/sambla-woocommerce-2.3.0.zip
```

## Manual fallback procedure (if the command can't run)

If artisan is unavailable for any reason, you can do the five steps manually:

```bash
V=2.3.0

# 1. Bump version in source (two lines)
sed -i "s/Version: [0-9.]*$/Version: $V/" wordpress-plugin/sambla-woocommerce/sambla-woocommerce.php
sed -i "s/SAMBLA_VERSION', '[0-9.]*'/SAMBLA_VERSION', '$V'/" wordpress-plugin/sambla-woocommerce/sambla-woocommerce.php

# 2. Build versioned zip
(cd wordpress-plugin && zip -r "../public/downloads/sambla-woocommerce-$V.zip" sambla-woocommerce -x "*.DS_Store")

# 3. Stable pointer
sudo cp public/downloads/sambla-woocommerce-$V.zip public/downloads/sambla-woocommerce.zip
sudo chown sambla:sambla public/downloads/sambla-woocommerce.zip

# 4. Env
sudo docker exec <app-container> sed -i "s/^SAMBLA_PLUGIN_VERSION=.*/SAMBLA_PLUGIN_VERSION=$V/" /var/www/html/.env

# 5. Clear config cache
sudo docker exec <app-container> php /var/www/html/artisan config:clear

# 6. Changelog — edit app/Http/Controllers/Api/V1/PluginUpdateController.php
# 7. Commit + push
```

The artisan command is a dumb wrapper around exactly these six shell
commands — if you ever need to read what it actually does, `handle()` in
`app/Console/Commands/ReleasePlugin.php` is short and uncommented in the
procedural sense.

## Verifying the release landed

From any machine:

```bash
# 1. API reports the new version
curl -s "https://sambla.ro/api/v1/plugin/update-check?slug=sambla-woocommerce&version=1.0.0" | jq .

# Expected:
#   new_version: "2.3.0"
#   package:     "https://sambla.ro/downloads/sambla-woocommerce-2.3.0.zip"

# 2. ZIP serves fresh (cf-cache-status MISS on first hit)
curl -sI "https://sambla.ro/downloads/sambla-woocommerce-2.3.0.zip"
# content-length should match `ls -la public/downloads/sambla-woocommerce-2.3.0.zip`
# last-modified should be today

# 3. Plugin header inside the downloaded ZIP is actually 2.3.0
curl -s "https://sambla.ro/downloads/sambla-woocommerce-2.3.0.zip" | \
  bsdtar -xOf - sambla-woocommerce/sambla-woocommerce.php | grep -E 'Version:|SAMBLA_VERSION'
```

All three should line up. If one is wrong, re-run the release command with
`--force` and check which step prints the mismatched path.
