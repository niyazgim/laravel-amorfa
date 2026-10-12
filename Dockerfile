# syntax=docker/dockerfile:1.7

# ── Stage 1: Composer vendor stage ────────────────────────────────
# Official Composer image resolves all PHP dependencies.
# Nothing about Composer lives in the runtime image.
FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Layer-cache deps separately from code.
COPY composer.json composer.lock* ./

RUN set -eux \
    && composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress \
    || composer update --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# Copy source and dump the optimized autoloader inside this stage.
# --no-scripts: artisan is not runnable during build (no .env yet).
COPY ./ /app/

RUN composer dump-autoload --optimize --no-dev --no-scripts

# ── Stage 2: Runtime ──────────────────────────────────────────────
# FrankenPHP bundles Caddy + PHP-FPM in one binary — single container.
# PHP 8.4: config/database.php references Pdo\Mysql (8.4+ only).
FROM dunglas/frankenphp:1-php8.4-alpine AS runtime

# CA certs for HTTPS; git/unzip for in-container composer runs.
# postgresql-client provides pg_isready for the entrypoint wait loop.
RUN apk add --no-cache ca-certificates git unzip postgresql-client

# Pull Composer from the official image — same pattern as the guide.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Official php extension installer — no Alpine package drift.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# @composer is NOT here — Composer comes from the composer:2 image above.
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
        zip

# php.ini overrides — production-oriented defaults.
COPY <<'PHPINI' /usr/local/etc/php/conf.d/99-app.ini
memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 60
max_input_time = 60

opcache.enable = 1
opcache.enable_cli = 0
opcache.validate_timestamps = 0
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

# Take the whole resolved app (source + vendor) from the vendor stage.
COPY --from=vendor /app /app

# Writable runtime dirs.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint: key guard, permissions fix, DB wait, migrations.
RUN cat > /usr/local/bin/entrypoint.sh <<'ENTRYPOINT'
#!/bin/sh
set -e

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "base64:CHANGE_ME" ]; then
    echo "WARNING: APP_KEY missing or placeholder — generating a temporary key." >&2
    echo "Set APP_KEY in .env and restart to persist it." >&2
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
fi

# Fix volume ownership for unprivileged worker execution.
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
