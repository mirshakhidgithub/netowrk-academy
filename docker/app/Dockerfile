# ─────────────────────────────────────────────
# Stage 1: Build Vue/Vite frontend assets
# ─────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app

RUN npm install -g pnpm

# 1. Copy only lockfile + package.json first → node_modules кэшируется отдельно
COPY package.json pnpm-lock.yaml ./

# Install deps — with scripts enabled so all packages are properly installed
RUN pnpm install --no-frozen-lockfile

# 2. Copy весь исходный код (нужен для сборки стилей, компонентов и т.д.)
COPY . .

# Generate iconify icons.css (required by the build)
RUN pnpm run build:icons

# Initialize MSW (creates public/mockServiceWorker.js)
RUN pnpm run msw:init || true

# Build the Vue/Vite app
RUN pnpm run build

# ─────────────────────────────────────────────
# Stage 2: PHP extensions base (редко меняется)
# ─────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS php_base

# Install system deps + compile PHP extensions — этот слой кэшируется
RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    git \
    autoconf \
    g++ \
    make \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    pcntl \
    bcmath \
    intl \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# ─────────────────────────────────────────────
# Stage 3: PHP app (меняется при каждом деплое)
# ─────────────────────────────────────────────
FROM php_base AS app

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3. Copy только composer файлы → кэш vendor не сбрасывается при изменении кода
COPY composer.json composer.lock ./

# Install PHP зависимости (использует composer.lock — детерминированно)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# 4. Copy весь код приложения
COPY . .

# Run composer scripts (post-autoload-dump и т.д.)
RUN composer run-script post-autoload-dump || true

# Copy built Vue assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy configs
COPY docker/app/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/app/zzz-docker.conf /usr/local/etc/php-fpm.d/zzz-docker.conf
COPY docker/app/supervisord.conf /etc/supervisord.conf
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Nginx logs to stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
