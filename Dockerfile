# syntax=docker/dockerfile:1.7

# FrankenPHP bundles Caddy + PHP-FPM in one binary, so the app ships as
# a single container. No nginx, no supervisor, no process manager.
#
# PHP 8.4 (not 8.3): config/database.php references Pdo\Mysql, which only
# exists on 8.4+. Pinning 8.4 removes the version drift landmine.
FROM dunglas/frankenphp:1-php8.4-alpine

# ca-certificates — Alpine ships without a CA bundle, so HTTPS to
#                   packagist fails TLS verification.
# git, unzip      — composer clones and extracts dist archives.
# postgresql-client — provides pg_isready for the entrypoint wait loop.
RUN apk add --no-cache ca-certificates git unzip postgresql-client

# install-php-extensions is shipped by the FrankenPHP base image.
# `@composer` is a special alias that installs the Composer binary.
RUN install-php-extensions \
        bcmath \
        ctype \
        curl \
        dom \
        fileinfo \
        filter \
        gd \
        gmp \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        pgsql \
        redis \
        tokenizer \
        xml \
        zip \
        @composer

# php.ini overrides — inlined so the whole stack is exactly two files.
COPY <<'PHPINI' /usr/local/etc/php/conf.d/99-app.ini
memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 60
max_input_time = 60

opcache.enable = 1
opcache.enable_cli = 0
opcache.validate_timestamps = 1
opcache.revalidate_freq = 0
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.interned_strings_buffer = 16

display_errors = Off
log_errors = On
error_log = /dev/stderr
PHPINI

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Deps first so this layer caches across code edits.
# `composer.lock*` keeps the COPY valid on a fresh checkout with no lock.
# If the lock is stale, install exits 2 and we fall back to update.
COPY composer.json composer.lock* ./
RUN set -eux \
    && composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress \
    || composer update --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# Application code.
COPY ./ /app/

# Optimized autoloader + writable runtime dirs. The mkdir is defensive:
# storage/framework/* subdirs are gitignored, so they may be absent on a
# fresh checkout even though .gitignore keeps the parents around.
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint: key bootstrap, DB wait, migrations, then exec CMD.
# Migrations run only when the web server is the CMD, so one-off
# `docker compose run app php artisan ...` invocations stay fast.
RUN cat > /usr/local/bin/entrypoint.sh <<'ENTRYPOINT'
#!/bin/sh
set -e

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "base64:CHANGE_ME" ]; then
    echo "WARNING: APP_KEY missing or placeholder — generating a temporary key." >&2
    echo "Set APP_KEY in .env and restart to persist it." >&2
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
fi

# The storage volume is remounted on every start; fix ownership so the
# unprivileged PHP worker can write into it.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ "$1" = "frankenphp" ] || [ "$1" = "php-fpm" ]; then
    if [ "${DB_CONNECTION:-}" = "pgsql" ]; then
        echo "Waiting for Postgres at ${DB_HOST:-db}:${DB_PORT:-5432}..."
        until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-laravel}" >/dev/null 2>&1; do
            sleep 2
        done
    fi
    php artisan migrate --force --no-interaction
fi

exec "$@"
ENTRYPOINT

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "php-server", "--root", "public/", "--listen", ":8000"]
