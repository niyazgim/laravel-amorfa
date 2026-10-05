# --- Stage 1: Build Assets & Dependencies ---
FROM php:8.3-fpm-alpine AS builder

WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application source
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# --- Stage 2: Production Image ---
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Install runtime production dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-libs \
    libzip \
    libpng

RUN docker-php-ext-install pdo_pgsql zip gd

# Copy application from builder stage
COPY --from=builder /var/www/html /var/www/html

# Copy Nginx, Supervisor, and PHP configurations
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set permissions for Laravel
RUN chown -R textures:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]