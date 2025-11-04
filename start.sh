#!/bin/bash

# Production startup script for E-Lingkod Dasol HRIS on Railway
# This script ensures proper server binding and error handling

# Don't exit on error - we want the app to start even if some checks fail
set +e

echo "Starting E-Lingkod Dasol HRIS on Railway..."
echo "Environment: ${APP_ENV:-production}"
echo "Port: ${PORT:-8000}"
echo "Application URL: ${APP_URL:-http://localhost}"

# Set default port if not provided
PORT=${PORT:-8000}
HOST=${HOST:-0.0.0.0}

echo "Binding to $HOST:$PORT"

# Check if required directories exist
if [ ! -d "storage/logs" ]; then
    mkdir -p storage/logs
    chmod 755 storage/logs
fi

# Ensure proper permissions for Laravel directories
chmod -R 755 storage bootstrap/cache
chmod -R 755 public

# Clear any stale cache files that might cause issues
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true

# Database connectivity check (simplified for Railway)
echo "Database connectivity will be checked by middleware..."

# Start PHP built-in server with proper binding
echo "Starting PHP development server..."
exec php -S "$HOST:$PORT" -t public index.php