#!/bin/bash

# Take the application down
php artisan down
echo "Deploy in progress..."

# Reset any local changes
echo "git checkout ."
git checkout .

# Pull the latest changes from the repository
echo "git pull"
git pull

# Install composer dependencies without dev dependencies and optimize autoloader
echo "composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader

# Run database migrations
echo "php artisan migrate --force"
php artisan migrate --force

# Optimize the framework
echo "php artisan optimize"
php artisan optimize

# Cache and clear configuration (run without sudo to maintain consistent ownership)
echo "php artisan config:cache"
php artisan config:cache

echo "php artisan config:clear"
php artisan config:clear

# Clear compiled views
echo "php artisan view:clear"
php artisan view:clear

# Clear the cache
echo "php artisan cache:clear"
php artisan cache:clear

# Bring the application back up
php artisan up
echo "Deploy work completed!"
