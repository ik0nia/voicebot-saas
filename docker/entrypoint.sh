#!/bin/sh
# Container entrypoint for every service that ships the app image
# (app, queue, scheduler, reverb).
#
# Responsibilities:
#   1. Normalize ownership + mode on storage/ and bootstrap/cache
#      so any files that leaked through with root ownership get fixed
#      on restart. Chat has gone dark twice because the Laravel daily
#      log file got created by root and FPM (www-data) couldn't write
#      to it — the Log::error inside handlers threw and turned the
#      response into a generic "Server Error". Restart now heals it.
#   2. Drop to www-data before exec'ing anything that isn't php-fpm.
#      php-fpm itself needs root at boot (master process) and drops
#      workers to www-data on its own. Everything else (horizon, the
#      scheduler loop, reverb, one-shot artisan commands) runs as
#      www-data, so files they create inherit the correct owner.
#
# Fail-soft on perm writes — mount options (bind mounts, overlayfs)
# can forbid chown inside the container; we just keep going.

set -e

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    2>/dev/null || true
chmod -R 0775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    2>/dev/null || true

case "$1" in
    php-fpm|php-fpm*)
        # FPM master must start as root to read php-fpm.conf and open
        # the listen socket; it spawns workers as www-data itself.
        exec "$@"
        ;;
    *)
        exec su-exec www-data "$@"
        ;;
esac
