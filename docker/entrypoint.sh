#!/bin/sh
# Prepares the bind-mounted Laravel application before handing over to Apache
# (or to whatever command the compose service defines).
set -e

APP_DIR=/var/www/html
cd "$APP_DIR"

log() { printf '[entrypoint] %s\n' "$*"; }

# Run a command as the application user (see docker/as-app.sh — the container is root
# under Docker, but already the host user under rootless podman with keep-id).
as_app() { /usr/local/bin/as-app sh -c "$1"; }

set_env_var() {
    key="$1"
    val="$2"
    if grep -qE "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        printf '%s=%s\n' "$key" "$val" >> .env
    fi
}

wait_for_db() {
    log "waiting for database ${DB_HOST:-db}:${DB_PORT:-3306} ..."
    i=0
    while [ "$i" -lt 60 ]; do
        if php -r '
            $h = getenv("DB_HOST") ?: "db";
            $p = getenv("DB_PORT") ?: "3306";
            $d = getenv("DB_DATABASE");
            $u = getenv("DB_USERNAME");
            $w = getenv("DB_PASSWORD");
            new PDO("mysql:host=$h;port=$p;dbname=$d", $u, $w);
        ' 2>/dev/null; then
            log "database is up"
            return 0
        fi
        i=$((i + 1))
        sleep 2
    done
    log "ERROR: database did not become reachable in time"
    return 1
}

# --- writable directories -------------------------------------------------
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache \
    public/sitemaps \
    public/tmp \
    public/uploads

# Bind mounts under rootless podman can already have the right owner; ignore failures.
chown -R www-data:www-data storage bootstrap/cache public/sitemaps public/tmp public/uploads 2>/dev/null || true

# --- secondary containers (scheduler) skip the one-time init --------------
if [ "${APP_INIT:-true}" != "true" ]; then
    log "APP_INIT=false — waiting for the app container to finish initialising"
    while [ ! -f vendor/autoload.php ] || [ ! -f .env ]; do sleep 3; done
    exec "$@"
fi

# --- .env -----------------------------------------------------------------
if [ ! -f .env ]; then
    log "creating .env from .env.example"
    cp .env.example .env
    chown www-data:www-data .env 2>/dev/null || true
fi

# Keep .env in sync with the values compose passes in, so artisan on the CLI and
# the web request path agree.
set_env_var APP_ENV        "${APP_ENV:-local}"
set_env_var APP_DEBUG      "${APP_DEBUG:-true}"
set_env_var APP_URL        "${APP_URL:-http://localhost:8000}"
set_env_var DB_CONNECTION  "${DB_CONNECTION:-mysql}"
set_env_var DB_HOST        "${DB_HOST:-db}"
set_env_var DB_PORT        "${DB_PORT:-3306}"
set_env_var DB_DATABASE    "${DB_DATABASE:-offenevergaben}"
set_env_var DB_USERNAME    "${DB_USERNAME:-offenevergaben}"
set_env_var DB_PASSWORD    "${DB_PASSWORD:-secret}"
set_env_var DB_SCRAPER_CONNECTION "${DB_SCRAPER_CONNECTION:-mysql_scraper}"
set_env_var DB_SCRAPER_HOST       "${DB_SCRAPER_HOST:-db}"
set_env_var DB_SCRAPER_PORT       "${DB_SCRAPER_PORT:-3306}"
set_env_var DB_SCRAPER_DATABASE   "${DB_SCRAPER_DATABASE:-fif_scraper}"
set_env_var DB_SCRAPER_USERNAME   "${DB_SCRAPER_USERNAME:-fif_scraper}"
set_env_var DB_SCRAPER_PASSWORD   "${DB_SCRAPER_PASSWORD:-fif_scraper}"

# --- dependencies ---------------------------------------------------------
if [ ! -f vendor/autoload.php ]; then
    log "installing composer dependencies (this takes a few minutes on first start)"
    as_app "COMPOSER_HOME=/tmp/composer composer install --no-interaction --prefer-dist --no-progress"
fi

# --- application key ------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:' .env; then
    log "generating APP_KEY"
    as_app "php artisan key:generate --force --no-interaction"
fi

as_app "php artisan config:clear --no-interaction" >/dev/null 2>&1 || true

# --- database -------------------------------------------------------------
wait_for_db

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "running migrations"
    as_app "php artisan migrate --force --no-interaction"
fi

# --- public/storage symlink ----------------------------------------------
if [ ! -e public/storage ]; then
    as_app "php artisan storage:link --no-interaction" || true
fi

log "ready — http://localhost:${APP_HOST_PORT:-8000}"

exec "$@"
