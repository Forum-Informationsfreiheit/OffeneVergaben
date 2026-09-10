# OffeneVergaben.at — Laravel 5.8 / PHP 7.4 on Apache
#
# Stages:
#   base   PHP extensions, Apache and php.ini, shared by dev and prod
#   dev    local development; compose.yaml bind-mounts the repository over /var/www/html
#   build  installs the production composer dependencies into a copy of the code
#   prod   code + vendor/ baked in, runs as www-data (default target, built by CI)

# ------------------------------------------------------------------------------------------------
FROM php:7.4-apache-bullseye AS base

# Debian bullseye is past its LTS window (ended 2026-08-31) and is being moved to
# archive.debian.org, so accept expired Release files. The deb.debian.org CDN has already
# dropped the bullseye-security packages while still serving their index (404 on download), so
# security updates come from security.debian.org directly. If installing fails anyway — bullseye
# gone from there too — retry everything against archive.debian.org.
#
# Only the build dependencies of the extensions below: libpng/libjpeg for gd, libzip for zip.
RUN set -eux; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    sed -i 's|deb.debian.org/debian-security|security.debian.org/debian-security|g' /etc/apt/sources.list; \
    install_packages() { \
        apt-get update -qq \
        && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libzip-dev; \
    }; \
    if ! install_packages; then \
        sed -i \
            -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' \
            -e 's|deb.debian.org|archive.debian.org|g' \
            /etc/apt/sources.list; \
        install_packages; \
    fi; \
    rm -rf /var/lib/apt/lists/*

# Everything else Laravel needs (mbstring, dom, curl, openssl, fileinfo, pdo, ...) is already
# compiled into the base image.
#   exif       required by unisharp/laravel-filemanager — composer refuses to install without it
#   gd         image thumbnails in laravel-filemanager (via intervention/image)
#   opcache    keeps Laravel's bootstrap at ~25 ms per request
#   pdo_mysql  database
#   zip        MakeDatasetsCsvDumpJob; composer also unpacks packages with it (no unzip binary)
RUN set -eux; \
    docker-php-ext-configure gd --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        exif \
        gd \
        opcache \
        pdo_mysql \
        zip

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Port 8080: dev runs as an unprivileged UID under rootless podman and prod runs as www-data;
# neither may bind port 80.
RUN set -eux; \
    a2enmod rewrite; \
    sed -i 's|^Listen 80$|Listen 8080|' /etc/apache2/ports.conf

# ------------------------------------------------------------------------------------------------
FROM base AS dev

# Xdebug for PHPUnit code coverage. PHPUnit 7.5 (php-code-coverage 6) only understands
# Xdebug 2 — Xdebug 3 fails its `xdebug.coverage_enable` check. Installed but NOT enabled,
# so Apache runs without the overhead; load it per run with `php -d zend_extension=xdebug`.
RUN set -eux; \
    pecl install xdebug-2.9.8; \
    rm -rf /tmp/pear

# Fully qualified so podman does not have to guess the registry for the build stage.
COPY --from=docker.io/library/composer:2.2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/as-app.sh /usr/local/bin/as-app

# Match the file owner on the host so the bind-mounted repo stays writable. Declared here, not
# at the top, so a different UID/GID does not invalidate the cached layers above.
ARG UID=1000
ARG GID=1000

# Remap www-data to the host user and hand it the Apache runtime directories — with rootless
# podman + keep-id the whole container runs as this user.
RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/as-app; \
    groupmod -o -g "${GID}" www-data; \
    usermod -o -u "${UID}" -g "${GID}" www-data; \
    chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2 /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]

# ------------------------------------------------------------------------------------------------
FROM base AS build

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=docker.io/library/composer:2.2 /usr/bin/composer /usr/local/bin/composer

# Dependencies first: this layer stays cached as long as composer.json/composer.lock don't change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

# The optimized autoloader needs the code. Its post-autoload-dump script (artisan
# package:discover) boots Laravel, which needs storage/ and bootstrap/cache/ to exist.
RUN set -eux; \
    mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs; \
    composer dump-autoload --optimize --no-dev --no-interaction

# ------------------------------------------------------------------------------------------------
FROM base AS prod

COPY docker/prod-entrypoint.sh /usr/local/bin/entrypoint.sh
COPY --from=build /var/www/html /var/www/html

# The code stays owned by root; www-data only gets the directories the app writes to.
# public/ itself is group-writable with the sticky bit: GenerateSitemapJob replaces
# public/sitemap.xml and public/sitemaps/, but may not delete root-owned files like index.php.
RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint.sh; \
    chown root:root /var/www/html; \
    chmod 755 /var/www/html; \
    mkdir -p public/sitemaps public/tmp public/uploads; \
    ln -s ../storage/app/public public/storage; \
    chown root:www-data public; \
    chmod 1775 public; \
    chown -R www-data:www-data bootstrap/cache storage public/sitemaps public/tmp public/uploads

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
