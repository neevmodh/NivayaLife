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
# Must match (or exceed) whatever composer.lock's dependencies actually
# require — a mismatch here doesn't fail the build, it fails at container
# boot instead ("Composer detected issues in your platform: requires PHP
# >= 8.4.1"), which is a much worse place to find out.
FROM php:8.4-fpm-bookworm

# System packages: Tesseract OCR + Ghostscript (PDF rasterization for the
# OCR pipeline and QR/PDF services), nginx + supervisor to run the whole
# stack in one container, plus the usual PHP extension build deps.
#
# The `-dev` packages are deliberately NOT purged after building the
# extensions: apt's autoremove doesn't reliably distinguish "only needed
# for headers" from "the extension's actual runtime .so lives here too" —
# purging them previously took libzip's and ImageMagick's runtime shared
# libraries with them, so zip/imagick loaded at build time but silently
# failed to load at container boot instead.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor gettext-base \
        tesseract-ocr tesseract-ocr-hin tesseract-ocr-guj ghostscript \
        libmagickwand-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install imagick && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/* \
    # ImageMagick's Debian package ships a default policy.xml that blocks its
    # PDF/PS/EPS coders (the Ghostscript delegate) outright, as a blanket
    # security-hardening default unrelated to whether the app actually needs
    # them — it does, that's the entire point of installing ghostscript
    # above, to rasterize PDF reports for OCR. Without this, every PDF
    # upload fails at Imagick::readImage() with "not authorized" rather than
    # anywhere OCR-quality-related. Scoped to just PDF/PS/EPS rather than
    # disabling the policy file wholesale.
    && find /etc/ImageMagick* -name policy.xml -exec sed -i \
        -e '/pattern="PDF"/s/rights="none"/rights="read"/' \
        -e '/pattern="PS"/s/rights="none"/rights="read"/' \
        -e '/pattern="EPS"/s/rights="none"/rights="read"/' \
        {} +

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
