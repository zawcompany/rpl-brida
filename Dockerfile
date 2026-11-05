# syntax=docker/dockerfile:1
# =============================================================================
# SIMPIL — image multi-stage (PHP-FPM + Nginx, Alpine)
#   target "app"  : PHP-FPM + kode + vendor + aset Vite   (production; juga dipakai worker antrean)
#   target "web"  : Nginx + public/ (aset statis); meneruskan *.php ke PHP-FPM
#   target "base" : PHP-FPM + ekstensi saja (dipakai docker-compose dev dengan bind-mount)
# Konfigurasi (kunci, DB, S3, ...) TIDAK dimasukkan ke image: semuanya dari environment variable.
# =============================================================================

# ---- base: PHP-FPM 8.4 + ekstensi yang dipakai aplikasi --------------------
# Symfony 8.1 (di composer.lock) mensyaratkan PHP >= 8.4.1.
FROM php:8.4-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# pdo_mysql (RDS/MySQL), redis (ElastiCache), gd/zip/intl/bcmath (sama dengan provision-ec2.sh), opcache
RUN install-php-extensions pdo_mysql redis gd zip intl bcmath opcache \
    && apk add --no-cache curl \
    # Bundle CA RDS agar MYSQL_ATTR_SSL_CA=/etc/ssl/certs/rds-global-bundle.pem berfungsi
    && curl -fsSL -o /etc/ssl/certs/rds-global-bundle.pem https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem \
    && apk del curl

COPY docker/php/simpil.ini /usr/local/etc/php/conf.d/99-simpil.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---- vendor: dependensi PHP tanpa dev (cache layer terpisah dari kode) ------
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# ---- assets: build Tailwind/Vite -------------------------------------------
FROM node:20-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
# Tailwind memindai view/JS saat build; sertakan semua yang direferensikan vite.config.js
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- app: image production PHP-FPM -----------------------------------------
FROM base AS app
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /build/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    # Direktori tulis Laravel; berkas lain bersifat read-only bagi www-data
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000

# ---- web: Nginx (menyajikan aset statis, FastCGI ke PHP-FPM) ----------------
FROM nginx:1.27-alpine AS web
# Template diproses envsubst saat start: PHP_FPM_HOST = "app" (compose) atau 127.0.0.1 (ECS, satu task)
ENV PHP_FPM_HOST=127.0.0.1
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 80
