# ==============================================================================
# E-Lingkod Dasol HRIS - Production Dockerfile for Render
# ==============================================================================

# --- Stage 1: Build frontend assets ---
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources/ resources/
RUN npm run build

# --- Stage 2: Install PHP dependencies ---
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# --- Stage 3: Final production image ---
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    gettext \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    autoconf \
    gcc \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        mbstring \
        bcmath \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf gcc g++ make

WORKDIR /app

COPY . .
COPY --from=composer /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

COPY nginx.conf /app/nginx.conf
COPY php-fpm.conf /app/php-fpm.conf
COPY supervisord.conf /app/supervisord.conf
COPY start.sh /app/start.sh

RUN chmod +x /app/start.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["bash", "/app/start.sh"]
