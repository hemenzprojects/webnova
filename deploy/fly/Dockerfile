# ---- Stage 1: Build Nuxt frontend ----
FROM node:20-alpine AS nuxt-builder
WORKDIR /build
COPY frontend/package*.json ./
RUN npm ci --prefer-offline
COPY frontend/ .
# Relative API path works for every tenant domain at runtime
ENV NUXT_PUBLIC_API_BASE=/api/v1
ENV NUXT_API_BASE_SSR=http://127.0.0.1:8080/api/v1
RUN npm run build

# ---- Stage 2: PHP + Nginx + Node runtime ----
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git curl libpng-dev libxml2-dev libzip-dev \
    zip unzip oniguruma-dev icu-dev \
    nginx supervisor nodejs \
    postgresql-client libpq-dev

# PostgreSQL driver (replaces mysql for Fly Postgres)
RUN docker-php-ext-install \
    pdo_pgsql pgsql \
    gd xml mbstring zip bcmath opcache intl

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Laravel backend
COPY backend/ .
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction

RUN php artisan filament:assets

# Built Nuxt SSR output
COPY --from=nuxt-builder /build/.output /var/www/frontend/.output

# Fly.io runtime configs
COPY deploy/fly/docker/nginx.conf /etc/nginx/nginx.conf
COPY deploy/fly/docker/supervisord.conf /etc/supervisord.conf
COPY deploy/fly/docker/start.sh /start.sh
RUN chmod +x /start.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && mkdir -p /run/nginx /var/log/supervisor

# PHP production settings
RUN printf "opcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0\n" \
        > /usr/local/etc/php/conf.d/opcache.ini \
    && printf "upload_max_filesize=20M\npost_max_size=20M\n" \
        > /usr/local/etc/php/conf.d/uploads.ini \
    && printf "memory_limit=512M\n" \
        > /usr/local/etc/php/conf.d/memory.ini

EXPOSE 8080

CMD ["/start.sh"]