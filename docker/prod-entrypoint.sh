#!/bin/sh
# Entrypoint of the production image. Code and vendor/ are baked into the image; configuration
# comes from environment variables (APP_KEY, DB_*, DB_SCRAPER_*, MAIL_*, ...), not from .env.
set -e

cd /var/www/html

log() { printf '[entrypoint] %s\n' "$*" >&2; }

if [ -z "${APP_KEY:-}" ] && [ ! -f .env ]; then
    log "ERROR: APP_KEY is not set (generate one with: php artisan key:generate --show)"
    exit 1
fi

# storage/ is usually a volume in production; an empty one lacks the layout Laravel expects.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Compiled Blade views on a persistent volume may stem from the previous image.
php artisan view:clear --no-interaction >/dev/null

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    log "running migrations"
    php artisan migrate --force --no-interaction
fi

exec "$@"
