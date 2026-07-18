# syntax=docker/dockerfile:1

# ---- Stage 1: front-end assets ----
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

# ---- Stage 2: PHP dependencies ----
# The composer:2 image is CLI-only and doesn't have gd/imagick etc. — only
# the runtime stage below does — so platform-requirement checks have to be
# skipped here. The dependency code itself doesn't care which stage
# installed it, only that the runtime that actually executes it has the
# right extensions, which it does.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 3: runtime image ----
FROM php:8.3-fpm-bookworm

# System packages: Tesseract OCR + Ghostscript (PDF rasterization for the
# OCR pipeline and QR/PDF services), nginx + supervisor to run the whole
# stack in one container, plus the usual PHP extension build deps.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor gettext-base \
        tesseract-ocr ghostscript \
        libmagickwand-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install imagick && docker-php-ext-enable imagick \
    && apt-get purge -y --auto-remove libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/private storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/supervisord.conf.template /etc/supervisor/conf.d/supervisord.conf.template
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
