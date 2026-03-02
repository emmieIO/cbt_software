#!/bin/bash
set -e

echo "Deployment started..."

# Enter maintenance mode
(php artisan down) || true

# Update codebase
git pull origin master

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
npm install
npm run build

# Run database migrations
php artisan migrate --force

# Clear and cache routes/config/views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart Octane if it's being used, otherwise restart queue
if php artisan list | grep -q 'octane:reload'; then
    php artisan octane:reload
else
    php artisan queue:restart
fi

# Exit maintenance mode
php artisan up

echo "Deployment finished successfully!"
