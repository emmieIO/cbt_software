#!/bin/bash

set -e

echo "🚀 Starting Bare Metal Deployment..."

cd /var/www/cbt_software

# 1. Pull latest code
git pull origin master

# 2. Dependencies
composer install --no-dev --optimize-autoloader

# Clear stale caches that might block wayfinder:generate boot
php artisan optimize:clear || true

# Clean up stale Wayfinder types to fix build errors
# We run this before npm run build to ensure types are available for Vite
rm -rf resources/js/actions
php artisan wayfinder:generate --with-form

npm install
npm run build

# 3. Laravel Housekeeping
php artisan migrate --force

# Sync the new permission names and role categories
php artisan db:seed --class=RevampPermissionsSeeder --force

# Reset Spatie permission cache
php artisan permission:cache-reset

# Ensure public storage link exists for image uploads
php artisan storage:link || true

# Rebuild Laravel caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Permissions (Crucial for the web user)
sudo chown -R www-data:www-data storage bootstrap/cache public/build
sudo chmod -R 775 storage bootstrap/cache

# 5. Restart FrankenPHP
sudo systemctl restart frankenphp

echo "✅ Site is live!"
