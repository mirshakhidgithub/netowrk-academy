# ─────────────────────────────────────────────
# Stage 1: Build Vue/Vite frontend assets
# ─────────────────────────────────────────────
FROM node:22-alpine AS frontend

WORKDIR /app

RUN npm install -g pnpm

# Copy all source first (needed for postinstall scripts like build:icons)
COPY . .

# Install deps — ignore postinstall scripts that depend on source files
# (build:icons and msw:init will run as part of `pnpm run build` instead)
RUN pnpm install --no-frozen-lockfile --ignore-scripts

# Generate iconify icons.css (required by the build)
RUN pnpm run build:icons

# Initialize MSW (creates public/mockServiceWorker.js)
RUN pnpm run msw:init || true

# Build the Vue/Vite app
RUN pnpm run build

# ─────────────────────────────────────────────
# Stage 2: PHP 8.3 + Nginx (single container)
# ─────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS app

# Install system dependencies + Nginx + Supervisor
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
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    pcntl \
    bcmath \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy PHP source (project is at repo root)
COPY . .

# Fix git safe.directory for composer, then resolve all dependencies
RUN git config --global --add safe.directory /var/www/html \
    && composer update \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Copy built Vue assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy configs
COPY docker/app/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/app/supervisord.conf /etc/supervisord.conf
COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Nginx logs to stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
