#!/bin/bash

# E-Lingkod Dasol HRIS - Railway Pre-deployment Script
# This script prepares the Laravel application for Railway deployment

set -e  # Exit on any error

echo "🚀 Starting E-Lingkod Dasol HRIS deployment setup..."

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

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "⚙️ Creating .env file from example..."
    cp .env.example .env
fi

# Generate Laravel application key if not set
echo "🔑 Setting up application key..."
php artisan key:generate --force

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

# Seed basic data if this is a fresh installation
echo "🌱 Checking if database seeding is needed..."
if php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | grep -q "0"; then
    echo "🌱 Running database seeders for fresh installation..."
    php artisan db:seed --class=DatabaseSeeder --force
fi

# Warm up cache if possible
echo "🔥 Warming up application cache..."
php artisan cache:clear >/dev/null 2>&1 || true

# Verify health endpoint is accessible
echo "🏥 Verifying health endpoint..."
curl -f http://localhost:${PORT:-8000}/health >/dev/null 2>&1 || echo "⚠️ Health endpoint will be available after startup"

echo "✅ E-Lingkod Dasol HRIS deployment setup completed!"
echo "🎯 Application is ready for Railway deployment"