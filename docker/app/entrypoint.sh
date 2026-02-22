#!/bin/bash
set -e

echo "🚀 Starting CCNA Trainer..."

# Generate APP_KEY if not set or empty
if [ -z "$APP_KEY" ]; then
    echo "⚙️  Generating APP_KEY..."
    php artisan key:generate --force
fi

# Wait for DB to be ready (max 30s)
echo "⏳ Waiting for database..."
for i in $(seq 1 15); do
    php artisan db:show --no-ansi > /dev/null 2>&1 && break
    echo "  DB not ready, retrying in 2s... ($i/15)"
    sleep 2
done

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

php artisan db:seed --force

# Cache config & routes for performance
echo "⚙️  Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create supervisor log dir
mkdir -p /var/log/supervisor

echo "✅ Ready! Starting Nginx + PHP-FPM via supervisord..."
exec supervisord -c /etc/supervisord.conf
