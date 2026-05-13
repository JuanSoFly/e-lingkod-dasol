#!/bin/bash

# E-Lingkod Dasol HRIS - Railway Utility Script
# This script can be run manually if needed, but is NOT auto-invoked.
# The main startup logic is in start.sh (via nixpacks.toml).

set -e

echo "🚀 Starting E-Lingkod Dasol HRIS manual setup..."

# Set environment variables for production
export APP_ENV=production
export APP_DEBUG=false
export LOG_CHANNEL=stderr

# Ensure Laravel storage directories exist and have proper permissions
echo "📁 Setting up storage directories..."
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p bootstrap/cache

# Set proper permissions for Laravel directories
echo "🔐 Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Generate Laravel application key ONLY if not already set
if [ -z "$APP_KEY" ]; then
    echo "🔑 APP_KEY not set, generating new key..."
    echo "⚠️  IMPORTANT: Copy the generated key and set it as APP_KEY in Railway dashboard!"
    php artisan key:generate --show
else
    echo "✅ APP_KEY is already set via environment variable"
fi

# Create storage symbolic link
echo "🔗 Creating storage symlink..."
php artisan storage:link --force

# Run database migrations (only if database is available)
echo "🗄️ Running database migrations..."
if php artisan migrate:status >/dev/null 2>&1; then
    echo "✅ Database connection confirmed, running migrations..."
    php artisan migrate --force
else
    echo "⚠️ Database not yet available, migrations will run on next deploy"
fi

# Clear and cache Laravel configurations
echo "💾 Optimizing Laravel for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ E-Lingkod Dasol HRIS setup completed!"