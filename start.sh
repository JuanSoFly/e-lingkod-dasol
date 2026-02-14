#!/bin/bash
set -e

echo "🚀 Starting E-Lingkod Dasol HRIS..."

# Create required temp directories
mkdir -p /tmp/nginx-client-body /tmp/nginx-proxy /tmp/nginx-fastcgi /tmp/nginx-uwsgi /tmp/nginx-scgi

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
