#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure correct permissions for storage and cache when mounted from host
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rw storage bootstrap/cache || true

# Ensure composer dependencies are installed (vendor cached via volume or image layer)
if [ ! -d vendor ]; then
  composer install --no-dev --prefer-dist --no-interaction --no-progress
fi

# Copy env if missing
if [ ! -f .env ]; then
  cp .env.docker .env || true
fi

# Generate app key if not set
php artisan key:generate --force --no-interaction || true

# Generate JWT secret if not present
php artisan jwt:secret --force --no-interaction || true

# Optimize config/routes
php artisan config:clear || true
php artisan route:clear || true
php artisan config:cache || true
php artisan route:cache || true

# DB wait and migrate moved to dedicated compose service to avoid delaying PHP-FPM

exec "$@"


