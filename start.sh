#!/bin/bash
set -e

echo "🚀 Starting E-Lingkod Dasol HRIS..."

# Create required temp directories
mkdir -p /tmp/nginx-client-body /tmp/nginx-proxy /tmp/nginx-fastcgi /tmp/nginx-uwsgi /tmp/nginx-scgi

# Ensure Laravel storage directories exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link --force 2>/dev/null || true
fi

# Run migrations if database is available (non-blocking)
echo "🗄️ Running database migrations..."
php artisan migrate --force 2>/dev/null || echo "⚠️ Migrations skipped (database may not be ready yet)"

# Substitute Railway's dynamic PORT into nginx config
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /app/nginx.conf > /tmp/nginx.conf

# Cache Laravel config, routes, and views for production
echo "📦 Caching Laravel configuration..."
cd /app
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✅ Starting services via supervisord..."
exec supervisord -c /app/supervisord.conf
