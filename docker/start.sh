#!/bin/bash

set -e

echo "======================================"
echo "Starting Laravel application"
echo "======================================"

cd /var/www/html


# ------------------------------------------------------------
# Fix Laravel permissions
# ------------------------------------------------------------

echo "Fixing Laravel permissions..."

mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

chown -R www-data:www-data storage bootstrap/cache

chmod -R 775 storage bootstrap/cache


# ------------------------------------------------------------
# Check application key
# ------------------------------------------------------------

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not configured."
    exit 1
fi


# ------------------------------------------------------------
# Database migrations
# ------------------------------------------------------------

echo "Running database migrations..."

su -s /bin/bash www-data -c \
    "php artisan migrate --force"


# ------------------------------------------------------------
# Database seed
# ------------------------------------------------------------

echo "Running database seeders..."

su -s /bin/bash www-data -c \
    "php artisan db:seed --force"


# ------------------------------------------------------------
# Clear old Laravel caches
# ------------------------------------------------------------

echo "Clearing Laravel caches..."

su -s /bin/bash www-data -c \
    "php artisan config:clear"

su -s /bin/bash www-data -c \
    "php artisan cache:clear"


# ------------------------------------------------------------
# Build Laravel production caches
# ------------------------------------------------------------

echo "Building Laravel production caches..."

su -s /bin/bash www-data -c \
    "php artisan config:cache"

su -s /bin/bash www-data -c \
    "php artisan route:cache"

su -s /bin/bash www-data -c \
    "php artisan view:cache"


# ------------------------------------------------------------
# Start Supervisor
# ------------------------------------------------------------

echo "======================================"
echo "Starting Supervisor"
echo "======================================"

exec /usr/bin/supervisord \
    -c /etc/supervisor/conf.d/supervisord.conf