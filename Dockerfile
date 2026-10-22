
# =========================================
# 1. PHP base
# =========================================
FROM php:8.4-fpm-bookworm AS php-base

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    ca-certificates \
    && docker-php-ext-install \
        pdo_pgsql \
        bcmath \
        intl \
        zip \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# =========================================
# 2. Composer dependencies
# =========================================
FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader

# =========================================
# 3. Vue + Vite + Wayfinder
# =========================================
FROM php-base AS frontend

# Install Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Composer dependencies agar Artisan tersedia
COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

# Install frontend dependencies
COPY package.json package-lock.json ./

RUN npm ci

# Copy source code
COPY . .

# Build-time environment
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

RUN npm run build

# =========================================
# 4. Laravel production runtime
# =========================================
FROM php-base AS production

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Copy production Composer dependencies
COPY --from=vendor /var/www/html/vendor ./vendor

# Copy Vite build
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-scripts \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]

# =========================================
# 5. Nginx production
# =========================================
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf \
    /etc/nginx/conf.d/default.conf

COPY --from=production \
    /var/www/html/public \
    /var/www/html/public